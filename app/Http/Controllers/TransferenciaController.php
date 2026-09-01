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
    //  VISTA PRINCIPAL (Enviar Mercancía / Recepciones / Buzón Cloud / Historial)
    // ─────────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $tab = $request->get('tab', 'enviar');
        if (!in_array($tab, ['enviar', 'recibir', 'nube', 'historial'])) {
            $tab = 'enviar';
        }
        $settings = Setting::values();
        $modoInventario = $settings['modo_inventario'] ?? 'dinamico';
        $modoTransferencias = $settings['modo_transferencias'] ?? 'cloud';

        // Si está en modo archivo y solicitaron la pestaña nube, redirigir a historial
        if ($modoTransferencias === 'archivo' && $tab === 'nube') {
            $tab = 'historial';
        }

        $sucursalActual = Sucursal::actual();
        $sucursales = Sucursal::orderBy('nombre')->get();
        $sucursalesDestino = $sucursalActual ? Sucursal::destinos() : collect();

        // Lotes activos si el modo es dinámico (solo aquellos con identificador de lote o serie real)
        $lotesActivos = collect();
        if ($modoInventario === 'dinamico') {
            $lotesActivos = CompraDetalle::where('cantidad_peso', '>', 0)
                ->where(function ($q) {
                    $q->where(function ($q2) {
                        $q2->whereNotNull('lote')->where('lote', '!=', '');
                    })->orWhere(function ($q2) {
                        $q2->whereNotNull('serie')->where('serie', '!=', '');
                    });
                })
                ->orderBy('fecha_vencimiento', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('articulo_id');
        }

        // Artículos con stock > 0 para el formulario de envío
        $articulos = Articulo::with('familia')
            ->where('estado', '!=', 'inactivo')
            ->where('stock', '>', 0)
            ->orderBy('descripcion')
            ->get()
            ->map(function ($a) use ($modoInventario, $lotesActivos) {
                $misLotes = [];
                // Solo si el modo es dinámico, el artículo NO es de tipo unidad simple y tiene lotes identificados
                if ($modoInventario === 'dinamico' && $a->tipo_articulo !== 'unidad' && $lotesActivos->has($a->id)) {
                    $misLotes = $lotesActivos->get($a->id)->map(function ($l) {
                        return [
                            'id' => $l->id,
                            'lote' => (string) ($l->lote ?? ''),
                            'serie' => (string) ($l->serie ?? ''),
                            'codigo_escaneado' => (string) ($l->codigo_escaneado ?? ''),
                            'fecha_vencimiento' => $l->fecha_vencimiento ? $l->fecha_vencimiento->format('Y-m-d') : null,
                            'fecha_vencimiento_format' => $l->fecha_vencimiento ? $l->fecha_vencimiento->format('d/m/Y') : 'S/V',
                            'cantidad_peso' => (float) $l->cantidad_peso,
                            'costo_unitario' => (float) $l->costo_unitario,
                        ];
                    })->values()->all();
                }

                return [
                    'id' => $a->id,
                    'codigo' => (string) ($a->codigo ?? ''),
                    'codigo_cliente' => (string) ($a->codigo_cliente ?? ''),
                    'item' => (string) ($a->item ?? ''),
                    'descripcion' => (string) ($a->descripcion ?? ''),
                    'tipo_articulo' => $a->tipo_articulo ?? 'pesable',
                    'precio_compra' => (float) ($a->precio_compra ?: $a->precio_sin_iva ?: 0),
                    'stock' => (float) $a->stock,
                    'familia_nombre' => (string) ($a->familia?->nombre ?? ''),
                    'lotes' => $misLotes,
                ];
            });

        // Conteo de pendientes entrantes locales
        $pendientesCount = 0;
        if ($sucursalActual) {
            $pendientesCount = Transferencia::where('sucursal_destino_id', $sucursalActual->id)
                ->where('estado', 'en_transito')
                ->count();
        }

        // Datos para la pestaña de Buzón en la Nube
        $transferenciasNube = [];
        $cloudStatus = null;
        $totalNubeCount = 0;
        
        $syncService = new TransferenciaSyncService();
        if ($tab === 'nube' && $modoTransferencias === 'cloud') {
            $cloudStatus = $syncService->testConexion();
            $respNube = $syncService->consultarTodasEnNube();
            $transferenciasNube = $respNube['transferencias'] ?? [];
            $totalNubeCount = $respNube['count'] ?? 0;
        }

        // Historial general de transferencias (todas las enviadas / recibidas)
        $transferenciasHistorial = Transferencia::with(['sucursalOrigen', 'sucursalDestino', 'usuario', 'usuarioRecibe', 'detalles'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('transferencias.index', compact(
            'tab',
            'settings',
            'modoInventario',
            'modoTransferencias',
            'sucursalActual',
            'sucursales',
            'sucursalesDestino',
            'articulos',
            'pendientesCount',
            'transferenciasNube',
            'cloudStatus',
            'totalNubeCount',
            'transferenciasHistorial'
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

        try {
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
                            if ((float)$compraDetalle->costo_unitario > 0) {
                                $costoUnitario = (float) $compraDetalle->costo_unitario;
                                $subtotal = round($cantidad * $costoUnitario, 2);
                            }

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

            // Determinar comportamiento según el modo de transferencias configurado
            $modoTransferencias = Setting::getValue('modo_transferencias', 'cloud');
            $syncResult = ['success' => true, 'modo' => $modoTransferencias];

            if ($modoTransferencias === 'archivo') {
                $transferencia->update([
                    'tipo_sincronizacion' => 'manual_trn',
                    'payload_json' => json_encode($transferencia->buildPayload(), JSON_UNESCAPED_UNICODE),
                ]);
                $syncResult = [
                    'success' => true,
                    'message' => 'Transferencia registrada en modo archivo (.TRN).',
                    'offline' => true,
                ];
            } else {
                // Intentar sincronizar con la nube
                $syncService = new TransferenciaSyncService();
                $syncResult = $syncService->enviarNube($transferencia);

                if (!$syncResult['success'] && ($syncResult['offline'] ?? false)) {
                    // Sin internet o error de conexión: marcar como manual
                    $transferencia->update([
                        'tipo_sincronizacion' => 'manual_trn',
                        'payload_json' => json_encode($transferencia->buildPayload(), JSON_UNESCAPED_UNICODE),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Transferencia {$transferencia->folio} registrada exitosamente.",
                'transferencia_id' => $transferencia->id,
                'folio' => $transferencia->folio,
                'modo_transferencias' => $modoTransferencias,
                'download_url' => route('transferencias.descargar-trn', $transferencia->id),
                'sync' => $syncResult,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error al registrar transferencia: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar la transferencia: ' . $e->getMessage(),
            ], 500);
        }
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

    /**
     * API: Consultar todas las transferencias alojadas en el buzón cloud (para todas las sucursales).
     */
    public function apiConsultarTodasNube(): JsonResponse
    {
        $syncService = new TransferenciaSyncService();
        $testConexion = $syncService->testConexion();
        $todas = $syncService->consultarTodasEnNube();

        return response()->json([
            'success' => true,
            'cloud_status' => $testConexion,
            'count' => $todas['count'] ?? 0,
            'transferencias' => $todas['transferencias'] ?? [],
        ]);
    }

    // ─────────────────────────────────────────────────────────
    //  IMPORTAR TRANSFERENCIA DESDE LA NUBE (a BD local)
    // ─────────────────────────────────────────────────────────
    public function importarDesdeNube(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'folio' => ['required', 'string'],
                'payload' => ['required', 'array'],
            ]);

            // Verificar si ya existe localmente
            $existente = Transferencia::where('folio', $data['folio'])->first();
            if ($existente) {
                return response()->json([
                    'success' => true,
                    'message' => 'Esta transferencia ya fue descargada localmente.',
                    'transferencia_id' => $existente->id,
                    'estado' => $existente->estado,
                ]);
            }

            $cloudData = $data['payload'];
            $sucursalActual = Sucursal::actual();

            // Buscar o crear sucursal origen por código
            $codigoOrigen = $cloudData['sucursal_origen'] ?? ($cloudData['payload']['sucursal_origen'] ?? 'ORIGEN');
            $sucursalOrigen = Sucursal::where('codigo', $codigoOrigen)->first();
            if (!$sucursalOrigen) {
                $sucursalOrigen = Sucursal::create([
                    'codigo' => $codigoOrigen,
                    'nombre' => 'Sucursal ' . $codigoOrigen,
                    'activo' => true,
                ]);
            }

            // Buscar o crear sucursal destino por código
            $codigoDestino = $cloudData['sucursal_destino'] ?? ($cloudData['payload']['sucursal_destino'] ?? 'DESTINO');
            $sucursalDestino = $sucursalActual ?: Sucursal::where('codigo', $codigoDestino)->first();
            if (!$sucursalDestino) {
                $sucursalDestino = Sucursal::create([
                    'codigo' => $codigoDestino,
                    'nombre' => 'Sucursal ' . $codigoDestino,
                    'activo' => true,
                ]);
            }

            $transferencia = null;

            DB::transaction(function () use ($cloudData, $sucursalOrigen, $sucursalDestino, $data, &$transferencia) {
                $transferencia = Transferencia::create([
                    'folio' => $data['folio'],
                    'sucursal_origen_id' => $sucursalOrigen->id,
                    'sucursal_destino_id' => $sucursalDestino->id,
                    'estado' => 'en_transito',
                    'tipo_sincronizacion' => 'cloud',
                    'total_peso' => (float) ($cloudData['total_peso'] ?? ($cloudData['payload']['total_peso'] ?? 0)),
                    'total_unidades' => (int) ($cloudData['total_unidades'] ?? ($cloudData['payload']['total_unidades'] ?? 0)),
                    'costo_total' => (float) ($cloudData['costo_total'] ?? ($cloudData['payload']['costo_total'] ?? 0)),
                    'notas' => $cloudData['notas'] ?? ($cloudData['payload']['notas'] ?? null),
                    'fecha_envio' => $cloudData['fecha_envio'] ?? ($cloudData['payload']['fecha_envio'] ?? now()),
                    'payload_json' => json_encode($cloudData, JSON_UNESCAPED_UNICODE),
                ]);

                // Extraer items independientemente de la estructura de envoltura
                $items = $cloudData['items'] 
                    ?? ($cloudData['payload']['items'] 
                    ?? (is_array($cloudData['payload'] ?? null) && isset($cloudData['payload'][0]) ? $cloudData['payload'] : []));

                foreach ($items as $item) {
                    $codigoItem = $item['codigo'] ?? null;
                    $codigoClienteItem = !empty($item['codigo_cliente']) ? (string)$item['codigo_cliente'] : null;
                    $descripcionItem = $item['descripcion'] ?? 'Artículo transferido';
                    
                    // Buscar artículo localmente por código cliente, código, descripción o ID
                    $localArticulo = null;
                    if ($codigoClienteItem) {
                        $localArticulo = Articulo::where('codigo_cliente', $codigoClienteItem)->first();
                    }
                    if (!$localArticulo && $codigoItem) {
                        $localArticulo = Articulo::where('codigo', $codigoItem)
                            ->orWhere('codigo_cliente', $codigoItem)
                            ->first();
                    }
                    if (!$localArticulo && $descripcionItem) {
                        $localArticulo = Articulo::where('descripcion', $descripcionItem)->first();
                    }
                    if (!$localArticulo && !empty($item['articulo_id'])) {
                        $localArticulo = Articulo::find($item['articulo_id']);
                    }

                    $costo = (float) ($item['precio_compra'] ?? ($item['costo_unitario'] ?? ($item['costo'] ?? 0)));
                    $precioSinIva = isset($item['precio_sin_iva']) && (float)$item['precio_sin_iva'] > 0 
                        ? (float)$item['precio_sin_iva'] 
                        : (isset($item['pvp']) && (float)$item['pvp'] > 0 ? (float)$item['pvp'] : round($costo > 0 ? $costo * 1.3 : 1.0, 2));
                    $pvp = isset($item['pvp']) && (float)$item['pvp'] > 0 
                        ? (float)$item['pvp'] 
                        : (isset($item['precio_venta']) && (float)$item['precio_venta'] > 0 ? (float)$item['precio_venta'] : $precioSinIva);

                    $familiaId = null;
                    if (!empty($item['familia_nombre'])) {
                        $fam = \App\Models\Familia::firstOrCreate(['nombre' => $item['familia_nombre']]);
                        $familiaId = $fam->id;
                    }

                    // Si el artículo aún no existe en esta sucursal, crearlo automáticamente con toda su ficha técnica
                    if (!$localArticulo) {
                        $localArticulo = Articulo::create([
                            'codigo' => $codigoItem ?: ('ART-' . strtoupper(substr(uniqid(), -6))),
                            'codigo_cliente' => $codigoClienteItem ?: ($codigoItem ?: ''),
                            'item' => $item['item'] ?? null,
                            'familia_id' => $familiaId,
                            'descripcion' => $descripcionItem,
                            'tipo_articulo' => $item['tipo_articulo'] ?? 'pesable',
                            'precio_compra' => $costo,
                            'precio_sin_iva' => $precioSinIva,
                            'aplica_iva' => (bool) ($item['aplica_iva'] ?? false),
                            'iva' => (float) ($item['iva'] ?? 0),
                            'pvp' => $pvp,
                            'precios_adicionales' => $item['precios_adicionales'] ?? null,
                            'stock' => 0,
                            'stock_minimo' => 0,
                            'estado' => 'activo',
                        ]);
                    } else {
                        // Enriquecer artículo existente si venía con datos parciales
                        $dirty = false;
                        if ($codigoClienteItem && ($localArticulo->codigo_cliente === $localArticulo->codigo || empty($localArticulo->codigo_cliente))) {
                            $localArticulo->codigo_cliente = $codigoClienteItem;
                            $dirty = true;
                        }
                        if ($familiaId && !$localArticulo->familia_id) {
                            $localArticulo->familia_id = $familiaId;
                            $dirty = true;
                        }
                        if ($pvp > 0 && ($localArticulo->pvp == 0 || $localArticulo->pvp == round($localArticulo->precio_compra * 1.3, 2))) {
                            $localArticulo->pvp = $pvp;
                            $localArticulo->precio_sin_iva = $precioSinIva;
                            $dirty = true;
                        }
                        if (!empty($item['precios_adicionales'])) {
                            $localArticulo->precios_adicionales = $item['precios_adicionales'];
                            $dirty = true;
                        }
                        if ($dirty) {
                            $localArticulo->save();
                        }
                    }

                    $fechaVenc = $item['fecha_vencimiento_lote'] ?? ($item['fecha_vencimiento'] ?? null);
                    if ($fechaVenc && !strtotime((string)$fechaVenc)) {
                        $fechaVenc = null;
                    }

                    TransferenciaDetalle::create([
                        'transferencia_id' => $transferencia->id,
                        'articulo_id' => $localArticulo->id,
                        'codigo' => $codigoItem,
                        'descripcion' => $descripcionItem,
                        'tipo_articulo' => $item['tipo_articulo'] ?? 'pesable',
                        'cantidad_enviada' => (float) ($item['cantidad_enviada'] ?? ($item['cantidad'] ?? 0)),
                        'unidad_medida' => $item['unidad_medida'] ?? 'LB',
                        'costo_unitario' => (float) ($item['costo_unitario'] ?? ($item['costo'] ?? 0)),
                        'subtotal_costo' => (float) ($item['subtotal_costo'] ?? 0),
                        'lote' => $item['lote'] ?? null,
                        'numero_lote' => $item['numero_lote'] ?? ($item['serie'] ?? null),
                        'fecha_vencimiento_lote' => $fechaVenc,
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Transferencia {$data['folio']} descargada desde la nube.",
                'transferencia_id' => $transferencia ? $transferencia->id : null,
                'estado' => 'en_transito',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error en importarDesdeNube: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'error' => 'Error al descargar transferencia: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────
    //  CONFIRMAR RECEPCIÓN (Sucursal Destino)
    // ─────────────────────────────────────────────────────────
    public function recibir(Request $request, Transferencia $transferencia): JsonResponse
    {
        if ($transferencia->estado !== 'en_transito') {
            return response()->json(['success' => false, 'error' => 'Esta transferencia ya no está en tránsito.'], 422);
        }

        try {
            DB::transaction(function () use ($transferencia, $request) {
                $transferencia->load('detalles', 'sucursalOrigen');
                $settings = Setting::values();
                $modoInventario = $settings['modo_inventario'] ?? 'dinamico';

                $compraTransferencia = null;
                if ($modoInventario === 'dinamico') {
                    $compraTransferencia = \App\Models\Compra::firstOrCreate(
                        ['numero_factura' => 'TRN-' . $transferencia->folio],
                        [
                            'proveedor_id' => null,
                            'proveedor_nombre' => 'Transferencia: ' . ($transferencia->sucursalOrigen->nombre ?? 'Sucursal'),
                            'fecha_compra' => now(),
                            'subtotal' => $transferencia->costo_total,
                            'iva' => 0,
                            'total' => $transferencia->costo_total,
                            'observaciones' => 'Entrada por Transferencia desde ' . ($transferencia->sucursalOrigen->nombre ?? 'Sucursal'),
                            'user_id' => Auth::id(),
                        ]
                    );
                }

                foreach ($transferencia->detalles as $detalle) {
                    $cantidadRecibida = (float) ($request->input("cantidades.{$detalle->id}") ?? $detalle->cantidad_enviada);

                    // Actualizar cantidad recibida
                    $detalle->update(['cantidad_recibida' => $cantidadRecibida]);

                    // Extraer metadata del payload si está presente
                    $itemMeta = null;
                    if (!empty($transferencia->payload_json)) {
                        $payloadObj = json_decode($transferencia->payload_json, true);
                        $itemsList = $payloadObj['items'] ?? ($payloadObj['payload']['items'] ?? ($payloadObj['payload'] ?? []));
                        if (is_array($itemsList)) {
                            foreach ($itemsList as $it) {
                                if (($it['codigo'] ?? '') == $detalle->codigo || ($it['descripcion'] ?? '') == $detalle->descripcion) {
                                    $itemMeta = $it;
                                    break;
                                }
                            }
                        }
                    }

                    // Buscar el artículo local (por ID, código cliente, código o descripción)
                    $articulo = Articulo::find($detalle->articulo_id);

                    if (!$articulo && !empty($itemMeta['codigo_cliente'])) {
                        $articulo = Articulo::where('codigo_cliente', $itemMeta['codigo_cliente'])->first();
                    }
                    if (!$articulo && $detalle->codigo) {
                        $articulo = Articulo::where('codigo', $detalle->codigo)
                            ->orWhere('codigo_cliente', $detalle->codigo)
                            ->first();
                    }
                    if (!$articulo && $detalle->descripcion) {
                        $articulo = Articulo::where('descripcion', $detalle->descripcion)->first();
                    }

                    $costoDet = (float) ($itemMeta['precio_compra'] ?? ($detalle->costo_unitario ?? 0));
                    $precioSinIvaDet = isset($itemMeta['precio_sin_iva']) && (float)$itemMeta['precio_sin_iva'] > 0 
                        ? (float)$itemMeta['precio_sin_iva'] 
                        : (isset($itemMeta['pvp']) && (float)$itemMeta['pvp'] > 0 ? (float)$itemMeta['pvp'] : round($costoDet > 0 ? $costoDet * 1.3 : 1.0, 2));
                    $pvpDet = isset($itemMeta['pvp']) && (float)$itemMeta['pvp'] > 0 
                        ? (float)$itemMeta['pvp'] 
                        : (isset($itemMeta['precio_venta']) && (float)$itemMeta['precio_venta'] > 0 ? (float)$itemMeta['precio_venta'] : $precioSinIvaDet);

                    $familiaIdDet = null;
                    if (!empty($itemMeta['familia_nombre'])) {
                        $fam = \App\Models\Familia::firstOrCreate(['nombre' => $itemMeta['familia_nombre']]);
                        $familiaIdDet = $fam->id;
                    }

                    if (!$articulo) {
                        $articulo = Articulo::create([
                            'codigo' => $detalle->codigo ?: ('ART-' . strtoupper(substr(uniqid(), -6))),
                            'codigo_cliente' => !empty($itemMeta['codigo_cliente']) ? $itemMeta['codigo_cliente'] : ($detalle->codigo ?: ''),
                            'item' => $itemMeta['item'] ?? null,
                            'familia_id' => $familiaIdDet,
                            'descripcion' => $detalle->descripcion ?: 'Artículo transferido',
                            'tipo_articulo' => $detalle->tipo_articulo ?? 'pesable',
                            'precio_compra' => $costoDet,
                            'precio_sin_iva' => $precioSinIvaDet,
                            'aplica_iva' => (bool) ($itemMeta['aplica_iva'] ?? false),
                            'iva' => (float) ($itemMeta['iva'] ?? 0),
                            'pvp' => $pvpDet,
                            'precios_adicionales' => $itemMeta['precios_adicionales'] ?? null,
                            'stock' => 0,
                            'stock_minimo' => 0,
                            'estado' => 'activo',
                        ]);
                    } else {
                        // Enriquecer artículo si faltaban datos o tenía valores por defecto
                        $dirty = false;
                        if (!empty($itemMeta['codigo_cliente']) && ($articulo->codigo_cliente === $articulo->codigo || empty($articulo->codigo_cliente))) {
                            $articulo->codigo_cliente = $itemMeta['codigo_cliente'];
                            $dirty = true;
                        }
                        if ($familiaIdDet && !$articulo->familia_id) {
                            $articulo->familia_id = $familiaIdDet;
                            $dirty = true;
                        }
                        if ($pvpDet > 0 && ($articulo->pvp == 0 || $articulo->pvp == round($articulo->precio_compra * 1.3, 2))) {
                            $articulo->pvp = $pvpDet;
                            $articulo->precio_sin_iva = $precioSinIvaDet;
                            $dirty = true;
                        }
                        if (!empty($itemMeta['precios_adicionales'])) {
                            $articulo->precios_adicionales = $itemMeta['precios_adicionales'];
                            $dirty = true;
                        }
                        if ($dirty) {
                            $articulo->save();
                        }
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

                        // Si el modo de inventario local es dinámico, registrar o actualizar el lote recibido
                        if ($modoInventario === 'dinamico' && $compraTransferencia) {
                            // Buscar si ya existe el lote exacto
                            $loteExistente = CompraDetalle::where('articulo_id', $articulo->id)
                                ->where('compra_id', $compraTransferencia->id)
                                ->where(function($q) use ($detalle) {
                                    if ($detalle->lote) {
                                        $q->where('lote', $detalle->lote);
                                    }
                                    if ($detalle->numero_lote) {
                                        $q->where('serie', $detalle->numero_lote);
                                    }
                                })
                                ->first();

                            if ($loteExistente) {
                                $loteExistente->cantidad_peso = $loteExistente->cantidad_peso + $cantidadRecibida;
                                $loteExistente->save();
                            } else {
                                // Extraer el código de escaneo original del payload si está presente
                                $codigoParaLote = $detalle->codigo;
                                if (!empty($transferencia->payload_json)) {
                                    $payloadObj = json_decode($transferencia->payload_json, true);
                                    $itemsList = $payloadObj['items'] ?? ($payloadObj['payload']['items'] ?? ($payloadObj['payload'] ?? []));
                                    if (is_array($itemsList)) {
                                        foreach ($itemsList as $it) {
                                            if (($it['lote'] ?? '') == $detalle->lote && ($it['numero_lote'] ?? '') == $detalle->numero_lote && !empty($it['codigo_escaneado'])) {
                                                $codigoParaLote = $it['codigo_escaneado'];
                                                break;
                                            }
                                        }
                                    }
                                }

                                CompraDetalle::create([
                                    'compra_id' => $compraTransferencia->id,
                                    'articulo_id' => $articulo->id,
                                    'codigo_escaneado' => $codigoParaLote ?: $detalle->codigo,
                                    'lote' => $detalle->lote,
                                    'serie' => $detalle->numero_lote,
                                    'fecha_vencimiento' => $detalle->fecha_vencimiento_lote,
                                    'cantidad_peso' => $cantidadRecibida,
                                    'costo_unitario' => $detalle->costo_unitario,
                                    'subtotal' => round($cantidadRecibida * $detalle->costo_unitario, 2),
                                ]);
                            }
                        }
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
            $syncService->marcarRecibidaNube($transferencia->folio, Auth::user()->name ?? 'Usuario');

            return response()->json([
                'success' => true,
                'message' => "Transferencia {$transferencia->folio} recibida exitosamente. El inventario ha sido actualizado.",
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error en recibir transferencia: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al recibir transferencia: ' . $e->getMessage(),
            ], 500);
        }
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
    public function testConexionCloud(Request $request): JsonResponse
    {
        $endpoint = $request->query('endpoint') ?: $request->input('endpoint');
        $token = $request->query('token') ?: $request->input('token');

        $syncService = new TransferenciaSyncService($endpoint, $token);
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
    //  GUARDAR CONFIGURACIÓN DE TRANSFERENCIAS (CLOUD O ARCHIVO) (AJAX)
    // ─────────────────────────────────────────────────────────
    public function guardarConfigCloud(Request $request): JsonResponse
    {
        $data = $request->validate([
            'modo_transferencias' => ['nullable', 'string', 'in:cloud,archivo'],
            'cloud_sync_endpoint' => ['nullable', 'string', 'max:500'],
            'cloud_sync_token' => ['nullable', 'string', 'max:500'],
        ]);

        if (isset($data['modo_transferencias'])) {
            Setting::setValue('modo_transferencias', $data['modo_transferencias']);
        }
        Setting::setValue('cloud_sync_endpoint', $data['cloud_sync_endpoint'] ?? '');
        Setting::setValue('cloud_sync_token', $data['cloud_sync_token'] ?? '');

        return response()->json([
            'success' => true,
            'message' => 'Configuración de transferencias guardada exitosamente.',
            'modo_transferencias' => $data['modo_transferencias'] ?? 'cloud',
        ]);
    }
}
