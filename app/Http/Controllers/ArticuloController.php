<?php

namespace App\Http\Controllers;

use App\Models\AjusteInventario;
use App\Models\Articulo;
use App\Models\Familia;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticuloController extends Controller
{
    public function index(): View
    {
        $search = request()->string('search')->trim()->toString();
        $settings = Setting::values();
        $usarImpuestos = (bool) ((int) ($settings['usar_impuestos'] ?? 1));
        $ivaGlobalEnabled = (bool) ((int) ($settings['iva_global_enabled'] ?? 1));
        $ivaRate = (float) ($settings['iva_global_rate'] ?? 21);

        $applyEffectiveTax = function($articulo) use ($usarImpuestos, $ivaGlobalEnabled, $ivaRate) {
            if (!$usarImpuestos) {
                $articulo->effective_iva = 0;
            } else if ($ivaGlobalEnabled) {
                $articulo->effective_iva = $ivaRate;
            } else {
                $articulo->effective_iva = $articulo->iva;
            }
            $articulo->effective_pvp = round($articulo->precio_sin_iva * (1 + ($articulo->effective_iva / 100)), 2);

            // Mapear desglose de lotes y vencimientos recibidos
            $articulo->lotes_desglose = $articulo->compraDetalles
                ->take(10)
                ->map(function($det) use ($articulo) {
                    $rawCode = $det->codigo_escaneado;
                    if (!$rawCode && $det->lote) {
                        $rawCode = "(01){$articulo->codigo}(10){$det->lote}(21){$det->serie}";
                    }
                    return [
                        'lote' => $det->lote ?: 'S/N',
                        'serie' => $det->serie ?: 'S/N',
                        'codigo_escaneado' => $rawCode ?: 'Sin código guardado',
                        'fecha_vencimiento' => $det->fecha_vencimiento ? $det->fecha_vencimiento->format('Y-m-d') : null,
                        'peso' => (float) $det->cantidad_peso,
                        'fecha_recepcion' => $det->compra ? $det->compra->fecha_compra->format('Y-m-d H:i') : null,
                    ];
                })
                ->values();

            return $articulo;
        };

        $articulos = Articulo::query()
            ->with(['familia', 'compraDetalles.compra'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('codigo', 'like', "%{$search}%")
                        ->orWhere('codigo_cliente', 'like', "%{$search}%")
                        ->orWhere('descripcion', 'like', "%{$search}%")
                        ->orWhereHas('familia', function ($familiaQuery) use ($search) {
                            $familiaQuery->where('nombre', 'like', "%{$search}%");
                        });
                });
            })
            ->get()
            ->map($applyEffectiveTax)
            ->sortBy('descripcion')
            ->values();

        $catalogoArticulos = Articulo::query()
            ->with(['familia', 'compraDetalles.compra'])
            ->orderBy('descripcion', 'asc')
            ->get()
            ->map($applyEffectiveTax);

        $familias = Familia::query()
            ->withCount('articulos')
            ->orderBy('nombre', 'asc')
            ->get();

        return view('articulos.index', compact('articulos', 'catalogoArticulos', 'familias', 'search', 'settings', 'usarImpuestos'));
    }

    public function create(): View
    {
        return view('articulos.create', [
            'articulo' => new Articulo([
                'familia_id' => null,
                'codigo_cliente' => null,
                'aplica_iva' => true,
                'iva' => 21,
                'stock' => 0,
                'estado' => 'activo',
            ]),
            'familias' => Familia::query()->orderBy('nombre', 'asc')->get(),
            'settings' => Setting::values(),
            'action' => route('articulos.store'),
            'method' => 'POST',
            'pageTitle' => 'Nuevo artículo',
            'submitLabel' => 'Guardar artículo',
        ]);
    }

    public static function parseGs1Barcode(?string $rawCode): array
    {
        if (!$rawCode) {
            return ['is_gs1' => false, 'gtin' => null, 'codigo_cliente' => null, 'peso' => null, 'fecha_vencimiento' => null, 'lote' => null, 'serie' => null, 'codigo_completo' => null];
        }

        $code = preg_replace('/[()\-\s]/', '', trim($rawCode));
        $result = [
            'is_gs1' => false,
            'gtin' => null,
            'codigo_cliente' => null,
            'peso' => null,
            'fecha_vencimiento' => null,
            'lote' => null,
            'serie' => null,
            'codigo_completo' => $code,
        ];

        if (preg_match('/01(\d{14})/', $code, $gtinMatch)) {
            $result['is_gs1'] = true;
            $result['gtin'] = $gtinMatch[1];
            $result['codigo_cliente'] = substr($gtinMatch[1], -6);

            // Peso: 320x (libras) o 310x (kilos)
            if (preg_match('/(320[0-5]|310[0-5])(\d{6})/', $code, $wMatch)) {
                $decimals = (int) substr($wMatch[1], 3, 1);
                $weightVal = ((int) $wMatch[2]) / pow(10, $decimals);
                $result['peso'] = $weightVal;
            }

            // Fecha de Vencimiento: (17YYMMDD o 15YYMMDD)
            if (preg_match('/(?:17|15)(\d{2})(\d{2})(\d{2})/', $code, $expMatch)) {
                $year = 2000 + (int) $expMatch[1];
                $month = str_pad($expMatch[2], 2, '0', STR_PAD_LEFT);
                $day = str_pad($expMatch[3], 2, '0', STR_PAD_LEFT);
                if (checkdate((int)$month, (int)$day, (int)$year)) {
                    $result['fecha_vencimiento'] = "{$year}-{$month}-{$day}";
                }
            }

            // Lote: 10XXXXXX
            if (preg_match('/10([A-Za-z0-9]+?)(?:21|320|310|17|15|$)/', $code, $lotMatch)) {
                $result['lote'] = $lotMatch[1];
            }

            // Serie: 21XXXXXX
            if (preg_match('/21([A-Za-z0-9]+)/', $code, $serMatch)) {
                $result['serie'] = $serMatch[1];
            }
        }

        return $result;
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateArticulo($request);

        $settings = Setting::values();
        $data = $this->applyTaxRules($data, $settings);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('articulos', 'public');
        }

        $rawCodigo = $request->input('codigo_original') ?: $request->input('codigo');
        $gs1Data = self::parseGs1Barcode($rawCodigo);

        if ($gs1Data['is_gs1'] && $gs1Data['gtin']) {
            $data['codigo'] = $gs1Data['gtin'];
            if (empty($data['codigo_cliente'])) {
                $data['codigo_cliente'] = $gs1Data['codigo_cliente'];
            }
            if ((float)($data['stock'] ?? 0) <= 0 && $gs1Data['peso'] > 0) {
                $data['stock'] = $gs1Data['peso'];
            }
        }

        $articulo = Articulo::create($data);

        // Si el artículo se crea con stock inicial > 0 y es pesable, registrar su lote en compra_detalles
        if ((float)$articulo->stock > 0 && $articulo->tipo_articulo !== 'unidad') {
            $compraInicial = \App\Models\Compra::firstOrCreate(
                ['numero_factura' => 'INI-' . $articulo->codigo],
                [
                    'proveedor_id' => null,
                    'proveedor_nombre' => 'Inventario Inicial',
                    'fecha_compra' => now(),
                    'subtotal' => round($articulo->stock * (float)$articulo->precio_compra, 2),
                    'iva' => 0,
                    'total' => round($articulo->stock * (float)$articulo->precio_compra, 2),
                    'observaciones' => 'Registro de lote inicial al dar de alta el artículo',
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                ]
            );

            \App\Models\CompraDetalle::create([
                'compra_id' => $compraInicial->id,
                'articulo_id' => $articulo->id,
                'codigo_escaneado' => $gs1Data['is_gs1'] ? $gs1Data['codigo_completo'] : $articulo->codigo,
                'lote' => $gs1Data['lote'] ?: 'INICIAL',
                'serie' => $gs1Data['serie'] ?: '1',
                'fecha_vencimiento' => $gs1Data['fecha_vencimiento'] ?: now()->addMonths(6)->toDateString(),
                'cantidad_peso' => (float)$articulo->stock,
                'costo_unitario' => (float)$articulo->precio_compra,
                'subtotal' => round($articulo->stock * (float)$articulo->precio_compra, 2),
            ]);
        }

        return redirect()
            ->route('articulos.index')
            ->with('status', 'Artículo creado correctamente y lote inicial registrado.');
    }

    public function edit(Articulo $articulo): View
    {
        return view('articulos.edit', [
            'articulo' => $articulo,
            'familias' => Familia::query()->orderBy('nombre', 'asc')->get(),
            'settings' => Setting::values(),
            'action' => route('articulos.update', $articulo),
            'method' => 'PUT',
            'pageTitle' => 'Editar artículo',
            'submitLabel' => 'Actualizar artículo',
        ]);
    }

    public function update(Request $request, Articulo $articulo): RedirectResponse
    {
        $data = $this->validateArticulo($request, $articulo->id);

        $settings = Setting::values();
        $data = $this->applyTaxRules($data, $settings);

        if ($request->hasFile('imagen')) {
            // Delete old image if needed
            if ($articulo->imagen) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($articulo->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('articulos', 'public');
        }

        $articulo->update($data);

        return redirect()
            ->route('articulos.index')
            ->with('status', 'Artículo actualizado correctamente.');
    }

    public function destroy(Articulo $articulo): RedirectResponse
    {
        try {
            $tieneTransferencias = \App\Models\TransferenciaDetalle::where('articulo_id', $articulo->id)->exists();
            $tieneCompras = \App\Models\CompraDetalle::where('articulo_id', $articulo->id)->exists();
            $tieneVentas = \App\Models\VentaDetalle::where('articulo_id', $articulo->id)->exists();

            if ($tieneTransferencias || $tieneCompras || $tieneVentas) {
                $articulo->update([
                    'estado' => 'inactivo',
                    'stock' => 0,
                ]);

                return redirect()
                    ->route('articulos.index')
                    ->with('warning', "El artículo '{$articulo->descripcion}' tiene movimientos registrados en transferencias o compras. Para proteger la trazabilidad contable, ha sido marcado como INACTIVO.");
            }

            $articulo->delete();

            return redirect()
                ->route('articulos.index')
                ->with('status', 'Artículo eliminado correctamente.');
        } catch (\Throwable $e) {
            $articulo->update([
                'estado' => 'inactivo',
                'stock' => 0,
            ]);

            return redirect()
                ->route('articulos.index')
                ->with('warning', "El artículo '{$articulo->descripcion}' está vinculado a registros del sistema y fue cambiado a estado INACTIVO.");
        }
    }

    public function adjustStock(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'stock_articulo_id' => ['required', 'exists:articulos,id'],
            'movimiento' => ['required', Rule::in(['sumar', 'restar'])],
            'cantidad' => ['required', 'numeric', 'min:0.001'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $articulo = Articulo::query()->findOrFail($data['stock_articulo_id']);
        $cantidad = (float) $data['cantidad'];
        $stockAnterior = (float) $articulo->stock;

        if ($data['movimiento'] === 'restar' && $cantidad > $articulo->stock) {
            return redirect()
                ->route('articulos.index')
                ->withErrors(['cantidad' => 'No hay stock suficiente para restar ese peso.'])
                ->withInput();
        }

        $articulo->stock = $data['movimiento'] === 'sumar'
            ? round(((float) $articulo->stock + $cantidad), 3)
            : round(((float) $articulo->stock - $cantidad), 3);

        $articulo->estado = $articulo->stock <= 0 ? 'sin_stock' : 'activo';
        $articulo->save();

        $stockNuevo = (float) $articulo->stock;
        $diferencia = round($stockNuevo - $stockAnterior, 3);
        $modoInventario = Setting::getValue('modo_inventario', 'dinamico');

        AjusteInventario::create([
            'articulo_id' => $articulo->id,
            'compra_detalle_id' => null,
            'lote' => null,
            'serie' => null,
            'user_id' => Auth::id(),
            'tipo_ajuste' => $data['movimiento'] === 'sumar' ? 'suma' : 'resta',
            'stock_anterior' => $stockAnterior,
            'cantidad_ajustada' => $cantidad,
            'stock_nuevo' => $stockNuevo,
            'diferencia_stock' => $diferencia,
            'origen' => 'web',
            'modo_inventario' => $modoInventario,
            'motivo' => $data['motivo'] ?? null,
        ]);

        return redirect()
            ->route('articulos.index')
            ->with('status', 'Stock ajustado correctamente.');
    }

    private function validateArticulo(Request $request, ?int $articuloId = null): array
    {
        $rules = [
            'codigo' => [
                'required',
                'string',
                'max:100',
                Rule::unique('articulos', 'codigo')->ignore($articuloId),
            ],
            'codigo_cliente' => ['nullable', 'string', 'max:50'],
            'item' => ['nullable', 'string', 'max:50'],
            'tipo_articulo' => ['required', Rule::in(['pesable', 'unidad'])],
            'familia_id' => ['required', 'exists:familias,id'],
            'aplica_iva' => ['nullable', 'boolean'],
            'descripcion' => ['required', 'string', 'max:255'],
            'precio_compra' => ['nullable', 'numeric', 'min:0'],
            'precio_sin_iva' => ['required', 'numeric', 'min:0'],
            'pvp' => ['nullable', 'numeric', 'min:0'],
            'precios_adicionales' => ['nullable', 'array'],
            'precios_adicionales.*.nombre' => ['nullable', 'string', 'max:100'],
            'precios_adicionales.*.precio' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'numeric', 'min:0'],
            'stock_minimo' => ['required', 'numeric', 'min:0'],
            'estado' => ['required', Rule::in(['activo', 'sin_stock', 'inactivo'])],
        ];

        if ($request->hasFile('imagen')) {
            $rules['imagen'] = ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'];
        }

        $data = $request->validate($rules);

        if (isset($data['precios_adicionales']) && is_array($data['precios_adicionales'])) {
            $filtered = [];
            foreach ($data['precios_adicionales'] as $item) {
                if (!empty($item['nombre']) && isset($item['precio']) && $item['precio'] !== '' && $item['precio'] !== null) {
                    $filtered[] = [
                        'nombre' => trim($item['nombre']),
                        'precio' => (float) $item['precio'],
                    ];
                }
            }
            $data['precios_adicionales'] = array_values($filtered);
        }

        return $data;
    }

    private function applyTaxRules(array $data, array $settings): array
    {
        $usarImpuestos = (bool) ((int) ($settings['usar_impuestos'] ?? 1));
        $ivaRate = (float) ($settings['iva_global_rate'] ?? 21);
        $ivaGlobalEnabled = (bool) ((int) ($settings['iva_global_enabled'] ?? 1));
        
        if (!$usarImpuestos) {
            $aplicaIva = false;
        } else {
            $aplicaIva = $ivaGlobalEnabled ? true : (bool) ($data['aplica_iva'] ?? false);
        }

        $data['aplica_iva'] = $aplicaIva;
        $data['iva'] = $aplicaIva ? $ivaRate : 0;

        if (!isset($data['pvp']) || $data['pvp'] === null || $data['pvp'] === '') {
            $data['pvp'] = $aplicaIva
                ? round((float) $data['precio_sin_iva'] * (1 + ($ivaRate / 100)), 2)
                : round((float) $data['precio_sin_iva'], 2);
        }

        return $data;
    }
}