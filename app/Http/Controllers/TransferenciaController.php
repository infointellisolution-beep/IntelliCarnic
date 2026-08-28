<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\CompraDetalle;
use App\Models\Setting;
use App\Models\Sucursal;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use App\Services\TransferenciaSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransferenciaController extends Controller
{
    // ─────────────────────────────────────────────────────────
    //  VISTA PRINCIPAL (4 Pestañas)
    // ─────────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $tab = $request->get('tab', 'enviar');
        $settings = Setting::values();
        $sucursalActual = Sucursal::actual();
        $sucursales = Sucursal::where('activo', true)->orderBy('nombre')->get();
        $sucursalesDestino = Sucursal::destinos();

        // Artículos con stock > 0 para el formulario de envío
        $articulos = Articulo::with('familia')
            ->where('estado', '!=', 'inactivo')
            ->where('stock', '>', 0)
            ->orderBy('descripcion')
            ->get();

        // Historial de transferencias
        $queryHistorial = Transferencia::with(['sucursalOrigen', 'sucursalDestino', 'usuario', 'usuarioRecibe'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('filtro_estado')) {
            $queryHistorial->where('estado', $request->input('filtro_estado'));
        }
        if ($request->filled('filtro_fecha_desde')) {
            $queryHistorial->whereDate('fecha_envio', '>=', $request->input('filtro_fecha_desde'));
        }
        if ($request->filled('filtro_fecha_hasta')) {
            $queryHistorial->whereDate('fecha_envio', '<=', $request->input('filtro_fecha_hasta'));
        }

        $historial = $queryHistorial->paginate(15)->withQueryString();

        // Conteo de pendientes entrantes
        $pendientesCount = 0;
        if ($sucursalActual) {
            $pendientesCount = Transferencia::where('sucursal_destino_id', $sucursalActual->id)
                ->where('estado', 'en_transito')
                ->count();
        }

        return view('transferencias.index', compact(
            'tab',
            'settings',
            'sucursalActual',
            'sucursales',
            'sucursalesDestino',
            'articulos',
            'historial',
            'pendientesCount'
        ));
    }

    // ─────────────────────────────────────────────────────────
    //  REGISTRAR Y ENVIAR TRANSFERENCIA (Sucursal Origen)
    // ─────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sucursal_destino_id' => ['required', 'exists:sucursales,id'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.articulo_id' => ['required', 'exists:articulos,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.001'],
            'detalles.*.compra_detalle_id' => ['nullable', 'exists:compra_detalles,id'],
        ]);

        $sucursalActual = Sucursal::actual();
        if (!$sucursalActual) {
            return response()->json(['success' => false, 'error' => 'No hay una sucursal configurada como actual. Vaya a la pestaña "Sucursales" y configure una.'], 422);
        }

        if ($sucursalActual->id == $data['sucursal_destino_id']) {
            return response()->json(['success' => false, 'error' => 'No puede enviar mercancía a la misma sucursal.'], 422);
        }

        $transferencia = null;
        $syncResult = null;

        DB::transaction(function () use ($data, $sucursalActual, &$transferencia) {
            $settings = Setting::values();
            $unidadPeso = strtoupper($settings['unidad_peso'] ?? 'LB');
            $folio = Transferencia::generarFolio();

            $totalPeso = 0;
            $totalUnidades = 0;
            $costoTotal = 0;

            // Crear la transferencia
            $transferencia = Transferencia::create([
                'folio' => $folio,
                'sucursal_origen_id' => $sucursalActual->id,
                'sucursal_destino_id' => $data['sucursal_destino_id'],
                'user_id' => Auth::id(),
                'estado' => 'en_transito',
                'notas' => $data['notas'] ?? null,
                'fecha_envio' => now(),
            ]);

            // Procesar cada línea de detalle
            foreach ($data['detalles'] as $item) {
                $articulo = Articulo::findOrFail($item['articulo_id']);
                $cantidad = (float) $item['cantidad'];
                $costoUnitario = (float) $articulo->precio_compra;
                $subtotal = round($cantidad * $costoUnitario, 2);

                // Determinar unidad de medida
                $unidadMedida = $articulo->isUnidad() ? 'UND' : $unidadPeso;

                // Datos del lote (si aplica)
                $lote = null;
                $numeroLote = null;
                $fechaVencimiento = null;
                $compraDetalleId = $item['compra_detalle_id'] ?? null;

                if ($compraDetalleId) {
                    $compraDetalle = CompraDetalle::find($compraDetalleId);
                    if ($compraDetalle) {
                        $lote = $compraDetalle->lote;
                        $numeroLote = $compraDetalle->serie;
                        $fechaVencimiento = $compraDetalle->fecha_vencimiento;

                        // Descontar del lote específico
                        $compraDetalle->cantidad_peso = max(0, $compraDetalle->cantidad_peso - $cantidad);
                        $compraDetalle->save();
                    }
                }

                TransferenciaDetalle::create([
                    'transferencia_id' => $transferencia->id,
                    'articulo_id' => $articulo->id,
                    'codigo' => $articulo->codigo ?? $articulo->codigo_cliente,
                    'descripcion' => $articulo->descripcion,
                    'tipo_articulo' => $articulo->tipo_articulo ?? 'pesable',
                    'cantidad_enviada' => $cantidad,
                    'unidad_medida' => $unidadMedida,
                    'costo_unitario' => $costoUnitario,
                    'subtotal_costo' => $subtotal,
                    'lote' => $lote,
                    'numero_lote' => $numeroLote,
                    'fecha_vencimiento_lote' => $fechaVencimiento,
                    'compra_detalle_id' => $compraDetalleId,
                ]);

                // Descontar stock general del artículo
                $articulo->stock = max(0, $articulo->stock - $cantidad);
                if ($articulo->stock <= 0) {
                    $articulo->estado = 'sin_stock';
                }
                $articulo->save();

                // Acumular totales
                if ($articulo->isUnidad()) {
                    $totalUnidades += (int) $cantidad;
                } else {
                    $totalPeso += $cantidad;
                }
                $costoTotal += $subtotal;
            }

            // Actualizar totales en la transferencia
            $transferencia->update([
                'total_peso' => $totalPeso,
                'total_unidades' => $totalUnidades,
                'costo_total' => $costoTotal,
            ]);
        });

        // Intentar sincronizar con la nube
        $syncService = new TransferenciaSyncService();
        $syncResult = $syncService->enviarNube($transferencia);

        if (!$syncResult['success'] && ($syncResult['offline'] ?? false)) {
            // Sin internet: marcar como manual
            $transferencia->update(['tipo_sincronizacion' => 'manual_trn']);
        }

        return response()->json([
            'success' => true,
            'message' => "Transferencia {$transferencia->folio} registrada exitosamente.",
            'transferencia_id' => $transferencia->id,
            'folio' => $transferencia->folio,
            'sync' => $syncResult,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  CONSULTAR TRANSFERENCIAS ENTRANTES (API AJAX)
    // ─────────────────────────────────────────────────────────
    public function apiSyncNube(): JsonResponse
    {
        $sucursalActual = Sucursal::actual();
        if (!$sucursalActual) {
            return response()->json(['success' => false, 'error' => 'No hay sucursal configurada.', 'count' => 0]);
        }

        $syncService = new TransferenciaSyncService();
        $result = $syncService->consultarPendientesNube($sucursalActual->codigo);

        // También contar las transferencias locales ya importadas en tránsito
        $localPendientes = Transferencia::where('sucursal_destino_id', $sucursalActual->id)
            ->where('estado', 'en_transito')
            ->with(['sucursalOrigen', 'detalles', 'usuario'])
            ->orderBy('fecha_envio', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'cloud' => $result,
            'locales' => $localPendientes,
            'total_pendientes' => $localPendientes->count() + ($result['count'] ?? 0),
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  IMPORTAR TRANSFERENCIA DESDE LA NUBE (a BD local)
    // ─────────────────────────────────────────────────────────
    public function importarDesdeNube(Request $request): JsonResponse
    {
        $data = $request->validate([
            'folio' => ['required', 'string'],
            'payload' => ['required', 'array'],
        ]);

        // Verificar que no exista ya localmente
        if (Transferencia::where('folio', $data['folio'])->exists()) {
            return response()->json(['success' => false, 'error' => 'Esta transferencia ya fue importada previamente.']);
        }

        $cloudData = $data['payload'];
        $sucursalActual = Sucursal::actual();

        // Buscar o crear sucursal origen por código
        $sucursalOrigen = Sucursal::where('codigo', $cloudData['sucursal_origen'] ?? '')->first();
        if (!$sucursalOrigen) {
            $sucursalOrigen = Sucursal::create([
                'codigo' => $cloudData['sucursal_origen'] ?? 'UNKNOWN',
                'nombre' => 'Sucursal ' . ($cloudData['sucursal_origen'] ?? '?'),
                'activo' => true,
            ]);
        }

        DB::transaction(function () use ($cloudData, $sucursalOrigen, $sucursalActual, $data) {
            $transferencia = Transferencia::create([
                'folio' => $data['folio'],
                'sucursal_origen_id' => $sucursalOrigen->id,
                'sucursal_destino_id' => $sucursalActual->id,
                'estado' => 'en_transito',
                'tipo_sincronizacion' => 'cloud',
                'total_peso' => $cloudData['total_peso'] ?? 0,
                'total_unidades' => $cloudData['total_unidades'] ?? 0,
                'costo_total' => $cloudData['costo_total'] ?? 0,
                'notas' => $cloudData['notas'] ?? null,
                'fecha_envio' => $cloudData['fecha_envio'] ?? now(),
                'payload_json' => json_encode($cloudData, JSON_UNESCAPED_UNICODE),
            ]);

            foreach ($cloudData['payload'] ?? [] as $item) {
                TransferenciaDetalle::create([
                    'transferencia_id' => $transferencia->id,
                    'articulo_id' => $item['articulo_id'] ?? 0,
                    'codigo' => $item['codigo'] ?? null,
                    'descripcion' => $item['descripcion'] ?? 'Artículo transferido',
                    'tipo_articulo' => $item['tipo_articulo'] ?? 'pesable',
                    'cantidad_enviada' => $item['cantidad_enviada'] ?? 0,
                    'unidad_medida' => $item['unidad_medida'] ?? 'LB',
                    'costo_unitario' => $item['costo_unitario'] ?? 0,
                    'subtotal_costo' => $item['subtotal_costo'] ?? 0,
                    'lote' => $item['lote'] ?? null,
                    'numero_lote' => $item['numero_lote'] ?? null,
                    'fecha_vencimiento_lote' => $item['fecha_vencimiento_lote'] ?? null,
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => "Transferencia {$data['folio']} importada desde la nube."]);
    }

    // ─────────────────────────────────────────────────────────
    //  CONFIRMAR RECEPCIÓN (Sucursal Destino)
    // ─────────────────────────────────────────────────────────
    public function recibir(Request $request, Transferencia $transferencia): JsonResponse
    {
        if ($transferencia->estado !== 'en_transito') {
            return response()->json(['success' => false, 'error' => 'Esta transferencia ya no está en tránsito.'], 422);
        }

        DB::transaction(function () use ($transferencia, $request) {
            $transferencia->load('detalles');

            foreach ($transferencia->detalles as $detalle) {
                $cantidadRecibida = (float) ($request->input("cantidades.{$detalle->id}") ?? $detalle->cantidad_enviada);

                // Actualizar cantidad recibida
                $detalle->update(['cantidad_recibida' => $cantidadRecibida]);

                // Buscar el artículo local (por código o descripción)
                $articulo = Articulo::find($detalle->articulo_id);

                if (!$articulo && $detalle->codigo) {
                    $articulo = Articulo::where('codigo', $detalle->codigo)
                        ->orWhere('codigo_cliente', $detalle->codigo)
                        ->first();
                }

                if ($articulo) {
                    // Sumar stock al artículo local
                    $articulo->stock = $articulo->stock + $cantidadRecibida;
                    if ($articulo->estado === 'sin_stock' && $articulo->stock > 0) {
                        $articulo->estado = 'activo';
                    }
                    // Actualizar costo de compra si viene informado
                    if ($detalle->costo_unitario > 0) {
                        $articulo->precio_compra = $detalle->costo_unitario;
                    }
                    $articulo->save();
                }
            }

            $transferencia->update([
                'estado' => 'recibida',
                'user_recibe_id' => Auth::id(),
                'fecha_recepcion' => now(),
            ]);
        });

        // Notificar a la nube
        $syncService = new TransferenciaSyncService();
        $syncService->marcarRecibidaNube($transferencia->folio, Auth::user()->name ?? 'Receptor');

        return response()->json([
            'success' => true,
            'message' => "Transferencia {$transferencia->folio} recibida exitosamente. El inventario ha sido actualizado.",
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  CANCELAR TRANSFERENCIA
    // ─────────────────────────────────────────────────────────
    public function cancelar(Transferencia $transferencia): JsonResponse
    {
        if ($transferencia->estado !== 'en_transito') {
            return response()->json(['success' => false, 'error' => 'Solo se pueden cancelar transferencias en tránsito.'], 422);
        }

        DB::transaction(function () use ($transferencia) {
            $transferencia->load('detalles');

            // Revertir stock en la sucursal origen
            foreach ($transferencia->detalles as $detalle) {
                $articulo = Articulo::find($detalle->articulo_id);
                if ($articulo) {
                    $articulo->stock = $articulo->stock + (float) $detalle->cantidad_enviada;
                    if ($articulo->estado === 'sin_stock') {
                        $articulo->estado = 'activo';
                    }
                    $articulo->save();
                }

                // Revertir lote si aplica
                if ($detalle->compra_detalle_id) {
                    $compraDetalle = CompraDetalle::find($detalle->compra_detalle_id);
                    if ($compraDetalle) {
                        $compraDetalle->cantidad_peso = $compraDetalle->cantidad_peso + (float) $detalle->cantidad_enviada;
                        $compraDetalle->save();
                    }
                }
            }

            $transferencia->update(['estado' => 'cancelada']);
        });

        // Notificar cancelación a la nube
        $syncService = new TransferenciaSyncService();
        $syncService->cancelarNube($transferencia->folio);

        return response()->json([
            'success' => true,
            'message' => "Transferencia {$transferencia->folio} cancelada. El stock ha sido devuelto.",
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  DETALLE DE TRANSFERENCIA (Modal AJAX)
    // ─────────────────────────────────────────────────────────
    public function show(Transferencia $transferencia): JsonResponse
    {
        $transferencia->load([
            'sucursalOrigen', 'sucursalDestino',
            'usuario', 'usuarioRecibe', 'detalles',
        ]);

        return response()->json(['transferencia' => $transferencia]);
    }

    // ─────────────────────────────────────────────────────────
    //  DESCARGAR ARCHIVO .TRN (Respaldo Offline)
    // ─────────────────────────────────────────────────────────
    public function descargarTrn(Transferencia $transferencia)
    {
        $syncService = new TransferenciaSyncService();
        $contenido = $syncService->generarArchivoTrn($transferencia);

        return response($contenido, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$transferencia->folio}.trn\"",
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  IMPORTAR ARCHIVO .TRN (Recepción Manual Offline)
    // ─────────────────────────────────────────────────────────
    public function importarTrn(Request $request): JsonResponse
    {
        $request->validate([
            'archivo_trn' => ['required', 'file', 'max:2048'],
        ]);

        $contenido = file_get_contents($request->file('archivo_trn')->getRealPath());
        $syncService = new TransferenciaSyncService();
        $result = $syncService->procesarArchivoTrn($contenido);

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        // Importar la transferencia como si viniera de la nube
        return $this->importarDesdeNube(new Request([
            'folio' => $result['data']['folio'],
            'payload' => $result['data'],
        ]));
    }

    // ─────────────────────────────────────────────────────────
    //  TICKET TÉRMICO DE TRANSFERENCIA (80mm)
    // ─────────────────────────────────────────────────────────
    public function imprimirTicket(Transferencia $transferencia)
    {
        $transferencia->load(['sucursalOrigen', 'sucursalDestino', 'usuario', 'detalles']);
        $settings = Setting::values();

        return view('transferencias.ticket', compact('transferencia', 'settings'));
    }

    // ─────────────────────────────────────────────────────────
    //  PROBAR CONEXIÓN CON HOSTINGER (AJAX)
    // ─────────────────────────────────────────────────────────
    public function testConexionCloud(): JsonResponse
    {
        $syncService = new TransferenciaSyncService();
        return response()->json($syncService->testConexion());
    }

    // ─────────────────────────────────────────────────────────
    //  CRUD SUCURSALES (Pestaña 4)
    // ─────────────────────────────────────────────────────────
    public function storeSucursal(Request $request): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:20', 'unique:sucursales,codigo'],
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'es_sucursal_actual' => ['nullable', 'boolean'],
        ]);

        $sucursal = Sucursal::create($data);

        if (!empty($data['es_sucursal_actual'])) {
            $sucursal->marcarComoActual();
        }

        return response()->json([
            'success' => true,
            'message' => "Sucursal {$sucursal->nombre} creada exitosamente.",
            'sucursal' => $sucursal,
        ]);
    }

    public function updateSucursal(Request $request, Sucursal $sucursal): JsonResponse
    {
        $data = $request->validate([
            'codigo' => ['required', 'string', 'max:20', 'unique:sucursales,codigo,' . $sucursal->id],
            'nombre' => ['required', 'string', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'es_sucursal_actual' => ['nullable', 'boolean'],
        ]);

        $sucursal->update($data);

        if (!empty($data['es_sucursal_actual'])) {
            $sucursal->marcarComoActual();
        }

        return response()->json([
            'success' => true,
            'message' => "Sucursal {$sucursal->nombre} actualizada.",
            'sucursal' => $sucursal->fresh(),
        ]);
    }

    public function destroySucursal(Sucursal $sucursal): JsonResponse
    {
        if ($sucursal->es_sucursal_actual) {
            return response()->json(['success' => false, 'error' => 'No puede eliminar la sucursal activa.'], 422);
        }

        $envios = Transferencia::where('sucursal_origen_id', $sucursal->id)->orWhere('sucursal_destino_id', $sucursal->id)->count();
        if ($envios > 0) {
            $sucursal->update(['activo' => false]);
            return response()->json(['success' => true, 'message' => 'La sucursal fue desactivada porque tiene transferencias asociadas.']);
        }

        $sucursal->delete();
        return response()->json(['success' => true, 'message' => 'Sucursal eliminada.']);
    }

    public function marcarSucursalActual(Sucursal $sucursal): JsonResponse
    {
        $sucursal->marcarComoActual();
        return response()->json(['success' => true, 'message' => "Esta sucursal ahora es la sucursal activa: {$sucursal->nombre}"]);
    }

    // ─────────────────────────────────────────────────────────
    //  GUARDAR CONFIGURACIÓN CLOUD (AJAX)
    // ─────────────────────────────────────────────────────────
    public function guardarConfigCloud(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cloud_sync_endpoint' => ['nullable', 'string', 'max:500'],
            'cloud_sync_token' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::setValue('cloud_sync_endpoint', $data['cloud_sync_endpoint'] ?? '');
        Setting::setValue('cloud_sync_token', $data['cloud_sync_token'] ?? '');

        return response()->json(['success' => true, 'message' => 'Configuración cloud guardada.']);
    }
}
