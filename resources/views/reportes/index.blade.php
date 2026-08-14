@extends('layouts.app')

@section('title', 'Módulo de Reportes - IntelliCarnic')

@section('content')
<div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-chart-pie" style="color: var(--primary);"></i> Módulo de Reportes e Historiales</h1>
        <p class="page-subtitle">Analiza las ventas, compras de mercancía y la comparativa del stock físico contra el mínimo configurado.</p>
    </div>
    <div>
        <a href="{{ route('reportes.exportar', ['tipo' => $tab, 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'familia_id' => $familiaId, 'filtro_stock' => $filtroStock]) }}" class="btn-modern btn-accent">
            <i class="fa-solid fa-file-csv"></i> Exportar a CSV / Excel
        </a>
    </div>
</div>

<!-- NAVEGACIÓN POR PESTAÑAS -->
<div class="tabs-header" style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border-color); margin-bottom: 1.5rem; flex-wrap: wrap;">
    <a href="{{ route('reportes.index', ['tab' => 'ventas', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}" 
       class="tab-item {{ $tab === 'ventas' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'ventas' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'ventas' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-cash-register"></i> Reporte de Ventas
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'compras', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}" 
       class="tab-item {{ $tab === 'compras' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'compras' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'compras' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-truck-ramp-box"></i> Reporte de Compras
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'inventario', 'familia_id' => $familiaId, 'filtro_stock' => $filtroStock]) }}" 
       class="tab-item {{ $tab === 'inventario' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'inventario' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'inventario' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-boxes-stacked"></i> Inventario Comparativo (Físico vs Mínimo)
    </a>
</div>

