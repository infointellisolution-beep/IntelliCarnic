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
            'descuento' => 'nullable|numeric',
            'impuestos' => 'required|numeric',
            'metodo_pago' => 'nullable|string',
            'monto_recibido' => 'nullable|numeric',
            'vuelto' => 'nullable|numeric',
            'items' => 'required|array',
            'items.*.articulo_id' => 'required|exists:articulos,id',
            'items.*.codigo_escaneado' => 'nullable|string',
            'items.*.cantidad' => 'required|numeric',
            'items.*.precio' => 'required|numeric',
            'items.*.descuento' => 'nullable|numeric',
            'items.*.subtotal' => 'required|numeric',
        ]);

        $cajaActiva = \App\Models\CajaSesion::query()->where('estado', 'abierta')->first();

        $venta = \App\Models\Venta::create([
            'subtotal' => $data['subtotal'],
            'descuento' => $data['descuento'] ?? 0,
            'impuestos' => $data['impuestos'],
            'total' => $data['total'],
            'metodo_pago' => $data['metodo_pago'] ?? 'efectivo',
            'monto_recibido' => $data['monto_recibido'] ?? $data['total'],
            'vuelto' => $data['vuelto'] ?? 0,
            'user_id' => auth()->id(),
            'caja_sesion_id' => $cajaActiva?->id,
        ]);

        foreach ($data['items'] as $item) {
            $venta->detalles()->create([
                'articulo_id' => $item['articulo_id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio'],
                'descuento' => $item['descuento'] ?? 0,
                'subtotal' => $item['subtotal'],
            ]);

            // Descontar stock general
            $articulo = Articulo::find($item['articulo_id']);
            if ($articulo) {
                $pesoAVender = (float) $item['cantidad'];
                $codigoScanned = $item['codigo_escaneado'] ?? null;
                $articulo->stock = max(0, round(((float)$articulo->stock - $pesoAVender), 3));
                if ($articulo->stock <= 0) {
                    $articulo->estado = 'sin_stock';
                }
                $articulo->save();

                // 2. Descontar del lote específico o aplicar PEPS (FIFO) solo si estamos en Modo Dinámico
                $modoInventario = \App\Models\Setting::get('modo_inventario', 'dinamico');
                if ($modoInventario === 'dinamico') {
                    $specificLot = null;

                    if ($codigoScanned) {
                        $cleanCode = preg_replace('/[()\-\s]/', '', $codigoScanned);

                        // Extraer Serie (21) si existe
                        if (preg_match('/01(\d{14})/', $cleanCode, $mGtin)) {
                            $rest = substr($cleanCode, strpos($cleanCode, $mGtin[0]) + 16);
                            if (preg_match('/21([a-zA-Z0-9]+?)(?=10|11|15|17|310|320|$)/', $rest, $mSerie)) {
                                $serieVal = $mSerie[1];
                                $specificLot = \App\Models\CompraDetalle::where('articulo_id', $articulo->id)
                                    ->where('serie', $serieVal)
                                    ->where('cantidad_peso', '>', 0)
                                    ->first();
                            }
                        }

                        if (!$specificLot) {
                            $specificLot = \App\Models\CompraDetalle::where('articulo_id', $articulo->id)
                                ->where(function($q) use ($codigoScanned, $cleanCode) {
                                    $q->where('codigo_escaneado', $codigoScanned)
                                      ->orWhere('codigo_escaneado', $cleanCode);
                                })
                                ->where('cantidad_peso', '>', 0)
                                ->first();
                        }
                    }

                    if ($specificLot) {
                        $specificLot->cantidad_peso = max(0, round((float)$specificLot->cantidad_peso - $pesoAVender, 3));
                        $specificLot->save();
                    } else {
                        $lotesDisponibles = \App\Models\CompraDetalle::where('articulo_id', $articulo->id)
                            ->where('cantidad_peso', '>', 0)
                            ->orderByRaw('fecha_vencimiento IS NULL ASC, fecha_vencimiento ASC, id ASC')
                            ->get();

                        $porDescontar = $pesoAVender;
                        foreach ($lotesDisponibles as $loteDetalle) {
                            if ($porDescontar <= 0) break;

                            $pesoEnLote = (float) $loteDetalle->cantidad_peso;
                            if ($pesoEnLote >= $porDescontar) {
                                $loteDetalle->cantidad_peso = round($pesoEnLote - $porDescontar, 3);
                                $loteDetalle->save();
                                $porDescontar = 0;
                            } else {
                                $porDescontar -= $pesoEnLote;
                                $loteDetalle->cantidad_peso = 0;
                                $loteDetalle->save();
                            }
                        }
                    }
                }
            }
        }

        if ($cajaActiva) {
            $cajaActiva->recargarTotales();
        }

        $articulosActualizados = [];
        foreach ($data['items'] as $item) {
            $art = Articulo::find($item['articulo_id']);
            if ($art) {
                $articulosActualizados[] = [
                    'id' => $art->id,
                    'stock' => floatval($art->stock),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'venta' => $venta->load('detalles.articulo'),
            'articulos_actualizados' => $articulosActualizados,
        ]);
    }
}
