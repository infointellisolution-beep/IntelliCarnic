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

        $totalDevolucionesMonto = (float) \App\Models\Devolucion::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])->sum('total_reembolsado');
        $countDevoluciones = \App\Models\Devolucion::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])->count();
        $totalVentasNeto = max(0, $totalVentasMonto - $totalDevolucionesMonto);

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

        $filtroVenta = $request->get('filtro_venta', 'todas');

        $ventasListaQuery = Venta::with(['detalles.articulo', 'devoluciones.detalles', 'cliente'])
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        if ($filtroVenta === 'contado') {
            $ventasListaQuery->where(function ($q) {
                $q->where('tipo_venta', 'normal')->orWhereNull('tipo_venta');
            })->whereNotIn('estado', ['devuelta', 'parcialmente_devuelta']);
        } elseif ($filtroVenta === 'credito') {
            $ventasListaQuery->where('tipo_venta', 'credito');
        } elseif ($filtroVenta === 'devolucion') {
            $ventasListaQuery->whereIn('estado', ['devuelta', 'parcialmente_devuelta']);
        }

        $ventasLista = $ventasListaQuery->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page_ventas');

        // Mapeo FIFO de estado e imputación de abonos a ventas a crédito
        $clientIds = $ventasLista->pluck('cliente_id')->filter()->unique();
        $clientesAbonosMap = [];

        foreach ($clientIds as $cid) {
            $clienteObj = \App\Models\Cliente::find($cid);
            if (!$clienteObj) continue;

            $totalAbonos = (float) $clienteObj->abonos()->sum('monto');
            $creditSales = $clienteObj->ventas()
                ->where('tipo_venta', 'credito')
                ->where('estado', '!=', 'devuelta')
                ->orderBy('created_at', 'asc')
                ->get();

            $accumulated = $totalAbonos;
            foreach ($creditSales as $cs) {
                $saleTotal = (float) $cs->total;
                if ($accumulated >= $saleTotal) {
                    $clientesAbonosMap[$cs->id] = [
                        'monto_abonado' => $saleTotal,
                        'saldo_pendiente' => 0.0,
                        'estado_credito' => 'saldado'
                    ];
                    $accumulated -= $saleTotal;
                } elseif ($accumulated > 0) {
                    $montoAbonado = $accumulated;
                    $saldoPen = round($saleTotal - $accumulated, 2);
                    $clientesAbonosMap[$cs->id] = [
                        'monto_abonado' => $montoAbonado,
                        'saldo_pendiente' => $saldoPen,
                        'estado_credito' => 'parcial'
                    ];
                    $accumulated = 0;
                } else {
                    $clientesAbonosMap[$cs->id] = [
                        'monto_abonado' => 0.0,
                        'saldo_pendiente' => $saleTotal,
                        'estado_credito' => 'pendiente'
                    ];
                }
            }
        }

        foreach ($ventasLista as $v) {
            if ($v->tipo_venta === 'credito') {
                $v->credito_info = $clientesAbonosMap[$v->id] ?? [
                    'monto_abonado' => 0.0,
                    'saldo_pendiente' => (float) $v->total,
                    'estado_credito' => 'pendiente'
                ];
            }
        }

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

        // 4. Datos para Reporte de Caja
        $cajaQuery = \App\Models\CajaSesion::query()
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        $totalCajaMontoInicial = (float) $cajaQuery->sum('monto_inicial');
        $totalCajaEfectivo = (float) $cajaQuery->sum('total_ventas_efectivo');
        $totalCajaTarjeta = (float) $cajaQuery->sum('total_ventas_tarjeta');
        $totalCajaTransferencia = (float) $cajaQuery->sum('total_ventas_transferencia');
        $totalCajaEntradas = (float) $cajaQuery->sum('total_entradas');
        $totalCajaSalidas = (float) $cajaQuery->sum('total_salidas');
        $totalCajaDiferencia = (float) $cajaQuery->sum('diferencia');
        $numCajasCerradas = (clone $cajaQuery)->where('estado', 'cerrada')->count();
        $numCajasAbiertas = (clone $cajaQuery)->where('estado', 'abierta')->count();

        $cajasLista = \App\Models\CajaSesion::with('user')
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page_caja');

        // 5. Datos para Reporte de Clientes
        $carteraTotal = (float) \App\Models\Cliente::sum('saldo_deudor');
        $creditosOtorgadosPeriodo = (float) Venta::where('tipo_venta', 'credito')
            ->where('estado', '!=', 'devuelta')
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->sum('total');

        $abonosRecaudadosPeriodo = (float) \App\Models\Abono::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->sum('monto');

        $clientesConDeuda = \App\Models\Cliente::where('saldo_deudor', '>', 0)->count();

        // Top 10 Clientes de Mayor Consumo en el período
        $topClientesConsumo = Venta::query()
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->whereNotNull('cliente_id')
            ->where('estado', '!=', 'devuelta')
            ->select('cliente_id', DB::raw('COUNT(*) as total_compras'), DB::raw('SUM(total) as monto_total'))
            ->with('cliente')
            ->groupBy('cliente_id')
            ->orderByDesc('monto_total')
            ->take(10)
            ->get();

        // Estado General de Cartera (Clientes deudores)
        $clientesDeudores = \App\Models\Cliente::where('saldo_deudor', '>', 0)
            ->orderByDesc('saldo_deudor')
            ->paginate(15, ['*'], 'page_deudores');

        // Historial de Abonos Recibidos en el período
        $abonosLista = \App\Models\Abono::with(['cliente', 'user'])
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'page_abonos');

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
            'totalDevolucionesMonto',
            'countDevoluciones',
            'totalVentasNeto',
            'numVentas',
            'promedioVenta',
            'totalPesoVendido',
            'ventasPorMetodo',
            'topProductosVendidos',
            'ventasLista',
            'filtroVenta',
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
            'articulosFiltrados',
            // Caja
            'totalCajaMontoInicial',
            'totalCajaEfectivo',
            'totalCajaTarjeta',
            'totalCajaTransferencia',
            'totalCajaEntradas',
            'totalCajaSalidas',
            'totalCajaDiferencia',
            'numCajasCerradas',
            'numCajasAbiertas',
            'cajasLista',
            // Clientes
            'carteraTotal',
            'creditosOtorgadosPeriodo',
            'abonosRecaudadosPeriodo',
            'clientesConDeuda',
            'topClientesConsumo',
            'clientesDeudores',
            'abonosLista'
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
            } elseif ($tipo === 'caja') {
                fputcsv($handle, ['N° Turno', 'Cajero / Usuario', 'Estado', 'Apertura', 'Cierre', 'Fondo Inicial ($)', 'Vtas. Efectivo ($)', 'Vtas. Tarjeta ($)', 'Vtas. Transferencia ($)', 'Entradas ($)', 'Salidas ($)', 'Saldo Esperado ($)', 'Saldo Real ($)', 'Diferencia ($)', 'Observaciones']);

                \App\Models\CajaSesion::with('user')
                    ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
                    ->orderBy('created_at', 'desc')
                    ->chunk(100, function ($cajas) use ($handle) {
                        foreach ($cajas as $c) {
                            fputcsv($handle, [
                                '#' . $c->id,
                                $c->user ? $c->user->name : 'N/A',
                                strtoupper($c->estado),
                                $c->fecha_apertura ? $c->fecha_apertura->format('Y-m-d H:i') : '',
                                $c->fecha_cierre ? $c->fecha_cierre->format('Y-m-d H:i') : 'ABIERTA',
                                number_format($c->monto_inicial, 2, '.', ''),
                                number_format($c->total_ventas_efectivo, 2, '.', ''),
                                number_format($c->total_ventas_tarjeta, 2, '.', ''),
                                number_format($c->total_ventas_transferencia, 2, '.', ''),
                                number_format($c->total_entradas, 2, '.', ''),
                                number_format($c->total_salidas, 2, '.', ''),
                                number_format($c->saldo_esperado, 2, '.', ''),
                                number_format($c->saldo_real, 2, '.', ''),
                                number_format($c->diferencia, 2, '.', ''),
                                $c->observaciones ?: ''
                            ]);
                        }
                    });
            }

            fclose($handle);
        }, 200, $headers);
    }
}
