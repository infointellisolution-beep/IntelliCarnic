<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Proveedor;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompraController extends Controller
{
    public function historial(Request $request): View
    {
        $query = Compra::with(['proveedor', 'user', 'detalles.articulo'])
            ->orderBy('fecha_compra', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('numero_factura', 'like', "%{$search}%")
                  ->orWhere('proveedor_nombre', 'like', "%{$search}%");
            });
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_compra', '>=', $request->input('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_compra', '<=', $request->input('fecha_hasta'));
        }

        $compras = $query->paginate(15)->withQueryString();

        return view('compras.index', [
            'compras' => $compras,
            'settings' => Setting::values(),
        ]);
    }

    public function create(): View
    {
        $articulos = Articulo::with('familia')
            ->where('estado', '!=', 'inactivo')
            ->orderBy('descripcion', 'asc')
            ->get();

        $proveedores = Proveedor::where('estado', 'activo')->orderBy('nombre', 'asc')->get();

        $nextId = (Compra::max('id') ?? 0) + 1;
        $nextInvoiceNumber = 'FAC-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('compras.create', [
            'articulos' => $articulos,
            'proveedores' => $proveedores,
            'settings' => Setting::values(),
            'nextInvoiceNumber' => $nextInvoiceNumber,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'numero_factura' => ['nullable', 'string', 'max:100'],
            'proveedor_nombre' => ['required', 'string', 'max:255'],
            'proveedor_id' => ['nullable', 'exists:proveedores,id'],
            'fecha_compra' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.articulo_id' => ['required', 'exists:articulos,id'],
            'detalles.*.codigo_escaneado' => ['nullable', 'string', 'max:255'],
            'detalles.*.lote' => ['nullable', 'string', 'max:100'],
            'detalles.*.serie' => ['nullable', 'string', 'max:100'],
            'detalles.*.fecha_vencimiento' => ['nullable', 'date'],
            'detalles.*.cantidad_peso' => ['required', 'numeric', 'min:0.001'],
            'detalles.*.costo_unitario' => ['required', 'numeric', 'min:0'],
        ]);

        $compra = null;
        DB::transaction(function () use ($data, &$compra) {
            $subtotal = 0;

            foreach ($data['detalles'] as $item) {
                $lineSubtotal = round((float)$item['cantidad_peso'] * (float)$item['costo_unitario'], 2);
                $subtotal += $lineSubtotal;
            }

            $compra = Compra::create([
                'numero_factura' => $data['numero_factura'] ?? null,
                'proveedor_id' => $data['proveedor_id'] ?? null,
                'proveedor_nombre' => $data['proveedor_nombre'],
                'fecha_compra' => $data['fecha_compra'],
                'subtotal' => $subtotal,
                'iva' => 0, // Las compras se registran al costo neto
                'total' => $subtotal,
                'observaciones' => $data['observaciones'] ?? null,
                'user_id' => Auth::id(),
            ]);

            $systemUnit = strtolower(Setting::get('unidad_peso', 'lb'));

            foreach ($data['detalles'] as $item) {
                $lineSubtotal = round((float)$item['cantidad_peso'] * (float)$item['costo_unitario'], 2);

                $rawCode = $item['codigo_escaneado'] ?? null;
                if ($rawCode) {
                    $rawCode = $this->rewriteBarcodeWeight($rawCode, (float)$item['cantidad_peso'], $systemUnit);
                }

                CompraDetalle::create([
                    'compra_id' => $compra->id,
                    'articulo_id' => $item['articulo_id'],
                    'codigo_escaneado' => $rawCode,
                    'lote' => $item['lote'] ?? null,
                    'serie' => $item['serie'] ?? null,
                    'fecha_vencimiento' => $item['fecha_vencimiento'] ?? null,
                    'cantidad_peso' => $item['cantidad_peso'],
                    'costo_unitario' => $item['costo_unitario'],
                    'subtotal' => $lineSubtotal,
                ]);

                // Actualizar Stock y Costo en el artículo
                $articulo = Articulo::find($item['articulo_id']);
                if ($articulo) {
                    $articulo->stock = $articulo->stock + (float)$item['cantidad_peso'];
                    $articulo->precio_compra = (float)$item['costo_unitario'];
                    
                    // Si el estado estaba sin_stock y ahora hay stock, reactivarlo
                    if ($articulo->estado === 'sin_stock' && $articulo->stock > 0) {
                        $articulo->estado = 'activo';
                    }
                    
                    $articulo->save();
                }
            }

            // Registrar movimiento de salida en Caja si hay una caja de turno abierta
            $cajaActiva = \App\Models\CajaSesion::query()->where('estado', 'abierta')->first();
            if ($cajaActiva && $subtotal > 0) {
                \App\Models\CajaMovimiento::create([
                    'caja_sesion_id' => $cajaActiva->id,
                    'user_id' => Auth::id() ?? $compra->user_id,
                    'tipo' => 'salida',
                    'monto' => $subtotal,
                    'concepto' => 'Compra de Mercancía/Carne - ' . $compra->proveedor_nombre . ($compra->numero_factura ? ' (Fact: ' . $compra->numero_factura . ')' : ''),
                    'observaciones' => 'Registro automático desde módulo de compras #' . $compra->id,
                ]);

                $cajaActiva->recargarTotales();
            }
        });

        if ($request->wantsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Compra registrada exitosamente.',
                'compra' => $compra,
            ]);
        }

        return redirect()
            ->route('compras.index')
            ->with('status', 'Compra registrada exitosamente. El inventario y costos han sido actualizados.');
    }

    public function show(Compra $compra)
    {
        $compra->load(['proveedor', 'user', 'detalles.articulo']);

        return response()->json([
            'compra' => $compra,
        ]);
    }

    public function storeProveedor(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'identificacion' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'correo' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
        ]);

        $proveedor = Proveedor::create($data);

        return response()->json([
            'success' => true,
            'proveedor' => $proveedor,
        ]);
    }

    private function rewriteBarcodeWeight(?string $code, float $weightVal, string $unit): ?string
    {
        if (!$code) return $code;
        $clean = preg_replace('/[()\-\s]/', '', trim($code));

        if (!preg_match('/(320[0-5]|310[0-5])(\d{6})/', $clean, $m)) {
            return $clean;
        }

        $ai = $m[1];
        $decimals = (int) substr($ai, 3, 1);
        $isKgInBarcode = str_starts_with($ai, '310');

        $targetWeight = $weightVal;
        if ($isKgInBarcode && in_array($unit, ['lb', 'lbs'])) {
            $targetWeight = $weightVal / 2.20462;
        } elseif (!$isKgInBarcode && $unit === 'kg') {
            $targetWeight = $weightVal * 2.20462;
        }

        $rawNum = (int) round($targetWeight * pow(10, $decimals));
        $formattedStr = str_pad((string) $rawNum, 6, '0', STR_PAD_LEFT);
        $formattedStr = substr($formattedStr, -6);

        return str_replace($m[0], $ai . $formattedStr, $clean);
    }
}
