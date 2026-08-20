<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Familia;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateArticulo($request);

        $settings = Setting::values();
        $data = $this->applyTaxRules($data, $settings);

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('articulos', 'public');
        }

        $articulo = Articulo::create($data);

        return redirect()
            ->route('articulos.index')
            ->with('status', 'Artículo creado correctamente.');
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
        Articulo::destroy($articulo->getKey());

        return redirect()
            ->route('articulos.index')
            ->with('status', 'Artículo eliminado correctamente.');
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