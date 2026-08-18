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

    public function getTicket($id)
    {
        // Limpiar identificador (quitar ceros a la izquierda o prefijos si vienen ej. "T-00012")
        $cleanId = preg_replace('/\D/', '', $id);
        if (!$cleanId) {
            return response()->json([
                'success' => false,
                'message' => 'Número de ticket inválido.'
            ], 404);
        }

        $venta = \App\Models\Venta::with(['detalles.articulo', 'user'])->find($cleanId);

        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => "No se encontró ningún ticket con el folio #{$id}."
            ], 404);
        }

        $items = [];
        $totalDevolvible = 0;

        foreach ($venta->detalles as $det) {
            $cantVendida = (float) $det->cantidad;
            $cantDevuelta = (float) $det->cantidad_devuelta;
            $cantDisponible = max(0, round($cantVendida - $cantDevuelta, 3));
            
            // Precio unitario efectivo considerando descuento aplicado
            $precioEfectivo = $cantVendida > 0 ? round((float)$det->subtotal / $cantVendida, 2) : (float)$det->precio_unitario;

            $totalDevolvible += ($cantDisponible * $precioEfectivo);

            $items[] = [
                'id' => $det->id,
                'articulo_id' => $det->articulo_id,
                'descripcion' => $det->articulo?->descripcion ?? 'Artículo Eliminado',
                'codigo' => $det->articulo?->codigo ?? '-',
                'cantidad_vendida' => $cantVendida,
                'cantidad_devuelta' => $cantDevuelta,
                'cantidad_disponible' => $cantDisponible,
                'precio_unitario' => (float)$det->precio_unitario,
                'descuento' => (float)$det->descuento,
                'subtotal' => (float)$det->subtotal,
                'precio_efectivo' => $precioEfectivo,
            ];
        }

        return response()->json([
            'success' => true,
            'ticket' => [
                'id' => $venta->id,
                'folio' => str_pad($venta->id, 6, '0', STR_PAD_LEFT),
                'fecha' => $venta->created_at->format('d/m/Y H:i'),
                'cajero' => $venta->user?->name ?? 'Sistema',
                'metodo_pago' => strtoupper($venta->metodo_pago ?? 'EFECTIVO'),
                'subtotal' => (float)$venta->subtotal,
                'descuento' => (float)$venta->descuento,
                'impuestos' => (float)$venta->impuestos,
                'total' => (float)$venta->total,
                'estado' => $venta->estado ?? 'completada',
                'total_devolvible' => round($totalDevolvible, 2),
                'items' => $items,
            ]
        ]);
    }

    public function procesarDevolucion(Request $request)
    {
        $data = $request->validate([
            'venta_id' => 'required|exists:ventas,id',
            'metodo_reembolso' => 'required|string',
            'motivo' => 'nullable|string',
            'reingresar_stock' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.venta_detalle_id' => 'required|exists:venta_detalles,id',
            'items.*.cantidad_devolver' => 'required|numeric|min:0.001',
        ]);

        $venta = \App\Models\Venta::with('detalles.articulo')->findOrFail($data['venta_id']);
        $reingresarStock = $data['reingresar_stock'] ?? true;

        $itemsProcesados = [];
        $totalReembolsado = 0;

        foreach ($data['items'] as $itemInput) {
            $detalle = $venta->detalles->firstWhere('id', $itemInput['venta_detalle_id']);
            if (!$detalle) continue;

            $cantDisponible = max(0, round((float)$detalle->cantidad - (float)$detalle->cantidad_devuelta, 3));
            $cantDevolver = (float) $itemInput['cantidad_devolver'];

            if ($cantDevolver <= 0 || $cantDevolver > $cantDisponible) {
                return response()->json([
                    'success' => false,
                    'message' => "La cantidad a devolver para '{$detalle->articulo?->descripcion}' excede lo disponible ({$cantDisponible})."
                ], 422);
            }

            $precioEfectivo = (float)$detalle->cantidad > 0 ? ((float)$detalle->subtotal / (float)$detalle->cantidad) : (float)$detalle->precio_unitario;
            $subtotalItem = round($precioEfectivo * $cantDevolver, 2);
            $totalReembolsado += $subtotalItem;

            $itemsProcesados[] = [
                'detalle' => $detalle,
                'cantidad' => $cantDevolver,
                'precio_unitario' => (float)$detalle->precio_unitario,
                'subtotal' => $subtotalItem,
            ];
        }

        if (empty($itemsProcesados)) {
            return response()->json([
                'success' => false,
                'message' => 'No se seleccionó ningún artículo válido para devolver.'
            ], 422);
        }

        $cajaActiva = \App\Models\CajaSesion::where('estado', 'abierta')->first();

        // 1. Crear registro de devolución
        $devolucion = \App\Models\Devolucion::create([
            'venta_id' => $venta->id,
            'user_id' => auth()->id(),
            'caja_sesion_id' => $cajaActiva?->id,
            'total_reembolsado' => $totalReembolsado,
            'metodo_reembolso' => $data['metodo_reembolso'],
            'motivo' => $data['motivo'] ?: 'Devolución de cliente',
        ]);

        $articulosActualizados = [];

        // 2. Procesar detalles y reintegrar stock
        foreach ($itemsProcesados as $proc) {
            $det = $proc['detalle'];
            $devolucion->detalles()->create([
                'venta_detalle_id' => $det->id,
                'articulo_id' => $det->articulo_id,
                'cantidad' => $proc['cantidad'],
                'precio_unitario' => $proc['precio_unitario'],
                'subtotal' => $proc['subtotal'],
                'reingresar_stock' => $reingresarStock,
            ]);

            // Actualizar cantidad_devuelta en la venta original
            $det->cantidad_devuelta = round((float)$det->cantidad_devuelta + $proc['cantidad'], 3);
            $det->save();

            // Reingresar stock
            if ($reingresarStock && $det->articulo) {
                $art = $det->articulo;
                $art->stock = round((float)$art->stock + $proc['cantidad'], 3);
                if ($art->estado === 'sin_stock' && $art->stock > 0) {
                    $art->estado = 'activo';
                }
                $art->save();

                $articulosActualizados[] = [
                    'id' => $art->id,
                    'stock' => floatval($art->stock),
                ];
            }
        }

        // 3. Actualizar estado de la venta
        $venta->refresh();
        $totalVendida = $venta->detalles->sum('cantidad');
        $totalDevuelta = $venta->detalles->sum('cantidad_devuelta');

        if ($totalDevuelta >= $totalVendida) {
            $venta->estado = 'devuelta';
        } else {
            $venta->estado = 'parcialmente_devuelta';
        }
        $venta->save();

        // 4. Si fue reembolso en efectivo, registrar salida de caja
        if ($data['metodo_reembolso'] === 'efectivo' && $cajaActiva) {
            \App\Models\CajaMovimiento::create([
                'caja_sesion_id' => $cajaActiva->id,
                'user_id' => auth()->id(),
                'tipo' => 'salida',
                'monto' => $totalReembolsado,
                'concepto' => "Reembolso Devolución Ticket #{$venta->id}" . ($data['motivo'] ? " ({$data['motivo']})" : ''),
                'observaciones' => "Reembolso en caja por devolución #{$devolucion->id}",
            ]);
            $cajaActiva->recargarTotales();
        }

        return response()->json([
            'success' => true,
            'message' => 'Devolución procesada correctamente.',
            'devolucion' => $devolucion->load(['detalles.articulo', 'venta', 'user']),
            'articulos_actualizados' => $articulosActualizados,
        ]);
    }
}
