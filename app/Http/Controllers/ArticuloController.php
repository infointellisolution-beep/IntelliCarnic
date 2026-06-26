<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Familia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticuloController extends Controller
{
    public function index(): View
    {
        $search = request()->string('search')->trim()->toString();

        $articulos = Articulo::query()
            ->with('familia')
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
            ->sortBy('descripcion')
            ->values();

        $catalogoArticulos = Articulo::query()
            ->with('familia')
            ->orderBy('descripcion', 'asc')
            ->get();

        $familias = Familia::query()
            ->withCount('articulos')
            ->orderBy('nombre', 'asc')
            ->get();

        return view('articulos.index', compact('articulos', 'catalogoArticulos', 'familias', 'search'));
    }

    public function create(): View
    {
        return view('articulos.create', [
            'articulo' => new Articulo([
                'familia_id' => null,
                'codigo_cliente' => null,
                'iva' => 21,
                'stock' => 0,
                'estado' => 'activo',
            ]),
            'familias' => Familia::query()->orderBy('nombre', 'asc')->get(),
            'action' => route('articulos.store'),
            'method' => 'POST',
            'pageTitle' => 'Nuevo artículo',
            'submitLabel' => 'Guardar artículo',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateArticulo($request);

        if ($data['pvp'] === null) {
            $data['pvp'] = round((float) $data['precio_sin_iva'] * (1 + ((float) $data['iva'] / 100)), 2);
        }

        Articulo::create($data);

        return redirect()
            ->route('articulos.index')
            ->with('status', 'Artículo creado correctamente.');
    }

    public function edit(Articulo $articulo): View
    {
        return view('articulos.edit', [
            'articulo' => $articulo,
            'familias' => Familia::query()->orderBy('nombre', 'asc')->get(),
            'action' => route('articulos.update', $articulo),
            'method' => 'PUT',
            'pageTitle' => 'Editar artículo',
            'submitLabel' => 'Actualizar artículo',
        ]);
    }

    public function update(Request $request, Articulo $articulo): RedirectResponse
    {
        $data = $this->validateArticulo($request, $articulo->id);

        if ($data['pvp'] === null) {
            $data['pvp'] = round((float) $data['precio_sin_iva'] * (1 + ((float) $data['iva'] / 100)), 2);
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
        $data = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:50',
                Rule::unique('articulos', 'codigo')->ignore($articuloId),
            ],
            'codigo_cliente' => ['required', 'string', 'max:50'],
            'familia_id' => ['required', 'exists:familias,id'],
            'descripcion' => ['required', 'string', 'max:255'],
            'precio_sin_iva' => ['required', 'numeric', 'min:0'],
            'iva' => ['required', 'numeric', 'min:0', 'max:100'],
            'pvp' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'numeric', 'min:0'],
            'estado' => ['required', Rule::in(['activo', 'sin_stock', 'inactivo'])],
        ]);

        return $data;
    }
}