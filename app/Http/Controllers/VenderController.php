<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Familia;
use App\Models\Articulo;

class VenderController extends Controller
{
    public function tactil()
    {
        $familias = Familia::all();
        $articulos = Articulo::with('familia')->get();
        $settings = \App\Models\Setting::values();

        return view('vender.tactil', compact('familias', 'articulos', 'settings'));
    }

    public function normal()
    {
        $articulos = Articulo::all();
        $settings = \App\Models\Setting::values();
        
        return view('vender.normal', compact('articulos', 'settings'));
    }

    public function cobrar(Request $request)
    {
        $data = $request->validate([
            'total' => 'required|numeric',
            'subtotal' => 'required|numeric',
            'impuestos' => 'required|numeric',
            'metodo_pago' => 'nullable|string',
            'monto_recibido' => 'nullable|numeric',
            'vuelto' => 'nullable|numeric',
            'items' => 'required|array',
            'items.*.articulo_id' => 'required|exists:articulos,id',
            'items.*.cantidad' => 'required|numeric',
            'items.*.precio' => 'required|numeric',
            'items.*.subtotal' => 'required|numeric',
        ]);

        $venta = \App\Models\Venta::create([
            'subtotal' => $data['subtotal'],
            'impuestos' => $data['impuestos'],
            'total' => $data['total'],
            'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
            'monto_recibido' => $data['monto_recibido'] ?? $data['total'],
            'vuelto' => $data['vuelto'] ?? 0,
            'user_id' => auth()->id(),
        ]);

        foreach ($data['items'] as $item) {
            $venta->detalles()->create([
                'articulo_id' => $item['articulo_id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio'],
                'subtotal' => $item['subtotal'],
            ]);

            // Descontar stock
            $articulo = Articulo::find($item['articulo_id']);
            if ($articulo) {
                $articulo->stock -= $item['cantidad'];
                $articulo->save();
            }
        }

        return response()->json(['success' => true, 'venta' => $venta->load('detalles.articulo')]);
    }
}
