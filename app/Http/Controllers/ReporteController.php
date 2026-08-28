<?php

namespace App\Http\Controllers;

use App\Models\AjusteInventario;
use App\Models\Articulo;
use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Devolucion;
use App\Models\DevolucionDetalle;
use App\Models\Familia;
use App\Models\Setting;
use App\Models\Sucursal;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use App\Models\Venta;
use App\Models\VentaDetalle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
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
        $perPage = (int) $request->get('per_page', 15);
        if ($perPage <= 0) $perPage = 15;
        if ($perPage > 99999) $perPage = 99999;

        $settings = Setting::values();
        $unidadPeso = strtoupper($settings['unidad_peso'] ?? 'LB');
        $familias = Familia::orderBy('nombre', 'asc')->get();
        $articulosCatalogo = Articulo::with('familia')->orderBy('descripcion', 'asc')->get();

        // 1. Datos para Reporte de Ventas
        $ventasQuery = Venta::query()
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        $totalVentasMonto = (float) $ventasQuery->sum('total');
        $totalVentasSubtotal = (float) $ventasQuery->sum('subtotal');
        $totalVentasImpuestos = (float) $ventasQuery->sum('impuestos');
        $numVentas = $ventasQuery->count();
        $promedioVenta = $numVentas > 0 ? $totalVentasMonto / $numVentas : 0;

        $totalDevolucionesMonto = (float) Devolucion::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])->sum('total_reembolsado');
        $countDevoluciones = Devolucion::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])->count();
        $totalVentasNeto = max(0, $totalVentasMonto - $totalDevolucionesMonto);

        $totalPesoVendido = (float) VentaDetalle::whereHas('venta', function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
        })->whereHas('articulo', function ($q) {
            $q->where('tipo_articulo', '!=', 'unidad');
        })->sum('cantidad');

        $totalUnidadesVendidas = (float) VentaDetalle::whereHas('venta', function ($q) use ($fechaInicio, $fechaFin) {
            $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
        })->whereHas('articulo', function ($q) {
            $q->where('tipo_articulo', 'unidad');
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
            ->with(['articulo.familia'])
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
            ->paginate($perPage, ['*'], 'page_ventas')
            ->withQueryString();

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
            ->paginate($perPage, ['*'], 'page_compras')
            ->withQueryString();

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

            $valorInversion = $stockFisico * (float) ($art->precio_compra ?: $art->precio_sin_iva);

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
        $articulosFiltradosCol = $articulosProcesados;
        if ($filtroStock !== 'todos') {
            $articulosFiltradosCol = $articulosProcesados->where('estado_evaluado', $filtroStock)->values();
        }

        // Paginación manual de la colección de inventario comparativo
        $pageInv = (int) $request->get('page_inv', 1);
        $articulosFiltrados = new LengthAwarePaginator(
            $articulosFiltradosCol->forPage($pageInv, $perPage),
            $articulosFiltradosCol->count(),
            $perPage,
            $pageInv,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page_inv']
        );
        $articulosFiltrados->withQueryString();

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
            ->paginate($perPage, ['*'], 'page_caja')
            ->withQueryString();

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
            ->paginate($perPage, ['*'], 'page_deudores')
            ->withQueryString();

        // Historial de Abonos Recibidos en el período
        $abonosLista = \App\Models\Abono::with(['cliente', 'user'])
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page_abonos')
            ->withQueryString();

        // 6. Datos para Reporte de Proveedores
        $comprasProvQuery = Compra::query()
            ->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        $totalInversionProveedoresPeriodo = (float) (clone $comprasProvQuery)->sum('total');
        $numRecepcionesPeriodo = (clone $comprasProvQuery)->count();
        $promedioInversionFactura = $numRecepcionesPeriodo > 0 ? $totalInversionProveedoresPeriodo / $numRecepcionesPeriodo : 0;

        $topProveedorPeriodo = Compra::select('proveedor_id', DB::raw('SUM(total) as total_invertido'))
            ->whereNotNull('proveedor_id')
            ->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->groupBy('proveedor_id')
            ->orderByDesc('total_invertido')
            ->with('proveedor')
            ->first();

        // Top 10 Proveedores de Mayor Inversión en el período
        $topProveedoresInversion = Compra::select('proveedor_id', DB::raw('COUNT(*) as total_facturas'), DB::raw('SUM(total) as monto_total'))
            ->whereNotNull('proveedor_id')
            ->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->groupBy('proveedor_id')
            ->orderByDesc('monto_total')
            ->with('proveedor')
            ->take(10)
            ->get();

        // Desglose de Proveedores con Compras en el período
        $comprasPorProveedor = \App\Models\Proveedor::query()
            ->whereHas('compras', function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            })
            ->withCount(['compras' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            }])
            ->withSum(['compras' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            }], 'total')
            ->orderByDesc('compras_sum_total')
            ->paginate($perPage, ['*'], 'page_prov')
            ->withQueryString();

        // Historial General de Recepciones en el Período
        $historialRecepciones = Compra::with(['proveedor', 'user', 'detalles.articulo'])
            ->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->orderBy('fecha_compra', 'desc')
            ->paginate($perPage, ['*'], 'page_recepciones')
            ->withQueryString();

        // 7. Datos para Reporte de KARDEX & Rotación de Inventario
        $articuloId = $request->get('articulo_id');
        $filtroMovimiento = $request->get('filtro_movimiento', 'todos'); // todos, compra, venta, devolucion
        $articuloKardex = $articuloId ? Articulo::with('familia')->find($articuloId) : null;

        $kardexTimeline = collect();
        $stockInicialPeriodo = 0.0;
        $totalKardexEntradas = 0.0;
        $totalKardexSalidas = 0.0;
        $totalKardexDevoluciones = 0.0;
        $stockFinalKardex = 0.0;

        if ($articuloKardex) {
            // 7.1 Stock previo a la fecha de inicio
            $comprasPrevias = (float) CompraDetalle::where('articulo_id', $articuloKardex->id)
                ->whereHas('compra', fn($q) => $q->where('fecha_compra', '<', $fechaInicio . ' 00:00:00'))
                ->sum('cantidad_peso');

            $ventasPrevias = (float) VentaDetalle::where('articulo_id', $articuloKardex->id)
                ->whereHas('venta', fn($q) => $q->where('created_at', '<', $fechaInicio . ' 00:00:00'))
                ->sum('cantidad');

            $devolucionesPrevias = (float) DevolucionDetalle::where('articulo_id', $articuloKardex->id)
                ->where('reingresar_stock', true)
                ->whereHas('devolucion', fn($q) => $q->where('created_at', '<', $fechaInicio . ' 00:00:00'))
                ->sum('cantidad');

            $stockInicialPeriodo = max(0, round($comprasPrevias - $ventasPrevias + $devolucionesPrevias, 3));

            // 7.2 Movimientos dentro del período
            $comprasKardex = CompraDetalle::with(['compra.proveedor', 'compra.user'])
                ->where('articulo_id', $articuloKardex->id)
                ->whereHas('compra', fn($q) => $q->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']))
                ->get()
                ->map(function ($cd) {
                    return [
                        'tipo' => 'compra',
                        'tipo_label' => 'Compra / Entrada',
                        'badge' => 'badge-success',
                        'icon' => 'fa-truck-ramp-box',
                        'color' => '#10b981',
                        'fecha' => $cd->compra->fecha_compra ?? $cd->created_at,
                        'documento' => $cd->compra->numero_factura ? 'Factura #' . $cd->compra->numero_factura : 'Compra #' . $cd->compra_id,
                        'tercero' => $cd->compra->proveedor_nombre ?: ($cd->compra->proveedor->nombre ?? 'Proveedor'),
                        'lote' => $cd->lote,
                        'serie' => $cd->serie,
                        'entrada_qty' => (float) $cd->cantidad_peso,
                        'entrada_costo' => (float) $cd->costo_unitario,
                        'entrada_total' => (float) $cd->subtotal,
                        'salida_qty' => 0.0,
                        'salida_precio' => 0.0,
                        'salida_total' => 0.0,
                        'usuario' => $cd->compra->user->name ?? 'Sistema'
                    ];
                });

            $ventasKardex = VentaDetalle::with(['venta.cliente', 'venta.user'])
                ->where('articulo_id', $articuloKardex->id)
                ->whereHas('venta', fn($q) => $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']))
                ->get()
                ->map(function ($vd) {
                    return [
                        'tipo' => 'venta',
                        'tipo_label' => 'Venta / Salida',
                        'badge' => 'badge-danger',
                        'icon' => 'fa-cash-register',
                        'color' => '#ef4444',
                        'fecha' => $vd->venta->created_at ?? $vd->created_at,
                        'documento' => 'Ticket #' . $vd->venta_id . ($vd->venta->tipo_venta === 'credito' ? ' (Crédito)' : ''),
                        'tercero' => $vd->venta->cliente->nombre ?? 'Consumidor Final',
                        'lote' => null,
                        'serie' => null,
                        'entrada_qty' => 0.0,
                        'entrada_costo' => 0.0,
                        'entrada_total' => 0.0,
                        'salida_qty' => (float) $vd->cantidad,
                        'salida_precio' => (float) $vd->precio_unitario,
                        'salida_total' => (float) $vd->subtotal,
                        'usuario' => $vd->venta->user->name ?? 'Cajero'
                    ];
                });

            $devolucionesKardex = DevolucionDetalle::with(['devolucion.venta', 'devolucion.user'])
                ->where('articulo_id', $articuloKardex->id)
                ->where('reingresar_stock', true)
                ->whereHas('devolucion', fn($q) => $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']))
                ->get()
                ->map(function ($dd) {
                    return [
                        'tipo' => 'devolucion',
                        'tipo_label' => 'Devolución / Reingreso',
                        'badge' => 'badge-warning',
                        'icon' => 'fa-rotate-left',
                        'color' => '#d97706',
                        'fecha' => $dd->devolucion->created_at ?? $dd->created_at,
                        'documento' => 'Devolución #' . $dd->devolucion_id . ' (Ref: Ticket #' . $dd->devolucion->venta_id . ')',
                        'tercero' => $dd->devolucion->motivo ?: 'Cliente',
                        'lote' => null,
                        'serie' => null,
                        'entrada_qty' => (float) $dd->cantidad,
                        'entrada_costo' => (float) $dd->precio_unitario,
                        'entrada_total' => (float) $dd->subtotal,
                        'salida_qty' => 0.0,
                        'salida_precio' => 0.0,
                        'salida_total' => 0.0,
                        'usuario' => $dd->devolucion->user->name ?? 'Cajero'
                    ];
                });

            // Unificar y ordenar cronológicamente de forma ascendente para calcular saldo acumulado
            $todosMovimientos = $comprasKardex->concat($ventasKardex)->concat($devolucionesKardex)
                ->sortBy(function ($m) {
                    return \Carbon\Carbon::parse($m['fecha'])->timestamp;
                })->values();

            $runningStock = $stockInicialPeriodo;
            $costoRef = (float) ($articuloKardex->precio_compra ?: $articuloKardex->precio_sin_iva);

            $kardexTimeline = $todosMovimientos->map(function ($m) use (&$runningStock, $costoRef, &$totalKardexEntradas, &$totalKardexSalidas, &$totalKardexDevoluciones) {
                if ($m['tipo'] === 'compra') {
                    $runningStock += $m['entrada_qty'];
                    $totalKardexEntradas += $m['entrada_qty'];
                } elseif ($m['tipo'] === 'devolucion') {
                    $runningStock += $m['entrada_qty'];
                    $totalKardexDevoluciones += $m['entrada_qty'];
                } else {
                    $runningStock -= $m['salida_qty'];
                    $totalKardexSalidas += $m['salida_qty'];
                }

                $m['saldo_stock'] = round($runningStock, 3);
                $m['saldo_valorado'] = round($runningStock * $costoRef, 2);
                return $m;
            });

            $stockFinalKardex = round($runningStock, 3);

            if ($filtroMovimiento !== 'todos') {
                $kardexTimeline = $kardexTimeline->where('tipo', $filtroMovimiento)->values();
            }
        }

        // Paginación del Kardex
        $pageKardex = (int) $request->get('page_kardex', 1);
        $kardexLista = new LengthAwarePaginator(
            $kardexTimeline->forPage($pageKardex, $perPage),
            $kardexTimeline->count(),
            $perPage,
            $pageKardex,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page_kardex']
        );
        $kardexLista->withQueryString();

        // 7.3 Análisis de Rotación de Inventario de todos los productos
        $diasPeriodo = max(1, (int) \Carbon\Carbon::parse($fechaInicio)->diffInDays(\Carbon\Carbon::parse($fechaFin)) + 1);

        $rotacionProductosCol = $articulosCatalogo->map(function ($art) use ($fechaInicio, $fechaFin, $diasPeriodo) {
            $comprado = (float) CompraDetalle::where('articulo_id', $art->id)
                ->whereHas('compra', fn($q) => $q->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']))
                ->sum('cantidad_peso');

            $vendido = (float) VentaDetalle::where('articulo_id', $art->id)
                ->whereHas('venta', fn($q) => $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']))
                ->sum('cantidad');

            $devuelto = (float) DevolucionDetalle::where('articulo_id', $art->id)
                ->where('reingresar_stock', true)
                ->whereHas('devolucion', fn($q) => $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']))
                ->sum('cantidad');

            $ventaNeta = max(0, round($vendido - $devuelto, 3));
            $stockActual = (float) $art->stock;
            $baseTotal = $comprado + $stockActual;

            $rotacionPct = $baseTotal > 0 ? round(($ventaNeta / $baseTotal) * 100, 1) : ($ventaNeta > 0 ? 100.0 : 0.0);

            if ($rotacionPct >= 60 || ($ventaNeta >= 20 && $stockActual <= $ventaNeta)) {
                $categoria = 'alta';
                $categoriaLabel = '🔥 Alta Rotación';
                $badgeClass = 'badge-success';
            } elseif ($rotacionPct >= 20 || $ventaNeta > 0) {
                $categoria = 'media';
                $categoriaLabel = '⚡ Media Rotación';
                $badgeClass = 'badge-warning';
            } else {
                $categoria = 'baja';
                $categoriaLabel = '❄️ Baja / Estancado';
                $badgeClass = 'badge-danger';
            }

            $ventaDiariaPromedio = $ventaNeta / $diasPeriodo;
            $diasCobertura = $ventaDiariaPromedio > 0 ? round($stockActual / $ventaDiariaPromedio, 0) : ($stockActual > 0 ? 999 : 0);

            $art->total_comprado_periodo = $comprado;
            $art->total_vendido_periodo = $vendido;
            $art->total_devuelto_periodo = $devuelto;
            $art->venta_neta_periodo = $ventaNeta;
            $art->stock_actual_num = $stockActual;
            $art->rotacion_pct = $rotacionPct;
            $art->rotacion_categoria = $categoria;
            $art->rotacion_label = $categoriaLabel;
            $art->rotacion_badge = $badgeClass;
            $art->dias_cobertura = $diasCobertura;
            $art->valor_stock_actual = round($stockActual * (float) ($art->precio_compra ?: $art->precio_sin_iva), 2);

            return $art;
        })->sortByDesc('venta_neta_periodo')->values();

        $pageRot = (int) $request->get('page_rot', 1);
        $rotacionProductos = new LengthAwarePaginator(
            $rotacionProductosCol->forPage($pageRot, $perPage),
            $rotacionProductosCol->count(),
            $perPage,
            $pageRot,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page_rot']
        );
        $rotacionProductos->withQueryString();

        $articulosKardexJson = $articulosCatalogo->map(function ($a) use ($unidadPeso) {
            return [
                'id' => $a->id,
                'descripcion' => $a->descripcion,
                'codigo' => (string) ($a->codigo ?? ''),
                'codigo_cliente' => (string) ($a->codigo_cliente ?? ''),
                'item' => (string) ($a->item ?? ''),
                'tipo_articulo' => $a->tipo_articulo ?? 'pesable',
                'stock' => (float) $a->stock,
                'unidad' => ($a->tipo_articulo === 'unidad') ? 'UND' : $unidadPeso,
                'familia' => $a->familia?->nombre ?? ''
            ];
        })->values();

        // 8. Datos para Reporte de Ajustes de Inventario
        $filtroTipoAjuste = $request->get('tipo_ajuste', 'todos');
        $filtroOrigen = $request->get('origen', 'todos');
        $modoInventario = $settings['modo_inventario'] ?? 'dinamico';

        $ajustesQueryBase = AjusteInventario::query()
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        if ($articuloId) {
            $ajustesQueryBase->where('articulo_id', $articuloId);
        }
        if ($filtroTipoAjuste && $filtroTipoAjuste !== 'todos') {
            $ajustesQueryBase->where('tipo_ajuste', $filtroTipoAjuste);
        }
        if ($filtroOrigen && $filtroOrigen !== 'todos') {
            $ajustesQueryBase->where('origen', $filtroOrigen);
        }

        // KPIs de Ajustes
        $totalAjustesConteo = (int) (clone $ajustesQueryBase)->count();
        $totalAjustesPositivo = (float) (clone $ajustesQueryBase)->where('diferencia_stock', '>', 0)->sum('diferencia_stock');
        $totalAjustesNegativo = (float) abs((clone $ajustesQueryBase)->where('diferencia_stock', '<', 0)->sum('diferencia_stock'));
        $totalAjustesNeto = (float) (clone $ajustesQueryBase)->sum('diferencia_stock');
        $ajustesHandheldCount = (int) (clone $ajustesQueryBase)->where('origen', 'handheld')->count();
        $ajustesWebCount = (int) (clone $ajustesQueryBase)->where('origen', 'web')->count();

        // Lista paginada de ajustes
        $ajustesLista = (clone $ajustesQueryBase)
            ->with(['articulo.familia', 'compraDetalle', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page_ajustes');
        $ajustesLista->withQueryString();

        $articuloAjuste = $articuloId ? Articulo::find($articuloId) : null;

        // 9. Datos para Reporte de Beneficios y Rentabilidad (Utilidad & COGS)
        $ventasValidas = Venta::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->whereNotIn('estado', ['devuelta'])
            ->with(['detalles.articulo.familia'])
            ->get();

        $devolucionesPeriodo = Devolucion::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->with(['detalles.articulo.familia'])
            ->get();

        $beneficioVentasBruto = 0.0;
        $beneficioCostoVentas = 0.0;
        $productosRentabilidadMap = [];
        $familiasRentabilidadMap = [];

        foreach ($ventasValidas as $v) {
            foreach ($v->detalles as $d) {
                $art = $d->articulo;
                if (!$art) continue;

                $cant = (float) $d->cantidad;
                $lineVenta = (float) $d->subtotal;
                $costoUnit = (float) ($art->precio_compra ?: $art->precio_sin_iva ?: 0);
                $lineCosto = $cant * $costoUnit;

                $beneficioVentasBruto += $lineVenta;
                $beneficioCostoVentas += $lineCosto;

                $artId = $art->id;
                if (!isset($productosRentabilidadMap[$artId])) {
                    $productosRentabilidadMap[$artId] = [
                        'articulo' => $art,
                        'cantidad_vendida' => 0.0,
                        'total_venta' => 0.0,
                        'total_costo' => 0.0,
                    ];
                }
                $productosRentabilidadMap[$artId]['cantidad_vendida'] += $cant;
                $productosRentabilidadMap[$artId]['total_venta'] += $lineVenta;
                $productosRentabilidadMap[$artId]['total_costo'] += $lineCosto;

                $famId = $art->familia_id ?? 0;
                $famNombre = $art->familia?->nombre ?? 'Sin Familia';
                if (!isset($familiasRentabilidadMap[$famId])) {
                    $familiasRentabilidadMap[$famId] = [
                        'familia_nombre' => $famNombre,
                        'total_venta' => 0.0,
                        'total_costo' => 0.0,
                    ];
                }
                $familiasRentabilidadMap[$famId]['total_venta'] += $lineVenta;
                $familiasRentabilidadMap[$famId]['total_costo'] += $lineCosto;
            }
        }

        // Restar devoluciones
        $beneficioDevolucionesMonto = 0.0;
        $beneficioDevolucionesCosto = 0.0;

        foreach ($devolucionesPeriodo as $dev) {
            foreach ($dev->detalles as $dd) {
                $art = $dd->articulo;
                if (!$art) continue;

                $cant = (float) $dd->cantidad;
                $lineDevVenta = (float) $dd->subtotal;
                $costoUnit = (float) ($art->precio_compra ?: $art->precio_sin_iva ?: 0);
                $lineDevCosto = $cant * $costoUnit;

                $beneficioDevolucionesMonto += $lineDevVenta;
                $beneficioDevolucionesCosto += $lineDevCosto;

                $artId = $art->id;
                if (isset($productosRentabilidadMap[$artId])) {
                    $productosRentabilidadMap[$artId]['cantidad_vendida'] = max(0, $productosRentabilidadMap[$artId]['cantidad_vendida'] - $cant);
                    $productosRentabilidadMap[$artId]['total_venta'] = max(0, $productosRentabilidadMap[$artId]['total_venta'] - $lineDevVenta);
                    $productosRentabilidadMap[$artId]['total_costo'] = max(0, $productosRentabilidadMap[$artId]['total_costo'] - $lineDevCosto);
                }

                $famId = $art->familia_id ?? 0;
                if (isset($familiasRentabilidadMap[$famId])) {
                    $familiasRentabilidadMap[$famId]['total_venta'] = max(0, $familiasRentabilidadMap[$famId]['total_venta'] - $lineDevVenta);
                    $familiasRentabilidadMap[$famId]['total_costo'] = max(0, $familiasRentabilidadMap[$famId]['total_costo'] - $lineDevCosto);
                }
            }
        }

        $beneficioVentasNeto = max(0, $beneficioVentasBruto - $beneficioDevolucionesMonto);
        $beneficioCostoNeto = max(0, $beneficioCostoVentas - $beneficioDevolucionesCosto);
        $beneficioGananciaBruta = round($beneficioVentasNeto - $beneficioCostoNeto, 2);
        $beneficioMargenPct = $beneficioVentasNeto > 0 ? round(($beneficioGananciaBruta / $beneficioVentasNeto) * 100, 1) : 0.0;

        // Pérdida por mermas y ajustes negativos
        $mermasPeriodo = AjusteInventario::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->where('diferencia_stock', '<', 0)
            ->with('articulo')
            ->get();

        $beneficioPerdidaMermas = 0.0;
        foreach ($mermasPeriodo as $m) {
            $costoUnit = (float) ($m->articulo?->precio_compra ?: $m->articulo?->precio_sin_iva ?: 0);
            $beneficioPerdidaMermas += abs((float)$m->diferencia_stock) * $costoUnit;
        }
        $beneficioPerdidaMermas = round($beneficioPerdidaMermas, 2);
        $beneficioGananciaAjustada = round($beneficioGananciaBruta - $beneficioPerdidaMermas, 2);

        // Procesar colección de productos con rentabilidad
        $rentabilidadProductosCol = collect($productosRentabilidadMap)->map(function ($item) {
            $art = $item['articulo'];
            $vNet = (float) $item['total_venta'];
            $cNet = (float) $item['total_costo'];
            $ganancia = round($vNet - $cNet, 2);
            $margenPct = $vNet > 0 ? round(($ganancia / $vNet) * 100, 1) : 0.0;
            $cant = (float) $item['cantidad_vendida'];
            $margenUnitario = $cant > 0 ? round($ganancia / $cant, 2) : 0.0;

            if ($margenPct >= 35) {
                $badgeClass = 'badge-success';
                $badgeLabel = '🟢 Alto Margen';
                $categoriaMargen = 'alto';
            } elseif ($margenPct >= 15) {
                $badgeClass = 'badge-warning';
                $badgeLabel = '🟡 Margen Medio';
                $categoriaMargen = 'medio';
            } else {
                $badgeClass = 'badge-danger';
                $badgeLabel = '🔴 Bajo Margen';
                $categoriaMargen = 'bajo';
            }

            return (object) [
                'id' => $art->id,
                'descripcion' => $art->descripcion,
                'codigo' => $art->codigo,
                'tipo_articulo' => $art->tipo_articulo ?? 'pesable',
                'unidad_simbolo' => $art->isUnidad() ? 'UND' : ($art->unidad_simbolo ?? 'LB'),
                'familia_id' => $art->familia_id ?? 0,
                'familia_nombre' => $art->familia?->nombre ?? 'Sin Familia',
                'cantidad_vendida' => $cant,
                'total_venta' => $vNet,
                'total_costo' => $cNet,
                'ganancia_bruta' => $ganancia,
                'margen_pct' => $margenPct,
                'margen_unitario' => $margenUnitario,
                'badge_class' => $badgeClass,
                'badge_label' => $badgeLabel,
                'categoria_margen' => $categoriaMargen,
            ];
        })->sortByDesc('ganancia_bruta')->values();

        $pageBeneficios = (int) $request->get('page_beneficios', 1);
        $rentabilidadProductos = new LengthAwarePaginator(
            $rentabilidadProductosCol->forPage($pageBeneficios, $perPage),
            $rentabilidadProductosCol->count(),
            $perPage,
            $pageBeneficios,
            ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page_beneficios']
        );
        $rentabilidadProductos->withQueryString();

        // Procesar rentabilidad por familias
        $rentabilidadFamilias = collect($familiasRentabilidadMap)->map(function ($fam) use ($beneficioGananciaBruta) {
            $v = (float) $fam['total_venta'];
            $c = (float) $fam['total_costo'];
            $g = round($v - $c, 2);
            $mPct = $v > 0 ? round(($g / $v) * 100, 1) : 0.0;
            $contribucion = $beneficioGananciaBruta > 0 ? round(($g / $beneficioGananciaBruta) * 100, 1) : 0.0;

            return (object) [
                'familia_nombre' => $fam['familia_nombre'],
                'total_venta' => $v,
                'total_costo' => $c,
                'ganancia_bruta' => $g,
                'margen_pct' => $mPct,
                'contribucion_pct' => max(0, $contribucion),
            ];
        })->sortByDesc('ganancia_bruta')->values();

        // 9. Datos para Reporte de Transferencias Multisucursal
        $sucursalesList = Sucursal::where('activo', true)->orderBy('nombre', 'asc')->get();
        $sucursalId = $request->get('sucursal_id');
        $tipoFlujo = $request->get('tipo_flujo', 'todos'); // todos, envios, recepciones
        $estadoTransferencia = $request->get('estado_transferencia', 'todos'); // todos, en_transito, recibida, cancelada

        $transQuery = Transferencia::query()
            ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

        if ($sucursalId) {
            if ($tipoFlujo === 'envios') {
                $transQuery->where('sucursal_origen_id', $sucursalId);
            } elseif ($tipoFlujo === 'recepciones') {
                $transQuery->where('sucursal_destino_id', $sucursalId);
            } else {
                $transQuery->where(function ($q) use ($sucursalId) {
                    $q->where('sucursal_origen_id', $sucursalId)
                      ->orWhere('sucursal_destino_id', $sucursalId);
                });
            }
        }

        if ($estadoTransferencia !== 'todos') {
            $transQuery->where('estado', $estadoTransferencia);
        }

        $totalTransCount = (clone $transQuery)->count();
        $totalTransPeso = (float) (clone $transQuery)->sum('total_peso');
        $totalTransUnidades = (int) (clone $transQuery)->sum('total_unidades');
        $totalTransCosto = (float) (clone $transQuery)->sum('costo_total');
        $transRecibidasCount = (clone $transQuery)->where('estado', 'recibida')->count();
        $transEnTransitoCount = (clone $transQuery)->where('estado', 'en_transito')->count();
        $transCanceladasCount = (clone $transQuery)->where('estado', 'cancelada')->count();
        $transTasaEfectividad = $totalTransCount > 0 ? round(($transRecibidasCount / $totalTransCount) * 100, 1) : 100;

        // Top Productos Más Transferidos
        $topArticulosTransferidos = TransferenciaDetalle::whereHas('transferencia', function ($q) use ($fechaInicio, $fechaFin, $sucursalId, $tipoFlujo, $estadoTransferencia) {
            $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);
            if ($sucursalId) {
                if ($tipoFlujo === 'envios') {
                    $q->where('sucursal_origen_id', $sucursalId);
                } elseif ($tipoFlujo === 'recepciones') {
                    $q->where('sucursal_destino_id', $sucursalId);
                } else {
                    $q->where(function ($sub) use ($sucursalId) {
                        $sub->where('sucursal_origen_id', $sucursalId)->orWhere('sucursal_destino_id', $sucursalId);
                    });
                }
            }
            if ($estadoTransferencia !== 'todos') {
                $q->where('estado', $estadoTransferencia);
            }
        })
            ->select(
                'articulo_id',
                'descripcion',
                'tipo_articulo',
                'unidad_medida',
                DB::raw('SUM(cantidad_enviada) as total_cantidad'),
                DB::raw('SUM(subtotal_costo) as total_costo'),
                DB::raw('COUNT(DISTINCT transferencia_id) as total_envios')
            )
            ->with(['articulo.familia'])
            ->groupBy('articulo_id', 'descripcion', 'tipo_articulo', 'unidad_medida')
            ->orderByDesc('total_cantidad')
            ->take(15)
            ->get()
            ->map(function ($item) {
                return [
                    'articulo_id' => $item->articulo_id,
                    'descripcion' => $item->descripcion,
                    'tipo_articulo' => $item->tipo_articulo,
                    'unidad_medida' => $item->unidad_medida,
                    'familia_id' => $item->articulo?->familia_id ?? 0,
                    'familia_nombre' => $item->articulo?->familia?->nombre ?? 'Sin Familia',
                    'total_cantidad' => (float) $item->total_cantidad,
                    'total_costo' => (float) $item->total_costo,
                    'total_envios' => (int) $item->total_envios,
                ];
            });

        // Flujo y Matriz de Rutas entre Sucursales
        $flujoSucursales = Transferencia::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
            ->select(
                'sucursal_origen_id',
                'sucursal_destino_id',
                DB::raw('COUNT(*) as total_transferencias'),
                DB::raw('SUM(total_peso) as suma_peso'),
                DB::raw('SUM(total_unidades) as suma_unidades'),
                DB::raw('SUM(costo_total) as suma_costo')
            )
            ->with(['sucursalOrigen', 'sucursalDestino'])
            ->groupBy('sucursal_origen_id', 'sucursal_destino_id')
            ->orderByDesc('suma_costo')
            ->get();

        $totalFlujoCostoGeneral = (float) $flujoSucursales->sum('suma_costo');

        // Listado Paginado de Transferencias
        $transferenciasLista = (clone $transQuery)
            ->with(['sucursalOrigen', 'sucursalDestino', 'usuario', 'usuarioRecibe', 'detalles.articulo'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page_transferencias')
            ->withQueryString();

        return view('reportes.index', compact(
            'tab',
            'fechaInicio',
            'fechaFin',
            'familiaId',
            'filtroStock',
            'unidadPeso',
            'modoInventario',
            'familias',
            'articulosCatalogo',
            'articulosKardexJson',
            'perPage',
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
            'totalUnidadesVendidas',
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
            'abonosLista',
            // Proveedores
            'totalInversionProveedoresPeriodo',
            'numRecepcionesPeriodo',
            'promedioInversionFactura',
            'topProveedorPeriodo',
            'topProveedoresInversion',
            'comprasPorProveedor',
            'historialRecepciones',
            // Kardex & Rotacion
            'articuloId',
            'filtroMovimiento',
            'articuloKardex',
            'stockInicialPeriodo',
            'totalKardexEntradas',
            'totalKardexSalidas',
            'totalKardexDevoluciones',
            'stockFinalKardex',
            'kardexLista',
            'rotacionProductos',
            // Ajustes
            'filtroTipoAjuste',
            'filtroOrigen',
            'totalAjustesConteo',
            'totalAjustesPositivo',
            'totalAjustesNegativo',
            'totalAjustesNeto',
            'ajustesHandheldCount',
            'ajustesWebCount',
            'ajustesLista',
            'articuloAjuste',
            // Beneficios & Rentabilidad
            'beneficioVentasNeto',
            'beneficioCostoNeto',
            'beneficioGananciaBruta',
            'beneficioMargenPct',
            'beneficioPerdidaMermas',
            'beneficioGananciaAjustada',
            'rentabilidadProductos',
            'rentabilidadProductosCol',
            'rentabilidadFamilias',
            // Transferencias
            'sucursalesList',
            'sucursalId',
            'tipoFlujo',
            'estadoTransferencia',
            'totalTransCount',
            'totalTransPeso',
            'totalTransUnidades',
            'totalTransCosto',
            'transRecibidasCount',
            'transEnTransitoCount',
            'transCanceladasCount',
            'transTasaEfectividad',
            'topArticulosTransferidos',
            'flujoSucursales',
            'totalFlujoCostoGeneral',
            'transferenciasLista'
        ));
    }

    public function exportarCsv(Request $request, string $tipo): StreamedResponse
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth()->toDateString());
        $fechaFin = $request->get('fecha_fin', now()->toDateString());
        $articuloId = $request->get('articulo_id');
        $filename = "Reporte_{$tipo}_" . date('Y-m-d_H-i') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        return response()->stream(function () use ($tipo, $fechaInicio, $fechaFin, $articuloId, $request) {
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
                        $valor = $stockFisico * (float) ($art->precio_compra ?: $art->precio_sin_iva);

                        fputcsv($handle, [
                            $art->codigo,
                            $art->descripcion,
                            $art->familia ? $art->familia->nombre : 'Sin Familia',
                            number_format($stockFisico, 3, '.', ''),
                            number_format($stockMinimo, 3, '.', ''),
                            number_format($dif, 3, '.', ''),
                            $estado,
                            number_format((float)($art->precio_compra ?: $art->precio_sin_iva), 2, '.', ''),
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
            } elseif ($tipo === 'kardex') {
                fputcsv($handle, ['Fecha / Hora', 'Tipo Movimiento', 'Documento / Referencia', 'Tercero (Proveedor / Cliente)', 'Lote / Serie', 'Entrada Cant/Peso', 'Entrada Costo ($)', 'Entrada Total ($)', 'Salida Cant/Peso', 'Salida Precio ($)', 'Salida Total ($)', 'Saldo Stock']);

                if ($articuloId) {
                    $art = Articulo::find($articuloId);
                    if ($art) {
                        $compras = CompraDetalle::with('compra')
                            ->where('articulo_id', $art->id)
                            ->whereHas('compra', fn($q) => $q->whereBetween('fecha_compra', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']))
                            ->get()->map(fn($cd) => [
                                'fecha' => $cd->compra->fecha_compra ?? $cd->created_at,
                                'tipo' => 'COMPRA',
                                'doc' => $cd->compra->numero_factura ? 'Factura #' . $cd->compra->numero_factura : 'Compra #' . $cd->compra_id,
                                'tercero' => $cd->compra->proveedor_nombre ?: 'Proveedor',
                                'lote_serie' => ($cd->lote ? 'Lote: ' . $cd->lote : '') . ($cd->serie ? ' Serie: ' . $cd->serie : ''),
                                'ent_qty' => (float)$cd->cantidad_peso,
                                'ent_cost' => (float)$cd->costo_unitario,
                                'ent_tot' => (float)$cd->subtotal,
                                'sal_qty' => 0, 'sal_price' => 0, 'sal_tot' => 0
                            ]);

                        $ventas = VentaDetalle::with('venta.cliente')
                            ->where('articulo_id', $art->id)
                            ->whereHas('venta', fn($q) => $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']))
                            ->get()->map(fn($vd) => [
                                'fecha' => $vd->venta->created_at ?? $vd->created_at,
                                'tipo' => 'VENTA',
                                'doc' => 'Ticket #' . $vd->venta_id,
                                'tercero' => $vd->venta->cliente->nombre ?? 'Consumidor Final',
                                'lote_serie' => '',
                                'ent_qty' => 0, 'ent_cost' => 0, 'ent_tot' => 0,
                                'sal_qty' => (float)$vd->cantidad,
                                'sal_price' => (float)$vd->precio_unitario,
                                'sal_tot' => (float)$vd->subtotal
                            ]);

                        $devs = DevolucionDetalle::with('devolucion')
                            ->where('articulo_id', $art->id)->where('reingresar_stock', true)
                            ->whereHas('devolucion', fn($q) => $q->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']))
                            ->get()->map(fn($dd) => [
                                'fecha' => $dd->devolucion->created_at ?? $dd->created_at,
                                'tipo' => 'DEVOLUCION',
                                'doc' => 'Devolución #' . $dd->devolucion_id,
                                'tercero' => $dd->devolucion->motivo ?: 'Cliente',
                                'lote_serie' => '',
                                'ent_qty' => (float)$dd->cantidad,
                                'ent_cost' => (float)$dd->precio_unitario,
                                'ent_tot' => (float)$dd->subtotal,
                                'sal_qty' => 0, 'sal_price' => 0, 'sal_tot' => 0
                            ]);

                        $todos = $compras->concat($ventas)->concat($devs)->sortBy(fn($m) => \Carbon\Carbon::parse($m['fecha'])->timestamp)->values();

                        $stockPrevio = (float) CompraDetalle::where('articulo_id', $art->id)->whereHas('compra', fn($q) => $q->where('fecha_compra', '<', $fechaInicio . ' 00:00:00'))->sum('cantidad_peso')
                            - (float) VentaDetalle::where('articulo_id', $art->id)->whereHas('venta', fn($q) => $q->where('created_at', '<', $fechaInicio . ' 00:00:00'))->sum('cantidad')
                            + (float) DevolucionDetalle::where('articulo_id', $art->id)->where('reingresar_stock', true)->whereHas('devolucion', fn($q) => $q->where('created_at', '<', $fechaInicio . ' 00:00:00'))->sum('cantidad');

                        $running = max(0, round($stockPrevio, 3));
                        foreach ($todos as $mov) {
                            if ($mov['tipo'] === 'COMPRA' || $mov['tipo'] === 'DEVOLUCION') {
                                $running += $mov['ent_qty'];
                            } else {
                                $running -= $mov['sal_qty'];
                            }
                            fputcsv($handle, [
                                \Carbon\Carbon::parse($mov['fecha'])->format('Y-m-d H:i:s'),
                                $mov['tipo'],
                                $mov['doc'],
                                $mov['tercero'],
                                $mov['lote_serie'],
                                number_format($mov['ent_qty'], 3, '.', ''),
                                number_format($mov['ent_cost'], 2, '.', ''),
                                number_format($mov['ent_tot'], 2, '.', ''),
                                number_format($mov['sal_qty'], 3, '.', ''),
                                number_format($mov['sal_price'], 2, '.', ''),
                                number_format($mov['sal_tot'], 2, '.', ''),
                                number_format($running, 3, '.', ''),
                            ]);
                        }
                    }
                }
            } elseif ($tipo === 'ajustes') {
                fputcsv($handle, [
                    'Fecha y Hora',
                    'Origen',
                    'Usuario',
                    'Código Artículo',
                    'Descripción Artículo',
                    'Familia',
                    'Modo Inventario',
                    'Lote',
                    'Serie',
                    'Tipo de Ajuste',
                    'Stock Anterior',
                    'Cantidad Ajustada',
                    'Stock Resultante',
                    'Diferencia Neta',
                    'Motivo / Observación'
                ]);

                $filtroTipoAjuste = $request->get('tipo_ajuste', 'todos');
                $filtroOrigen = $request->get('origen', 'todos');

                $ajustesQuery = AjusteInventario::with(['articulo.familia', 'user'])
                    ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

                if ($articuloId) {
                    $ajustesQuery->where('articulo_id', $articuloId);
                }
                if ($filtroTipoAjuste && $filtroTipoAjuste !== 'todos') {
                    $ajustesQuery->where('tipo_ajuste', $filtroTipoAjuste);
                }
                if ($filtroOrigen && $filtroOrigen !== 'todos') {
                    $ajustesQuery->where('origen', $filtroOrigen);
                }

                $ajustes = $ajustesQuery->orderBy('created_at', 'desc')->get();

                foreach ($ajustes as $aj) {
                    fputcsv($handle, [
                        $aj->created_at->format('Y-m-d H:i:s'),
                        strtoupper($aj->origen),
                        $aj->user?->name ?? 'Sistema / Handheld',
                        $aj->articulo?->codigo ?? '',
                        $aj->articulo?->descripcion ?? '',
                        $aj->articulo?->familia?->nombre ?? '',
                        $aj->modo_inventario === 'dinamico' ? 'Dinámico (Por Lotes)' : 'Simple (General)',
                        $aj->lote ?: 'N/A (General)',
                        $aj->serie ?: 'N/A',
                        strtoupper($aj->tipo_ajuste),
                        number_format((float) $aj->stock_anterior, 3, '.', ''),
                        number_format((float) $aj->cantidad_ajustada, 3, '.', ''),
                        number_format((float) $aj->stock_nuevo, 3, '.', ''),
                        ($aj->diferencia_stock >= 0 ? '+' : '') . number_format((float) $aj->diferencia_stock, 3, '.', ''),
                        $aj->motivo ?? ''
                    ]);
                }
            } elseif ($tipo === 'beneficios') {
                fputcsv($handle, ['Producto / Descripción', 'Código / SKU', 'Familia', 'Cantidad Vendida', 'Unidad', 'Ventas Netas ($)', 'Costo Total ($)', 'Ganancia Bruta ($)', 'Margen (%)', 'Margen Unitario ($)']);

                $ventasValidas = Venta::whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'])
                    ->whereNotIn('estado', ['devuelta'])
                    ->with(['detalles.articulo.familia'])
                    ->get();

                $prodMap = [];
                foreach ($ventasValidas as $v) {
                    foreach ($v->detalles as $d) {
                        $art = $d->articulo;
                        if (!$art) continue;
                        $artId = $art->id;
                        $cant = (float) $d->cantidad;
                        $vTot = (float) $d->subtotal;
                        $cUnit = (float) ($art->precio_compra ?: $art->precio_sin_iva ?: 0);
                        $cTot = $cant * $cUnit;

                        if (!isset($prodMap[$artId])) {
                            $prodMap[$artId] = [
                                'articulo' => $art,
                                'cant' => 0.0,
                                'venta' => 0.0,
                                'costo' => 0.0,
                            ];
                        }
                        $prodMap[$artId]['cant'] += $cant;
                        $prodMap[$artId]['venta'] += $vTot;
                        $prodMap[$artId]['costo'] += $cTot;
                    }
                }

                foreach ($prodMap as $item) {
                    $art = $item['articulo'];
                    $vTot = $item['venta'];
                    $cTot = $item['costo'];
                    $ganancia = round($vTot - $cTot, 2);
                    $mPct = $vTot > 0 ? round(($ganancia / $vTot) * 100, 1) : 0.0;
                    $mUnit = $item['cant'] > 0 ? round($ganancia / $item['cant'], 2) : 0.0;
                    $uSym = ($art->tipo_articulo === 'unidad') ? 'UND' : 'LB';

                    fputcsv($handle, [
                        $art->descripcion,
                        $art->codigo,
                        $art->familia?->nombre ?? 'Sin Familia',
                        number_format($item['cant'], ($art->tipo_articulo === 'unidad' ? 0 : 3), '.', ''),
                        $uSym,
                        number_format($vTot, 2, '.', ''),
                        number_format($cTot, 2, '.', ''),
                        number_format($ganancia, 2, '.', ''),
                        number_format($mPct, 1, '.', '') . '%',
                        number_format($mUnit, 2, '.', ''),
                    ]);
                }
            } elseif ($tipo === 'transferencias') {
                fputcsv($handle, [
                    'Folio',
                    'Fecha Envío',
                    'Sucursal Origen',
                    'Sucursal Destino',
                    'Usuario Emisor',
                    'Usuario Receptor',
                    'Estado',
                    'Tipo Sincronización',
                    'Total Peso (LB)',
                    'Total Unidades',
                    'Costo Total ($)',
                    'Fecha Recepción',
                    'Notas'
                ]);

                $query = Transferencia::with(['sucursalOrigen', 'sucursalDestino', 'usuario', 'usuarioRecibe'])
                    ->whereBetween('created_at', [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59']);

                $sucId = $request->get('sucursal_id');
                $flujo = $request->get('tipo_flujo', 'todos');
                $est = $request->get('estado_transferencia', 'todos');

                if ($sucId) {
                    if ($flujo === 'envios') {
                        $query->where('sucursal_origen_id', $sucId);
                    } elseif ($flujo === 'recepciones') {
                        $query->where('sucursal_destino_id', $sucId);
                    } else {
                        $query->where(function ($q) use ($sucId) {
                            $q->where('sucursal_origen_id', $sucId)->orWhere('sucursal_destino_id', $sucId);
                        });
                    }
                }

                if ($est !== 'todos') {
                    $query->where('estado', $est);
                }

                $query->orderBy('created_at', 'desc')->chunk(100, function ($transferencias) use ($handle) {
                    foreach ($transferencias as $t) {
                        fputcsv($handle, [
                            $t->folio,
                            $t->fecha_envio ? $t->fecha_envio->format('Y-m-d H:i') : $t->created_at->format('Y-m-d H:i'),
                            $t->sucursalOrigen->nombre ?? 'N/A',
                            $t->sucursalDestino->nombre ?? 'N/A',
                            $t->usuario->name ?? 'N/A',
                            $t->usuarioRecibe->name ?? 'N/A',
                            strtoupper($t->estado),
                            $t->tipo_sincronizacion === 'cloud' ? 'NUBE' : 'MANUAL (.TRN)',
                            number_format((float) $t->total_peso, 3, '.', ''),
                            $t->total_unidades,
                            number_format((float) $t->costo_total, 2, '.', ''),
                            $t->fecha_recepcion ? $t->fecha_recepcion->format('Y-m-d H:i') : 'PENDIENTE',
                            $t->notas ?? '',
                        ]);
                    }
                });
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * API para obtener datos dinámicos de gráficos comparativos
     * Permite drill-down / drill-up: Anual (Meses) <-> Mensual (Días) <-> Semanal (Día a Día)
     * Permite seleccionar cualquier mes o semana específica a comparar.
     */
    public function apiGraficoComparativo(Request $request)
    {
        $tipo = $request->get('tipo', 'ventas'); // 'ventas' o 'compras'
        $nivel = $request->get('nivel', 'mensual'); // 'mensual', 'semanal', 'anual'
        $mesBase = $request->get('mes_base', now()->format('Y-m')); // 'YYYY-MM'
        $mesComparar = $request->get('mes_comparar', 'auto'); // 'auto', 'YYYY-MM', 'mismo_ano_anterior', 'ventas_vs_compras', 'ninguno'
        $semanaFecha = $request->get('semana_fecha', now()->format('Y-m-d')); // YYYY-MM-DD
        $semanaComparar = $request->get('semana_comparar', 'auto'); // 'auto', 'YYYY-MM-DD', 'ventas_vs_compras', 'ninguna'
        $anoBase = (int) $request->get('ano_base', now()->format('Y'));
        $anoComparar = $request->get('ano_comparar', 'auto'); // 'auto', 'YYYY', 'ventas_vs_compras', 'ninguno'

        $mesesNombresMap = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        $mesesNombresCortos = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];
        $diasSemanaNombres = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

        // Obtener lista global de meses disponibles en la base de datos
        $minVentaDate = Venta::min('created_at') ?: now()->format('Y-m-d');
        $maxVentaDate = Venta::max('created_at') ?: now()->format('Y-m-d');
        $minCompraDate = Compra::min('fecha_compra') ?: now()->format('Y-m-d');
        $maxCompraDate = Compra::max('fecha_compra') ?: now()->format('Y-m-d');
        $minTransDate = Transferencia::min('created_at') ?: now()->format('Y-m-d');
        $maxTransDate = Transferencia::max('created_at') ?: now()->format('Y-m-d');

        $minDateGlobal = Carbon::parse(min($minVentaDate, $minCompraDate, $minTransDate))->startOfMonth();
        $maxDateGlobal = Carbon::parse(max($maxVentaDate, $maxCompraDate, $maxTransDate, now()->format('Y-m-d')))->startOfMonth();

        $mesesDisponibles = [];
        $cursorMonth = $maxDateGlobal->copy();
        while ($cursorMonth->greaterThanOrEqualTo($minDateGlobal)) {
            $mesVal = $cursorMonth->format('Y-m');
            $mesesDisponibles[] = [
                'value' => $mesVal,
                'label' => $mesesNombresMap[(int) $cursorMonth->format('n')] . ' ' . $cursorMonth->format('Y'),
                'year' => $cursorMonth->format('Y'),
                'month' => (int) $cursorMonth->format('n')
            ];
            $cursorMonth->subMonthNoOverflow();
        }

        $anosDisponibles = array_values(array_unique(array_column($mesesDisponibles, 'year')));

        // Helper para consultar datos diarios de un mes
        $getDatosMes = function ($mesYm, $tipoConsulta) use ($mesesNombresMap) {
            $date = Carbon::parse($mesYm . '-01');
            $diasEnMes = (int) $date->daysInMonth;
            $inicio = $date->copy()->startOfMonth()->format('Y-m-d 00:00:00');
            $fin = $date->copy()->endOfMonth()->format('Y-m-d 23:59:59');

            if ($tipoConsulta === 'compras') {
                $raw = Compra::whereBetween('fecha_compra', [$inicio, $fin])
                    ->selectRaw("CAST(strftime('%d', fecha_compra) AS INTEGER) as dia, SUM(total) as total")
                    ->groupBy('dia')
                    ->pluck('total', 'dia')
                    ->toArray();
            } elseif ($tipoConsulta === 'transferencias') {
                $raw = Transferencia::whereBetween('created_at', [$inicio, $fin])
                    ->where('estado', '!=', 'cancelada')
                    ->selectRaw("CAST(strftime('%d', created_at) AS INTEGER) as dia, SUM(costo_total) as total")
                    ->groupBy('dia')
                    ->pluck('total', 'dia')
                    ->toArray();
            } elseif ($tipoConsulta === 'beneficios') {
                $vds = VentaDetalle::whereBetween('created_at', [$inicio, $fin])
                    ->whereHas('venta', fn($q) => $q->whereNotIn('estado', ['devuelta']))
                    ->with('articulo')
                    ->get();
                $raw = [];
                foreach ($vds as $vd) {
                    $d = (int) $vd->created_at->format('d');
                    $vTot = (float) $vd->subtotal;
                    $cUnit = (float) ($vd->articulo?->precio_compra ?: $vd->articulo?->precio_sin_iva ?: 0);
                    $cTot = (float) $vd->cantidad * $cUnit;
                    $ganancia = $vTot - $cTot;
                    $raw[$d] = ($raw[$d] ?? 0.0) + $ganancia;
                }
            } else {
                $raw = Venta::whereBetween('created_at', [$inicio, $fin])
                    ->whereNotIn('estado', ['devuelta'])
                    ->selectRaw("CAST(strftime('%d', created_at) AS INTEGER) as dia, SUM(total) as total")
                    ->groupBy('dia')
                    ->pluck('total', 'dia')
                    ->toArray();
            }

            $dias = [];
            for ($d = 1; $d <= $diasEnMes; $d++) {
                $dias[$d] = round((float) ($raw[$d] ?? 0), 2);
            }

            return [
                'dias' => $dias,
                'total' => round(array_sum($dias), 2),
                'countDias' => $diasEnMes,
                'label' => ($mesesNombresMap[(int)$date->format('n')] ?? $date->format('M')) . ' ' . $date->format('Y')
            ];
        };

        // Helper para consultar datos de una semana (Lunes a Domingo)
        $getDatosSemana = function ($fechaEnSemana, $tipoConsulta) use ($diasSemanaNombres) {
            $dateRef = Carbon::parse($fechaEnSemana);
            $startOfWeek = $dateRef->copy()->startOfWeek(); // Lunes
            $endOfWeek = $dateRef->copy()->endOfWeek();     // Domingo

            $diasData = [];
            $diasLabels = [];

            for ($i = 0; $i < 7; $i++) {
                $dayCur = $startOfWeek->copy()->addDays($i);
                $diasLabels[] = $diasSemanaNombres[$i] . ' ' . $dayCur->format('d/m');

                if ($tipoConsulta === 'compras') {
                    $monto = (float) Compra::whereBetween('fecha_compra', [$dayCur->format('Y-m-d 00:00:00'), $dayCur->format('Y-m-d 23:59:59')])
                        ->sum('total');
                } elseif ($tipoConsulta === 'transferencias') {
                    $monto = (float) Transferencia::whereBetween('created_at', [$dayCur->format('Y-m-d 00:00:00'), $dayCur->format('Y-m-d 23:59:59')])
                        ->where('estado', '!=', 'cancelada')
                        ->sum('costo_total');
                } elseif ($tipoConsulta === 'beneficios') {
                    $vds = VentaDetalle::whereBetween('created_at', [$dayCur->format('Y-m-d 00:00:00'), $dayCur->format('Y-m-d 23:59:59')])
                        ->whereHas('venta', fn($q) => $q->whereNotIn('estado', ['devuelta']))
                        ->with('articulo')
                        ->get();
                    $monto = 0.0;
                    foreach ($vds as $vd) {
                        $vTot = (float) $vd->subtotal;
                        $cUnit = (float) ($vd->articulo?->precio_compra ?: $vd->articulo?->precio_sin_iva ?: 0);
                        $cTot = (float) $vd->cantidad * $cUnit;
                        $monto += ($vTot - $cTot);
                    }
                } else {
                    $monto = (float) Venta::whereBetween('created_at', [$dayCur->format('Y-m-d 00:00:00'), $dayCur->format('Y-m-d 23:59:59')])
                        ->whereNotIn('estado', ['devuelta'])
                        ->sum('total');
                }
                $diasData[] = round($monto, 2);
            }

            return [
                'data' => $diasData,
                'labels' => $diasLabels,
                'total' => round(array_sum($diasData), 2),
                'start' => $startOfWeek->format('Y-m-d'),
                'end' => $endOfWeek->format('Y-m-d'),
                'label' => 'Sem ' . $startOfWeek->format('W') . ' (' . $startOfWeek->format('d/m') . ' - ' . $endOfWeek->format('d/m') . ')'
            ];
        };

        // Helper para consultar datos de 12 meses de un año
        $getDatosAno = function ($ano, $tipoConsulta) use ($mesesNombresCortos) {
            $inicioAno = $ano . '-01-01 00:00:00';
            $finAno = $ano . '-12-31 23:59:59';

            if ($tipoConsulta === 'compras') {
                $raw = Compra::whereBetween('fecha_compra', [$inicioAno, $finAno])
                    ->selectRaw("CAST(strftime('%m', fecha_compra) AS INTEGER) as mes, SUM(total) as total")
                    ->groupBy('mes')
                    ->pluck('total', 'mes')
                    ->toArray();
            } elseif ($tipoConsulta === 'transferencias') {
                $raw = Transferencia::whereBetween('created_at', [$inicioAno, $finAno])
                    ->where('estado', '!=', 'cancelada')
                    ->selectRaw("CAST(strftime('%m', created_at) AS INTEGER) as mes, SUM(costo_total) as total")
                    ->groupBy('mes')
                    ->pluck('total', 'mes')
                    ->toArray();
            } elseif ($tipoConsulta === 'beneficios') {
                $vds = VentaDetalle::whereBetween('created_at', [$inicioAno, $finAno])
                    ->whereHas('venta', fn($q) => $q->whereNotIn('estado', ['devuelta']))
                    ->with('articulo')
                    ->get();
                $raw = [];
                foreach ($vds as $vd) {
                    $m = (int) $vd->created_at->format('m');
                    $vTot = (float) $vd->subtotal;
                    $cUnit = (float) ($vd->articulo?->precio_compra ?: $vd->articulo?->precio_sin_iva ?: 0);
                    $cTot = (float) $vd->cantidad * $cUnit;
                    $ganancia = $vTot - $cTot;
                    $raw[$m] = ($raw[$m] ?? 0.0) + $ganancia;
                }
            } else {
                $raw = Venta::whereBetween('created_at', [$inicioAno, $finAno])
                    ->whereNotIn('estado', ['devuelta'])
                    ->selectRaw("CAST(strftime('%m', created_at) AS INTEGER) as mes, SUM(total) as total")
                    ->groupBy('mes')
                    ->pluck('total', 'mes')
                    ->toArray();
            }

            $mesesData = [];
            for ($m = 1; $m <= 12; $m++) {
                $mesesData[] = round((float) ($raw[$m] ?? 0), 2);
            }

            return [
                'data' => $mesesData,
                'labels' => array_values($mesesNombresCortos),
                'total' => round(array_sum($mesesData), 2),
                'label' => 'Año ' . $ano
            ];
        };

        // -------------------------------------------------------------
        // NIVEL 1: MENSUAL (Día 1 al 31)
        // -------------------------------------------------------------
        if ($nivel === 'mensual') {
            $dateBase = Carbon::parse($mesBase . '-01');
            $resBase = $getDatosMes($mesBase, $tipo);

            $targetCompararYm = null;
            $hasComparison = true;
            $isVentasVsCompras = false;

            if ($mesComparar === 'ventas_vs_compras') {
                $isVentasVsCompras = true;
                $resComp = $getDatosMes($mesBase, $tipo === 'ventas' ? 'compras' : 'ventas');
                $labelComp = ($tipo === 'ventas' ? 'Compras / Costos' : 'Ventas Facturadas') . ' (' . $resBase['label'] . ')';
            } elseif ($mesComparar === 'ninguno') {
                $hasComparison = false;
                $resComp = null;
                $labelComp = 'Sin comparación';
            } else {
                if ($mesComparar === 'auto' || $mesComparar === 'previo') {
                    $targetCompararYm = $dateBase->copy()->subMonthNoOverflow()->format('Y-m');
                } elseif ($mesComparar === 'mismo_ano_anterior') {
                    $targetCompararYm = $dateBase->copy()->subYear()->format('Y-m');
                } else {
                    $targetCompararYm = $mesComparar;
                }

                $resComp = $getDatosMes($targetCompararYm, $tipo);
                $labelComp = $mesesNombresMap[(int) Carbon::parse($targetCompararYm . '-01')->format('n')] . ' ' . Carbon::parse($targetCompararYm . '-01')->format('Y');
            }

            $maxDias = $hasComparison && $resComp ? max($resBase['countDias'], $resComp['countDias']) : $resBase['countDias'];
            $labels = [];
            $dataBase = [];
            $dataComparar = [];

            for ($d = 1; $d <= $maxDias; $d++) {
                $labels[] = 'Día ' . $d;
                $dataBase[] = $resBase['dias'][$d] ?? 0;
                if ($hasComparison && $resComp) {
                    $dataComparar[] = $resComp['dias'][$d] ?? 0;
                }
            }

            // Desglose de semanas del mes para drill-down
            $semanasDelMes = [];
            $firstDayOfMonth = $dateBase->copy()->startOfMonth();
            $lastDayOfMonth = $dateBase->copy()->endOfMonth();
            $cursorWeek = $firstDayOfMonth->copy();
            $wNum = 1;

            while ($cursorWeek->lessThanOrEqualTo($lastDayOfMonth)) {
                $wStart = $cursorWeek->copy()->startOfWeek();
                $wEnd = $cursorWeek->copy()->endOfWeek();
                
                $semanasDelMes[] = [
                    'num' => $wNum,
                    'label' => 'Semana ' . $wNum . ' (' . $wStart->format('d/m') . ' - ' . $wEnd->format('d/m') . ')',
                    'fecha' => $cursorWeek->format('Y-m-d'),
                    'start' => $wStart->format('d/m'),
                    'end' => $wEnd->format('d/m')
                ];
                $cursorWeek->addWeek();
                $wNum++;
            }

            $totalBase = $resBase['total'];
            $totalComp = $hasComparison && $resComp ? $resComp['total'] : 0;
            $diffMonto = round($totalBase - $totalComp, 2);
            $diffPct = $totalComp > 0 ? round(($diffMonto / $totalComp) * 100, 1) : ($totalBase > 0 ? 100.0 : 0.0);

            $labelPrefix = ($tipo === 'ventas' ? 'Ventas ' : ($tipo === 'compras' ? 'Compras ' : ($tipo === 'transferencias' ? 'Transferencias ' : 'Beneficio ')));

            return response()->json([
                'success' => true,
                'nivel' => 'mensual',
                'tipo' => $tipo,
                'mesBase' => $mesBase,
                'mesBaseLabel' => $mesesNombresMap[(int) $dateBase->format('n')] . ' ' . $dateBase->format('Y'),
                'mesBasePrev' => $dateBase->copy()->subMonthNoOverflow()->format('Y-m'),
                'mesBaseNext' => $dateBase->copy()->addMonthNoOverflow()->format('Y-m'),
                'anoPertenece' => $dateBase->format('Y'),
                'mesComparar' => $mesComparar,
                'mesCompararLabel' => $labelComp,
                'hasComparison' => $hasComparison,
                'isVentasVsCompras' => $isVentasVsCompras,
                'labels' => $labels,
                'labelBase' => $labelPrefix . $mesesNombresMap[(int) $dateBase->format('n')] . ' ' . $dateBase->format('Y'),
                'labelComparar' => $labelComp,
                'dataBase' => $dataBase,
                'dataComparar' => $hasComparison ? $dataComparar : [],
                'totalBase' => $totalBase,
                'totalComparar' => $totalComp,
                'diffMonto' => $diffMonto,
                'diffPct' => $diffPct,
                'semanasDelMes' => $semanasDelMes,
                'mesesDisponibles' => $mesesDisponibles,
                'anosDisponibles' => $anosDisponibles
            ]);
        }

        // -------------------------------------------------------------
        // NIVEL 2: SEMANAL (Día a Día: Lun a Dom)
        // -------------------------------------------------------------
        if ($nivel === 'semanal') {
            $dateSemana = Carbon::parse($semanaFecha);
            $resBase = $getDatosSemana($semanaFecha, $tipo);

            $hasComparison = true;
            $isVentasVsCompras = false;

            if ($semanaComparar === 'ventas_vs_compras') {
                $isVentasVsCompras = true;
                $resComp = $getDatosSemana($semanaFecha, $tipo === 'ventas' ? 'compras' : 'ventas');
                $labelComp = ($tipo === 'ventas' ? 'Compras / Costos' : 'Ventas') . ' (' . $resBase['label'] . ')';
            } elseif ($semanaComparar === 'ninguna') {
                $hasComparison = false;
                $resComp = null;
                $labelComp = 'Sin comparación';
            } else {
                if ($semanaComparar === 'auto' || $semanaComparar === 'anterior') {
                    $fechaComp = $dateSemana->copy()->subWeek()->format('Y-m-d');
                } elseif ($semanaComparar === 'mes_anterior') {
                    $fechaComp = $dateSemana->copy()->subMonthNoOverflow()->format('Y-m-d');
                } else {
                    $fechaComp = $semanaComparar;
                }

                $resComp = $getDatosSemana($fechaComp, $tipo);
                $labelComp = $resComp['label'];
            }

            $totalBase = $resBase['total'];
            $totalComp = $hasComparison && $resComp ? $resComp['total'] : 0;
            $diffMonto = round($totalBase - $totalComp, 2);
            $diffPct = $totalComp > 0 ? round(($diffMonto / $totalComp) * 100, 1) : ($totalBase > 0 ? 100.0 : 0.0);
            $labelPrefix = ($tipo === 'ventas' ? 'Ventas ' : ($tipo === 'compras' ? 'Compras ' : ($tipo === 'transferencias' ? 'Transferencias ' : 'Beneficio ')));

            return response()->json([
                'success' => true,
                'nivel' => 'semanal',
                'tipo' => $tipo,
                'semanaFecha' => $semanaFecha,
                'semanaBaseLabel' => $resBase['label'],
                'semanaPrevFecha' => $dateSemana->copy()->subWeek()->format('Y-m-d'),
                'semanaNextFecha' => $dateSemana->copy()->addWeek()->format('Y-m-d'),
                'mesPerteneciente' => $dateSemana->format('Y-m'),
                'mesPertenecienteLabel' => $mesesNombresMap[(int) $dateSemana->format('n')] . ' ' . $dateSemana->format('Y'),
                'semanaComparar' => $semanaComparar,
                'semanaCompararLabel' => $labelComp,
                'hasComparison' => $hasComparison,
                'isVentasVsCompras' => $isVentasVsCompras,
                'labels' => $resBase['labels'],
                'labelBase' => $labelPrefix . $resBase['label'],
                'labelComparar' => $labelComp,
                'dataBase' => $resBase['data'],
                'dataComparar' => $hasComparison && $resComp ? $resComp['data'] : [],
                'totalBase' => $totalBase,
                'totalComparar' => $totalComp,
                'diffMonto' => $diffMonto,
                'diffPct' => $diffPct,
                'mesesDisponibles' => $mesesDisponibles,
                'anosDisponibles' => $anosDisponibles
            ]);
        }

        // -------------------------------------------------------------
        // NIVEL 3: ANUAL / MULTIMES (Ene a Dic)
        // -------------------------------------------------------------
        if ($nivel === 'anual') {
            $resBase = $getDatosAno($anoBase, $tipo);

            $hasComparison = true;
            $isVentasVsCompras = false;

            if ($anoComparar === 'ventas_vs_compras') {
                $isVentasVsCompras = true;
                $resComp = $getDatosAno($anoBase, $tipo === 'ventas' ? 'compras' : 'ventas');
                $labelComp = ($tipo === 'ventas' ? 'Compras / Costos' : 'Ventas') . ' (Año ' . $anoBase . ')';
            } elseif ($anoComparar === 'ninguno') {
                $hasComparison = false;
                $resComp = null;
                $labelComp = 'Sin comparación';
            } else {
                $targetAno = ($anoComparar === 'auto') ? ($anoBase - 1) : (int) $anoComparar;
                $resComp = $getDatosAno($targetAno, $tipo);
                $labelComp = 'Año ' . $targetAno;
            }

            $totalBase = $resBase['total'];
            $totalComp = $hasComparison && $resComp ? $resComp['total'] : 0;
            $diffMonto = round($totalBase - $totalComp, 2);
            $diffPct = $totalComp > 0 ? round(($diffMonto / $totalComp) * 100, 1) : ($totalBase > 0 ? 100.0 : 0.0);
            $labelPrefix = ($tipo === 'ventas' ? 'Ventas ' : ($tipo === 'compras' ? 'Compras ' : ($tipo === 'transferencias' ? 'Transferencias ' : 'Beneficio ')));

            return response()->json([
                'success' => true,
                'nivel' => 'anual',
                'tipo' => $tipo,
                'anoBase' => $anoBase,
                'anoBaseLabel' => 'Año ' . $anoBase,
                'anoBasePrev' => $anoBase - 1,
                'anoBaseNext' => $anoBase + 1,
                'anoComparar' => $anoComparar,
                'anoCompararLabel' => $labelComp,
                'hasComparison' => $hasComparison,
                'isVentasVsCompras' => $isVentasVsCompras,
                'labels' => $resBase['labels'],
                'labelBase' => $labelPrefix . 'Año ' . $anoBase,
                'labelComparar' => $labelComp,
                'dataBase' => $resBase['data'],
                'dataComparar' => $hasComparison && $resComp ? $resComp['data'] : [],
                'totalBase' => $totalBase,
                'totalComparar' => $totalComp,
                'diffMonto' => $diffMonto,
                'diffPct' => $diffPct,
                'mesesDisponibles' => $mesesDisponibles,
                'anosDisponibles' => $anosDisponibles
            ]);
        }

        return response()->json(['error' => 'Nivel no válido'], 400);
    }
}
