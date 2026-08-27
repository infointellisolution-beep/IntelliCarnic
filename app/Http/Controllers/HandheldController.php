<?php

namespace App\Http\Controllers;

use App\Models\AjusteInventario;
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

        $nextId = (Compra::max('id') ?? 0) + 1;
        $nextInvoiceNumber = 'FAC-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        return view('handheld.compras', compact('proveedores', 'articulos', 'nextInvoiceNumber'));
    }

    /**
     * Módulo 3: Conteo / Ajuste Rápido de Inventario Móvil.
     */
    public function conteo(): View
    {
        $articulos = Articulo::orderBy('descripcion')->get();
        $settings = Setting::values();
        $modoInventario = $settings['modo_inventario'] ?? 'dinamico';

        // En modo dinámico, cargar los lotes activos (compra_detalles con stock > 0)
        $lotesActivos = [];
        if ($modoInventario === 'dinamico') {
            $lotesActivos = CompraDetalle::where('cantidad_peso', '>', 0)
                ->with('articulo:id,descripcion')
                ->orderBy('articulo_id')
                ->orderBy('fecha_vencimiento', 'asc')
                ->get()
                ->map(function ($lote) use ($settings) {
                    return [
                        'id' => $lote->id,
                        'articulo_id' => $lote->articulo_id,
                        'lote' => $lote->lote,
                        'serie' => $lote->serie,
                        'codigo_escaneado' => $lote->codigo_escaneado,
                        'cantidad_peso' => (float) $lote->cantidad_peso,
                        'fecha_vencimiento' => $lote->fecha_vencimiento ? $lote->fecha_vencimiento->format('Y-m-d') : null,
                        'created_at' => $lote->created_at ? $lote->created_at->format('Y-m-d H:i') : null,
                    ];
                })
                ->values()
                ->toArray();
        }

        return view('handheld.conteo', compact('articulos', 'settings', 'modoInventario', 'lotesActivos'));
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
            'lote_id' => ['nullable', 'integer', 'exists:compra_detalles,id'],
        ]);

        $articulo = Articulo::findOrFail($data['articulo_id']);
        $cantidad = (float) $data['cantidad'];
        $modoInventario = Setting::getValue('modo_inventario', 'dinamico');
        $loteId = $data['lote_id'] ?? null;

        DB::beginTransaction();
        try {
            if ($modoInventario === 'dinamico' && $loteId) {
                // ── Modo Dinámico: Ajustar el lote específico ──
                $lote = CompraDetalle::where('id', $loteId)
                    ->where('articulo_id', $articulo->id)
                    ->firstOrFail();

                $stockAnteriorLote = (float) $lote->cantidad_peso;

                if ($data['tipo_ajuste'] === 'reemplazo') {
                    $lote->cantidad_peso = $cantidad;
                } elseif ($data['tipo_ajuste'] === 'suma') {
                    $lote->cantidad_peso += $cantidad;
                } else {
                    $lote->cantidad_peso = max(0, $lote->cantidad_peso - $cantidad);
                }

                $lote->save();

                $stockNuevoLote = (float) $lote->cantidad_peso;
                $diferenciaLote = round($stockNuevoLote - $stockAnteriorLote, 3);

                // Recalcular stock general del artículo como suma de todos sus lotes
                $nuevoStockTotal = (float) CompraDetalle::where('articulo_id', $articulo->id)
                    ->where('cantidad_peso', '>', 0)
                    ->sum('cantidad_peso');

                $articulo->stock = $nuevoStockTotal;
                $msgExtra = ' (Lote: ' . ($lote->lote ?: $lote->id) . ' → ' . number_format($lote->cantidad_peso, 3) . ')';

                // Registrar auditoría en AjusteInventario
                AjusteInventario::create([
                    'articulo_id' => $articulo->id,
                    'compra_detalle_id' => $lote->id,
                    'lote' => $lote->lote,
                    'serie' => $lote->serie,
                    'user_id' => Auth::id(),
                    'tipo_ajuste' => $data['tipo_ajuste'],
                    'stock_anterior' => $stockAnteriorLote,
                    'cantidad_ajustada' => $cantidad,
                    'stock_nuevo' => $stockNuevoLote,
                    'diferencia_stock' => $diferenciaLote,
                    'origen' => 'handheld',
                    'modo_inventario' => 'dinamico',
                    'motivo' => $data['motivo'] ?? null,
                ]);
            } else {
                // ── Modo Simple: Ajustar stock general directamente ──
                $stockAnterior = (float) $articulo->stock;

                if ($data['tipo_ajuste'] === 'reemplazo') {
                    $articulo->stock = $cantidad;
                } elseif ($data['tipo_ajuste'] === 'suma') {
                    $articulo->stock += $cantidad;
                } else {
                    $articulo->stock = max(0, $articulo->stock - $cantidad);
                }
                $msgExtra = '';

                $stockNuevo = (float) $articulo->stock;
                $diferencia = round($stockNuevo - $stockAnterior, 3);

                // Registrar auditoría en AjusteInventario
                AjusteInventario::create([
                    'articulo_id' => $articulo->id,
                    'compra_detalle_id' => null,
                    'lote' => null,
                    'serie' => null,
                    'user_id' => Auth::id(),
                    'tipo_ajuste' => $data['tipo_ajuste'],
                    'stock_anterior' => $stockAnterior,
                    'cantidad_ajustada' => $cantidad,
                    'stock_nuevo' => $stockNuevo,
                    'diferencia_stock' => $diferencia,
                    'origen' => 'handheld',
                    'modo_inventario' => 'simple',
                    'motivo' => $data['motivo'] ?? null,
                ]);
            }

            // Actualizar estado del artículo
            if ($articulo->stock <= 0) {
                $articulo->estado = 'sin_stock';
            } elseif ($articulo->stock <= $articulo->stock_minimo) {
                $articulo->estado = 'bajo_stock';
            } else {
                $articulo->estado = 'activo';
            }

            $articulo->save();
            DB::commit();

            return redirect()
                ->route('handheld.conteo')
                ->with('status', 'Stock de "' . $articulo->descripcion . '" actualizado a ' . number_format($articulo->stock, 3) . '.' . $msgExtra);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('handheld.conteo')
                ->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }
}
