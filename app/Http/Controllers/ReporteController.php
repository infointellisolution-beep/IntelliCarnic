<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Familia;
use App\Models\Setting;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'ventas');
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->get('fecha_fin', now()->toDateString());
        $familiaId = $request->get('familia_id');
        $filtroStock = $request->get('filtro_stock', 'todos'); // todos, ok, bajo, sin_stock

        $settings = Setting::values();
        $unidadPeso = strtoupper($settings['unidad_peso'] ?? 'LB');
        $familias = Familia::orderBy('nombre', 'asc')->get();

        // 1. Datos para Reporte de Ventas
        $ventasQuery = Venta::query()
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        $totalVentasMonto = (float) $ventasQuery->sum('total');
        $totalVentasSubtotal = (float) $ventasQuery->sum('subtotal');
        $totalVentasImpuestos = (float) $ventasQuery->sum('impuestos');
        $numVentas = $ventasQuery->count();
        $promedioVenta = $numVentas > 0 ? $totalVentasMonto / $numVentas : 0;

        $totalPesoVendido = (float) VentaDetalle::whereHas('venta', function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
        })->sum('cantidad');

        $ventasPorMetodo = Venta::query()
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->select('metodo_pago', DB::raw('COUNT(*) as total_transacciones'), DB::raw('SUM(total) as monto_total'))
            ->groupBy('metodo_pago')
            ->get();

        $topProductosVendidos = VentaDetalle::whereHas('venta', function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
        })
            ->select('articulo_id', DB::raw('SUM(cantidad) as total_cantidad'), DB::raw('SUM(subtotal) as total_monto'))
            ->with('articulo')
            ->groupBy('articulo_id')
            ->orderByDesc('total_cantidad')
            ->take(10)
            ->get();

        $ventasLista = Venta::with('detalles.articulo')
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page_ventas');

        // 2. Datos para Reporte de Compras
        $comprasQuery = Compra::query()
            ->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        $totalComprasMonto = (float) $comprasQuery->sum('total');
        $totalComprasSubtotal = (float) $comprasQuery->sum('subtotal');
        $numCompras = $comprasQuery->count();

        $totalPesoComprado = (float) CompraDetalle::whereHas('compra', function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
        })->sum('cantidad_peso');

        $comprasLista = Compra::with('detalles.articulo')
            ->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->orderBy('fecha_compra', 'desc')
            ->paginate(15, ['*'], 'page_compras');

        // 3. Datos para Reporte de Inventario Comparativo (Físico vs Mínimo)
        $articulosQuery = Articulo::with('familia');

        if (!empty($familiaId)) {
            $articulosQuery->where('familia_id', $familiaId);
        }

        $articulosTodos = $articulosQuery->orderBy('descripcion', 'asc')->get();

        // Categorizar productos por estado de stock
        $articulosProcesados = $articulosTodos->map(function ($art) {
            $stockFisico = (float) $art->stock;
            $stockMinimo = (float) ($art->stock_minimo ?? 0);
            $diferencia = $stockFisico - $stockMinimo;

            if ($stockFisico <= 0) {
                $estado = 'sin_stock';
                $badgeClass = 'badge-danger';
                $estadoLabel = 'Sin Stock';
            } elseif ($stockFisico <= $stockMinimo) {
                $estado = 'bajo';
                $badgeClass = 'badge-warning';
                $estadoLabel = 'Stock Bajo';
            } else {
                $estado = 'ok';
                $badgeClass = 'badge-success';
                $estadoLabel = 'Stock Normal';
            }

            $valorInversion = $stockFisico * (float) $art->precio_sin_iva;

            $art->stock_fisico_num = $stockFisico;
            $art->stock_minimo_num = $stockMinimo;
            $art->diferencia_num = $diferencia;
            $art->estado_evaluado = $estado;
            $art->badge_class = $badgeClass;
            $art->estado_label = $estadoLabel;
            $art->valor_inversion = $valorInversion;

            return $art;
        });

        $countTotalArticulos = $articulosProcesados->count();
        $countStockOk = $articulosProcesados->where('estado_evaluado', 'ok')->count();
        $countStockBajo = $articulosProcesados->where('estado_evaluado', 'bajo')->count();
        $countSinStock = $articulosProcesados->where('estado_evaluado', 'sin_stock')->count();
        $valorTotalInventario = $articulosProcesados->sum('valor_inversion');

        // Filtrar según la selección del usuario (ok, bajo, sin_stock)
        $articulosFiltrados = $articulosProcesados;
        if ($filtroStock !== 'todos') {
            $articulosFiltrados = $articulosProcesados->where('estado_evaluado', $filtroStock);
        }

        return view('reportes.index', compact(
            'tab',
            'fechaInicio',
            'fechaFin',
            'familiaId',
            'filtroStock',
            'unidadPeso',
            'familias',
            // Ventas
            'totalVentasMonto',
            'totalVentasSubtotal',
            'totalVentasImpuestos',
            'numVentas',
            'promedioVenta',
            'totalPesoVendido',
            'ventasPorMetodo',
            'topProductosVendidos',
            'ventasLista',
            // Compras
            'totalComprasMonto',
            'totalComprasSubtotal',
            'numCompras',
            'totalPesoComprado',
            'comprasLista',
            // Inventario
            'countTotalArticulos',
            'countStockOk',
            'countStockBajo',
            'countSinStock',
            'valorTotalInventario',
            'articulosFiltrados'
        ));
    }

    public function exportarCsv(Request $request, string $tipo): StreamedResponse
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->get('fecha_fin', now()->toDateString());
        $filename = "Reporte_{$tipo}_" . date('Y-m-d_H-i') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return response()->stream(function () use ($tipo, $fechaInicio, $fechaFin, $request) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($tipo === 'ventas') {
                fputcsv($handle, ['N° Venta', 'Fecha / Hora', 'Método Pago', 'Subtotal ($)', 'Impuestos ($)', 'Total ($)']);

                Venta::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
                    ->orderBy('created_at', 'desc')
                    ->chunk(100, function ($ventas) use ($handle) {
                        foreach ($ventas as $v) {
                            fputcsv($handle, [
                                '#' . str_pad($v->id, 6, '0', STR_PAD_LEFT),
                                $v->created_at->format('Y-m-d H:i:s'),
                                strtoupper($v->metodo_pago),
                                number_format($v->subtotal, 2, '.', ''),
                                number_format($v->impuestos, 2, '.', ''),
                                number_format($v->total, 2, '.', ''),
                            ]);
                        }
                    });
            } elseif ($tipo === 'compras') {
                fputcsv($handle, ['N° Factura', 'Proveedor', 'Fecha Compra', 'Subtotal ($)', 'IVA ($)', 'Total ($)']);

                Compra::whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
                    ->orderBy('fecha_compra', 'desc')
                    ->chunk(100, function ($compras) use ($handle) {
                        foreach ($compras as $c) {
                            fputcsv($handle, [
                                $c->numero_factura ?: 'N/A',
                                $c->proveedor_nombre ?: 'Proveedor General',
                                $c->fecha_compra ? $c->fecha_compra->format('Y-m-d H:i') : 'N/A',
                                number_format($c->subtotal, 2, '.', ''),
                                number_format($c->iva, 2, '.', ''),
                                number_format($c->total, 2, '.', ''),
                            ]);
                        }
                    });
            } elseif ($tipo === 'inventario') {
                fputcsv($handle, ['Código SKU', 'Descripción', 'Familia', 'Stock Físico', 'Stock Mínimo', 'Diferencia', 'Estado', 'Costo Unitario ($)', 'Valor Total ($)']);

                Articulo::with('familia')->orderBy('descripcion', 'asc')->chunk(100, function ($articulos) use ($handle) {
                    foreach ($articulos as $art) {
                        $stockFisico = (float) $art->stock;
                        $stockMinimo = (float) ($art->stock_minimo ?? 0);
                        $dif = $stockFisico - $stockMinimo;
                        $estado = $stockFisico <= 0 ? 'SIN STOCK' : ($stockFisico <= $stockMinimo ? 'STOCK BAJO' : 'NORMAL');
                        $valor = $stockFisico * (float) $art->precio_sin_iva;

                        fputcsv($handle, [
                            $art->codigo,
                            $art->descripcion,
                            $art->familia ? $art->familia->nombre : 'Sin Familia',
                            number_format($stockFisico, 3, '.', ''),
                            number_format($stockMinimo, 3, '.', ''),
                            number_format($dif, 3, '.', ''),
                            $estado,
                            number_format($art->precio_sin_iva, 2, '.', ''),
                            number_format($valor, 2, '.', ''),
                        ]);
                    }
                });
            }

            fclose($handle);
        }, 200, $headers);
    }
}
