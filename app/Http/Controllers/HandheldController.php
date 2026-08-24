<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Cliente;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Proveedor;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HandheldController extends Controller
{
    /**
     * Menú Principal del Modo Handheld (Solo 3 módulos rápidos).
     */
    public function index(): View
    {
        return view('handheld.index');
    }

    /**
     * Módulo 1: TPV Móvil (Venta Express en Pasillo).
     */
    public function tpv(): View
    {
        $clientes = Cliente::where('estado', 'activo')->orderBy('nombre')->get();
        $articulos = Articulo::where('estado', 'activo')->with('familia')->get();
        $settings = Setting::values();

        return view('handheld.tpv', compact('clientes', 'articulos', 'settings'));
    }

    /**
     * Módulo 2: Recepción de Compras Móvil (Bodega/Cuarto Frío).
     */
    public function compras(): View
    {
        $proveedores = Proveedor::where('estado', 'activo')->orderBy('nombre')->get();
        $articulos = Articulo::where('estado', 'activo')->orderBy('descripcion')->get();

        return view('handheld.compras', compact('proveedores', 'articulos'));
    }

    /**
     * Módulo 3: Conteo / Ajuste Rápido de Inventario Móvil.
     */
    public function conteo(): View
    {
        $articulos = Articulo::orderBy('descripcion')->get();
        $settings = Setting::values();

        return view('handheld.conteo', compact('articulos', 'settings'));
    }

    /**
     * Procesar Ajuste Rápido de Conteo de Inventario desde la Handheld.
     */
    public function storeConteo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'articulo_id' => ['required', 'exists:articulos,id'],
            'tipo_ajuste' => ['required', 'in:reemplazo,suma,resta'],
            'cantidad' => ['required', 'numeric', 'min:0.001'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $articulo = Articulo::findOrFail($data['articulo_id']);
        $cantidad = (float) $data['cantidad'];

        if ($data['tipo_ajuste'] === 'reemplazo') {
            $articulo->stock = $cantidad;
        } elseif ($data['tipo_ajuste'] === 'suma') {
            $articulo->stock += $cantidad;
        } else {
            $articulo->stock = max(0, $articulo->stock - $cantidad);
        }

        if ($articulo->stock <= 0) {
            $articulo->estado = 'sin_stock';
        } elseif ($articulo->stock <= $articulo->stock_minimo) {
            $articulo->estado = 'bajo_stock';
        } else {
            $articulo->estado = 'activo';
        }

        $articulo->save();

        return redirect()
            ->route('handheld.conteo')
            ->with('status', 'Stock de "' . $articulo->descripcion . '" actualizado a ' . number_format($articulo->stock, 3) . '.');
    }
}