<!-- FILTROS DE BÚSQUEDA -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="{{ route('reportes.index') }}" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="tab" value="{{ $tab }}">

        @if($tab === 'ventas' || $tab === 'compras')
            <div style="flex: 1; min-width: 180px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Fecha Desde</label>
                <input type="date" name="fecha_inicio" value="{{ $fechaInicio }}" class="input-modern" required>
            </div>
            <div style="flex: 1; min-width: 180px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Fecha Hasta</label>
                <input type="date" name="fecha_fin" value="{{ $fechaFin }}" class="input-modern" required>
            </div>
        @else
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Familia de Producto</label>
                <select name="familia_id" class="input-modern">
                    <option value="">-- Todas las Familias --</option>
                    @foreach($familias as $fam)
                        <option value="{{ $fam->id }}" @selected($familiaId == $fam->id)>{{ $fam->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex: 1; min-width: 200px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Estado de Stock</label>
                <select name="filtro_stock" class="input-modern">
                    <option value="todos" @selected($filtroStock === 'todos')>Todos los Estados</option>
                    <option value="ok" @selected($filtroStock === 'ok')>🟢 Stock Normal (Suficiente)</option>
                    <option value="bajo" @selected($filtroStock === 'bajo')>🟠 Stock Bajo (<= Mínimo)</option>
                    <option value="sin_stock" @selected($filtroStock === 'sin_stock')>🔴 Sin Stock (Agotado)</option>
                </select>
            </div>
        @endif

        <div>
            <button type="submit" class="btn-modern btn-primary" style="width: auto;">
                <i class="fa-solid fa-filter"></i> Aplicar Filtros
            </button>
        </div>
    </form>
</div>

<!-- PESTAÑA 1: VENTAS -->
@if($tab === 'ventas')
    <!-- Tarjetas KPI Ventas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">TOTAL VENDIDO</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">${{ number_format($totalVentasMonto, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Subtotal: ${{ number_format($totalVentasSubtotal, 2) }} | IVA: ${{ number_format($totalVentasImpuestos, 2) }}</div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">VOLUMEN VENDIDO</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">{{ number_format($totalPesoVendido, 2) }} <span style="font-size: 1rem;">{{ $unidadPeso }}</span></div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Peso neto comercializado en el rango</div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Nº TRANSACCIONES</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #6366f1; margin-top: 0.25rem;">{{ $numVentas }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Tickets/Ventas cobradas</div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">PROMEDIO POR TICKET</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #f59e0b; margin-top: 0.25rem;">${{ number_format($promedioVenta, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Ticket promedio alcanzado</div>
        </div>
    </div>

    <!-- Sección Secundaria: Top Productos & Métodos de Pago -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Top Productos -->
        <div class="card" style="padding: 1.25rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">
                <i class="fa-solid fa-trophy" style="color: #f59e0b;"></i> Top 10 Productos Más Vendidos
            </h3>
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th style="text-align: right;">Cantidad</th>
                        <th style="text-align: right;">Total ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProductosVendidos as $index => $top)
                        <tr>
                            <td><span class="badge badge-info" style="font-weight: 700;">{{ $index + 1 }}</span></td>
                            <td style="font-weight: 600;">{{ $top->articulo ? $top->articulo->descripcion : 'Producto Eliminado' }}</td>
                            <td style="text-align: right; font-weight: 700;">{{ number_format($top->total_cantidad, 2) }} {{ $unidadPeso }}</td>
                            <td style="text-align: right; color: var(--primary); font-weight: 700;">${{ number_format($top->total_monto, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 1rem;">No hay ventas registradas en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Métodos de Pago -->
        <div class="card" style="padding: 1.25rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">
                <i class="fa-solid fa-wallet" style="color: var(--primary);"></i> Desglose por Método de Pago
            </h3>
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Método de Pago</th>
                        <th style="text-align: center;">Ventas</th>
                        <th style="text-align: right;">Monto Total ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventasPorMetodo as $metodo)
                        <tr>
                            <td style="font-weight: 600; text-transform: uppercase;">
                                @if($metodo->metodo_pago === 'efectivo')
                                    <i class="fa-solid fa-money-bill-wave" style="color: #10b981;"></i> Efectivo
                                @elseif($metodo->metodo_pago === 'tarjeta')
                                    <i class="fa-solid fa-credit-card" style="color: #3b82f6;"></i> Tarjeta
                                @else
                                    <i class="fa-solid fa-circle-dollar-to-slot"></i> {{ $metodo->metodo_pago }}
                                @endif
                            </td>
                            <td style="text-align: center; font-weight: 700;">{{ $metodo->total_transacciones }}</td>
                            <td style="text-align: right; font-weight: 700; color: var(--primary);">${{ number_format($metodo->monto_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1rem;">Sin transacciones.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabla General de Ventas -->
    <div class="card" style="padding: 1.25rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">Historial Detallado de Ventas</h3>
        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>N° Ticket</th>
                        <th>Fecha / Hora</th>
                        <th>Método Pago</th>
                        <th style="text-align: right;">Subtotal</th>
                        <th style="text-align: right;">Impuestos</th>
                        <th style="text-align: right;">Total Cobrado</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventasLista as $v)
                        <tr>
                            <td style="font-weight: 700; font-family: monospace; color: var(--primary);">#{{ str_pad($v->id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $v->created_at->format('d/m/Y h:i A') }}</td>
                            <td>
                                <span class="badge badge-info" style="text-transform: uppercase;">{{ $v->metodo_pago }}</span>
                            </td>
                            <td style="text-align: right;">${{ number_format($v->subtotal, 2) }}</td>
                            <td style="text-align: right;">${{ number_format($v->impuestos, 2) }}</td>
                            <td style="text-align: right; font-weight: 800; color: var(--primary);">${{ number_format($v->total, 2) }}</td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-modern btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.82rem;" onclick="abrirDocumentoVenta({{ json_encode($v) }})">
                                    <i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary);"></i> Ver Documento
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No se encontraron registros de ventas en este rango de fechas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1rem;">
            {{ $ventasLista->appends(['tab' => 'ventas', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin])->links() }}
        </div>
    </div>
@endif

<!-- PESTAÑA 2: COMPRAS -->
@if($tab === 'compras')
    <!-- Tarjetas KPI Compras -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">INVERSIÓN EN COMPRAS</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent); margin-top: 0.25rem;">${{ number_format($totalComprasMonto, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Subtotal: ${{ number_format($totalComprasSubtotal, 2) }}</div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">VOLUMEN INGRESADO</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">{{ number_format($totalPesoComprado, 2) }} <span style="font-size: 1rem;">{{ $unidadPeso }}</span></div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Peso recibido de proveedores</div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Nº RECEPCIONES / FACTURAS</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #6366f1; margin-top: 0.25rem;">{{ $numCompras }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Compras registradas en el período</div>
        </div>
    </div>

    <!-- Tabla General de Compras -->
    <div class="card" style="padding: 1.25rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">Historial Detallado de Compras</h3>
        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>N° Factura</th>
                        <th>Proveedor</th>
                        <th>Fecha de Compra</th>
                        <th style="text-align: center;">Ítems/Cajas</th>
                        <th style="text-align: right;">Subtotal</th>
                        <th style="text-align: right;">Total Invertido</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comprasLista as $c)
                        <tr>
                            <td style="font-weight: 700; font-family: monospace; color: var(--accent);">{{ $c->numero_factura ?: 'SIN FACTURA' }}</td>
                            <td style="font-weight: 600;">{{ $c->proveedor_nombre ?: 'Proveedor General' }}</td>
                            <td>{{ $c->fecha_compra ? $c->fecha_compra->format('d/m/Y h:i A') : 'N/A' }}</td>
                            <td style="text-align: center;">
                                <span class="badge badge-info">{{ $c->detalles->count() }} ítems</span>
                            </td>
                            <td style="text-align: right;">${{ number_format($c->subtotal, 2) }}</td>
                            <td style="text-align: right; font-weight: 800; color: var(--accent);">${{ number_format($c->total, 2) }}</td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-modern btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.82rem;" onclick="abrirDocumentoCompra({{ json_encode($c) }})">
                                    <i class="fa-solid fa-boxes-packing" style="color: var(--accent);"></i> Ver Documento
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No se encontraron registros de compras en este rango de fechas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1rem;">
            {{ $comprasLista->appends(['tab' => 'compras', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin])->links() }}
        </div>
    </div>
@endif

<!-- PESTAÑA 3: INVENTARIO COMPARATIVO -->
@if($tab === 'inventario')
    <!-- Tarjetas KPI Inventario -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">TOTAL PRODUCTOS</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--text-main); margin-top: 0.25rem;">{{ $countTotalArticulos }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">En catálogo activo</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid #10b981;">
            <div style="font-size: 0.85rem; color: #10b981; font-weight: 700;">🟢 STOCK NORMAL</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">{{ $countStockOk }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Stock > Mínimo configurado</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid #f59e0b;">
            <div style="font-size: 0.85rem; color: #f59e0b; font-weight: 700;">🟠 STOCK BAJO (ALERTA)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #f59e0b; margin-top: 0.25rem;">{{ $countStockBajo }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Stock <= Mínimo (Requiere compra)</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid #ef4444;">
            <div style="font-size: 0.85rem; color: #ef4444; font-weight: 700;">🔴 SIN STOCK (AGOTADO)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #ef4444; margin-top: 0.25rem;">{{ $countSinStock }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Stock físico en 0.00</div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">VALOR INVENTARIO</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">${{ number_format($valorTotalInventario, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Valorización a precio base</div>
        </div>
    </div>

    <!-- Tabla Comparativa de Inventario (Físico vs Mínimo) -->
    <div class="card" style="padding: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700;">Comparativa de Stock Físico vs Mínimo Configurado</h3>
            <span class="badge badge-info" style="font-size: 0.85rem;">{{ $articulosFiltrados->count() }} productos listados</span>
        </div>

        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Código / SKU</th>
                        <th>Descripción del Producto</th>
                        <th>Familia</th>
                        <th style="text-align: right;">Stock Físico Actual</th>
                        <th style="text-align: right;">Stock Mínimo</th>
                        <th style="text-align: right;">Diferencia</th>
                        <th style="text-align: center;">Estado del Stock</th>
                        <th style="text-align: right;">Costo ($)</th>
                        <th style="text-align: right;">Valor Total ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articulosFiltrados as $art)
                        <tr style="{{ $art->estado_evaluado === 'sin_stock' ? 'background: rgba(239, 68, 68, 0.04);' : ($art->estado_evaluado === 'bajo' ? 'background: rgba(245, 158, 11, 0.04);' : '') }}">
                            <td style="font-family: monospace; font-weight: 600;">{{ $art->codigo }}</td>
                            <td style="font-weight: 600; color: var(--text-main);">{{ $art->descripcion }}</td>
                            <td>{{ $art->familia ? $art->familia->nombre : 'Sin Familia' }}</td>
                            <td style="text-align: right; font-weight: 800; font-size: 1.05rem; color: {{ $art->stock_fisico_num <= 0 ? '#ef4444' : ($art->stock_fisico_num <= $art->stock_minimo_num ? '#f59e0b' : '#10b981') }};">
                                {{ number_format($art->stock_fisico_num, 3) }} {{ $unidadPeso }}
                            </td>
                            <td style="text-align: right; font-weight: 600; color: var(--text-muted);">
                                {{ number_format($art->stock_minimo_num, 3) }} {{ $unidadPeso }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: {{ $art->diferencia_num < 0 ? '#ef4444' : '#10b981' }};">
                                {{ $art->diferencia_num > 0 ? '+' : '' }}{{ number_format($art->diferencia_num, 3) }} {{ $unidadPeso }}
                            </td>
                            <td style="text-align: center;">
                                <span class="badge {{ $art->badge_class }}" style="font-size: 0.85rem; padding: 0.35rem 0.65rem;">
                                    @if($art->estado_evaluado === 'ok')
                                        🟢 {{ $art->estado_label }}
                                    @elseif($art->estado_evaluado === 'bajo')
                                        🟠 {{ $art->estado_label }}
                                    @else
                                        🔴 {{ $art->estado_label }}
                                    @endif
                                </span>
                            </td>
                            <td style="text-align: right;">${{ number_format($art->precio_sin_iva, 2) }}</td>
                            <td style="text-align: right; font-weight: 700; color: var(--primary);">${{ number_format($art->valor_inversion, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay productos que coincidan con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- MODAL DOCUMENTO DE VENTA (COMPROBANTE TICKET) -->
<div id="modal-documento-venta" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; max-width: 900px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color);">
        <div style="padding: 1.25rem 1.75rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="background: rgba(37, 99, 235, 0.1); color: var(--primary); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--primary); margin: 0;" id="doc-venta-titulo">Comprobante de Venta</h2>
                    <div style="font-size: 0.85rem; color: var(--text-muted);" id="doc-venta-fecha"></div>
                </div>
            </div>
            <button type="button" onclick="cerrarDocumentoVenta()" style="background: none; border: none; font-size: 1.4rem; color: var(--text-muted); cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div style="padding: 1.75rem;" id="doc-venta-body">
            <!-- Renderizado dinámico vía JS -->
        </div>

        <div style="padding: 1.25rem 1.75rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: space-between; align-items: center; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
            <button type="button" class="btn-modern btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir Comprobante</button>
            <button type="button" class="btn-modern btn-secondary" onclick="cerrarDocumentoVenta()">Cerrar Ventana</button>
        </div>
    </div>
</div>

<!-- MODAL DOCUMENTO DE COMPRA (COMPROBANTE RECEPCIÓN) -->
<div id="modal-documento-compra" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; max-width: 950px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color);">
        <div style="padding: 1.25rem 1.75rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="background: rgba(249, 115, 22, 0.12); color: var(--accent); width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                    <i class="fa-solid fa-boxes-packing"></i>
                </div>
                <div>
                    <h2 style="font-size: 1.2rem; font-weight: 800; color: var(--accent); margin: 0;" id="doc-compra-titulo">Documento de Recepción de Mercancía</h2>
                    <div style="font-size: 0.85rem; color: var(--text-muted);" id="doc-compra-fecha"></div>
                </div>
            </div>
            <button type="button" onclick="cerrarDocumentoCompra()" style="background: none; border: none; font-size: 1.4rem; color: var(--text-muted); cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div style="padding: 1.75rem;" id="doc-compra-body">
            <!-- Renderizado dinámico vía JS -->
        </div>

        <div style="padding: 1.25rem 1.75rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: space-between; align-items: center; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px;">
            <button type="button" class="btn-modern btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir Documento</button>
            <button type="button" class="btn-modern btn-secondary" onclick="cerrarDocumentoCompra()">Cerrar Ventana</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const unidadPesoGlobal = "{{ $unidadPeso }}";

    function abrirDocumentoVenta(venta) {
        const modal = document.getElementById('modal-documento-venta');
        const ticketNum = String(venta.id).padStart(6, '0');
        document.getElementById('doc-venta-titulo').innerText = `Comprobante de Venta Ticket #${ticketNum}`;
        document.getElementById('doc-venta-fecha').innerText = `Fecha: ${venta.created_at ? new Date(venta.created_at).toLocaleString() : ''} | Estado: PAGADO`;

        let html = `
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">N° Ticket</div>
                    <div style="font-weight: 800; font-family: monospace; font-size: 1.15rem; color: var(--primary); margin-top: 0.2rem;">#${ticketNum}</div>
                </div>
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Método de Pago</div>
                    <div style="font-weight: 700; font-size: 1.05rem; text-transform: uppercase; color: var(--text-main); margin-top: 0.2rem;">
                        <i class="fa-solid fa-credit-card" style="color: var(--primary);"></i> ${(venta.metodo_pago || 'EFECTIVO').toUpperCase()}
                    </div>
                </div>
                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 10px; padding: 1rem;">
                    <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Cobrado</div>
                    <div style="font-weight: 800; font-size: 1.2rem; color: var(--primary); margin-top: 0.2rem;">$${parseFloat(venta.total || 0).toFixed(2)}</div>
                </div>
            </div>

            <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.85rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-list-check" style="color: var(--primary);"></i> Desglose de Productos Comercializados
            </h4>
            <div style="overflow-x: auto; margin-bottom: 1.5rem; border: 1px solid var(--border-color); border-radius: 10px;">
                <table class="table-modern" style="width: 100%; margin: 0;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding: 0.85rem 1rem;">Descripción del Producto</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Cantidad / Peso</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Precio Unit. ($)</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Subtotal ($)</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        if (venta.detalles && venta.detalles.length > 0) {
            venta.detalles.forEach(d => {
                const desc = d.articulo ? d.articulo.descripcion : 'Producto General';
                const sku = d.articulo ? d.articulo.codigo : 'N/A';
                const cant = parseFloat(d.cantidad || 0).toFixed(2);
                const precio = parseFloat(d.precio_unitario || 0).toFixed(2);
                const sub = parseFloat(d.subtotal || 0).toFixed(2);

                html += `
                    <tr>
                        <td style="padding: 0.85rem 1rem;">
                            <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;">${desc}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">SKU / Código: ${sku}</div>
                        </td>
                        <td style="padding: 0.85rem 1rem; text-align: right; font-weight: 700; font-size: 0.95rem;">${cant} ${unidadPesoGlobal}</td>
                        <td style="padding: 0.85rem 1rem; text-align: right;">$${precio}</td>
                        <td style="padding: 0.85rem 1rem; text-align: right; font-weight: 800; color: var(--primary); font-size: 1rem;">$${sub}</td>
                    </tr>
                `;
            });
        } else {
            html += `<tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">Sin detalles de ítems registrados.</td></tr>`;
        }

        html += `
                    </tbody>
                </table>
            </div>

            <div style="background: #f8fafc; border-radius: 10px; padding: 1.25rem; border: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                <div style="min-width: 250px; display: flex; flex-direction: column; gap: 0.4rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                        <span style="color: var(--text-muted);">Subtotal:</span>
                        <span style="font-weight: 600;">$${parseFloat(venta.subtotal || 0).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                        <span style="color: var(--text-muted);">Impuestos (IVA):</span>
                        <span style="font-weight: 600;">$${parseFloat(venta.impuestos || 0).toFixed(2)}</span>
                    </div>
                    <div style="border-top: 2px solid var(--border-color); margin-top: 0.4rem; padding-top: 0.4rem; display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 800; color: var(--primary);">
                        <span>TOTAL:</span>
                        <span>$${parseFloat(venta.total || 0).toFixed(2)}</span>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('doc-venta-body').innerHTML = html;
        modal.style.display = 'flex';
    }

    function cerrarDocumentoVenta() {
        document.getElementById('modal-documento-venta').style.display = 'none';
    }

    function abrirDocumentoCompra(compra) {
        const modal = document.getElementById('modal-documento-compra');
        const numFactura = compra.numero_factura || 'SIN FACTURA';
        const prov = compra.proveedor_nombre || 'Proveedor General';
        document.getElementById('doc-compra-titulo').innerText = `Documento de Recepción de Compra (${numFactura})`;
        document.getElementById('doc-compra-fecha').innerText = `Proveedor: ${prov} | Fecha: ${compra.fecha_compra ? new Date(compra.fecha_compra).toLocaleString() : 'N/A'}`;

        let html = `
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                <div style="background: #fff7ed; border: 1px solid #ffedd5; border-radius: 10px; padding: 1rem;">
                    <div style="font-size: 0.75rem; color: #9a3412; font-weight: 700; text-transform: uppercase;">N° Factura / Comprobante</div>
                    <div style="font-weight: 800; font-family: monospace; font-size: 1.15rem; color: var(--accent); margin-top: 0.2rem;">${numFactura}</div>
                </div>
                <div style="background: #fff7ed; border: 1px solid #ffedd5; border-radius: 10px; padding: 1rem;">
                    <div style="font-size: 0.75rem; color: #9a3412; font-weight: 700; text-transform: uppercase;">Proveedor Registrado</div>
                    <div style="font-weight: 700; font-size: 1.05rem; color: var(--text-main); margin-top: 0.2rem;">${prov}</div>
                </div>
                <div style="background: #fff7ed; border: 1px solid #ffedd5; border-radius: 10px; padding: 1rem;">
                    <div style="font-size: 0.75rem; color: #9a3412; font-weight: 700; text-transform: uppercase;">Total Invertido</div>
                    <div style="font-weight: 800; font-size: 1.2rem; color: var(--accent); margin-top: 0.2rem;">$${parseFloat(compra.total || 0).toFixed(2)}</div>
                </div>
            </div>

            <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.85rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-boxes-stacked" style="color: var(--accent);"></i> Desglose de Mercancía y Lotes Recibidos
            </h4>
            <div style="overflow-x: auto; margin-bottom: 1.5rem; border: 1px solid var(--border-color); border-radius: 10px;">
                <table class="table-modern" style="width: 100%; margin: 0; min-width: 800px;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding: 0.85rem 1rem;">Producto & SKU</th>
                            <th style="padding: 0.85rem 1rem;">Lote / Serie</th>
                            <th style="padding: 0.85rem 1rem;">Vencimiento</th>
                            <th style="padding: 0.85rem 1rem;">Código Escaneado</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Peso Recibido</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Costo Unit. ($)</th>
                            <th style="padding: 0.85rem 1rem; text-align: right;">Subtotal ($)</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        if (compra.detalles && compra.detalles.length > 0) {
            compra.detalles.forEach(d => {
                const desc = d.articulo ? d.articulo.descripcion : 'Producto General';
                const sku = d.articulo ? d.articulo.codigo : 'N/A';
                const loteVal = d.lote || '-';
                const serieVal = d.serie || '-';
                const venc = d.fecha_vencimiento ? d.fecha_vencimiento.substring(0, 10) : 'Sin Vencimiento';
                const peso = parseFloat(d.cantidad_peso || 0).toFixed(2);
                const costo = parseFloat(d.costo_unitario || 0).toFixed(2);
                const sub = parseFloat(d.subtotal || 0).toFixed(2);
                const codeBar = d.codigo_escaneado || '-';

                html += `
                    <tr>
                        <td style="padding: 0.85rem 1rem; vertical-align: top;">
                            <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;">${desc}</div>
                            <div style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace; margin-top: 2px;">SKU: ${sku}</div>
                        </td>
                        <td style="padding: 0.85rem 1rem; vertical-align: top;">
                            <div style="font-family: monospace; font-size: 0.85rem; color: #1e293b;">Lote: <b>${loteVal}</b></div>
                            <div style="font-family: monospace; font-size: 0.8rem; color: #64748b;">Serie: ${serieVal}</div>
                        </td>
                        <td style="padding: 0.85rem 1rem; vertical-align: top;">
                            <span class="badge badge-success" style="font-size: 0.8rem;">🟢 ${venc}</span>
                        </td>
                        <td style="padding: 0.85rem 1rem; vertical-align: top;">
                            <div style="font-size: 0.75rem; font-family: monospace; color: #475569; background: #e2e8f0; padding: 4px 8px; border-radius: 6px; display: inline-block; word-break: break-all; max-width: 220px; line-height: 1.3;">
                                ${codeBar}
                            </div>
                        </td>
                        <td style="padding: 0.85rem 1rem; text-align: right; font-weight: 800; font-size: 0.95rem; color: #10b981; vertical-align: top;">
                            ${peso} ${unidadPesoGlobal}
                        </td>
                        <td style="padding: 0.85rem 1rem; text-align: right; vertical-align: top;">$${costo}</td>
                        <td style="padding: 0.85rem 1rem; text-align: right; font-weight: 800; color: var(--accent); font-size: 1rem; vertical-align: top;">$${sub}</td>
                    </tr>
                `;
            });
        } else {
            html += `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">Sin detalles de recepción registrados.</td></tr>`;
        }

        html += `
                    </tbody>
                </table>
            </div>

            <div style="background: #fff7ed; border-radius: 10px; padding: 1.25rem; border: 1px solid #ffedd5; display: flex; justify-content: flex-end;">
                <div style="min-width: 250px; display: flex; flex-direction: column; gap: 0.4rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                        <span style="color: var(--text-muted);">Subtotal:</span>
                        <span style="font-weight: 600;">$${parseFloat(compra.subtotal || 0).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                        <span style="color: var(--text-muted);">Impuestos (IVA):</span>
                        <span style="font-weight: 600;">$${parseFloat(compra.iva || 0).toFixed(2)}</span>
                    </div>
                    <div style="border-top: 2px solid #fdba74; margin-top: 0.4rem; padding-top: 0.4rem; display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 800; color: var(--accent);">
                        <span>TOTAL COMPRA:</span>
                        <span>$${parseFloat(compra.total || 0).toFixed(2)}</span>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('doc-compra-body').innerHTML = html;
        modal.style.display = 'flex';
    }

    function cerrarDocumentoCompra() {
        document.getElementById('modal-documento-compra').style.display = 'none';
    }
</script>
@endpush

@endsection
