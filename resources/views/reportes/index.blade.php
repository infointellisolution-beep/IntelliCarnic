@extends('layouts.app')

@section('title', 'Módulo de Reportes - IntelliCarnic')

@section('content')
<div class="content-header" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
    <div>
        <h1 class="page-title"><i class="fa-solid fa-chart-pie" style="color: var(--primary);"></i> Módulo de Reportes e Historiales</h1>
        <p class="page-subtitle">Analiza las ventas, compras de mercancía y la comparativa del stock físico contra el mínimo configurado.</p>
    </div>
    <div>
        <a href="{{ route('reportes.exportar', ['tipo' => $tab, 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'familia_id' => $familiaId, 'filtro_stock' => $filtroStock, 'articulo_id' => $articuloId]) }}" class="btn-modern btn-accent">
            <i class="fa-solid fa-file-csv"></i> Exportar a CSV / Excel
        </a>
    </div>
</div>

<!-- NAVEGACIÓN POR PESTAÑAS -->
<div class="tabs-header" style="display: flex; gap: 0.5rem; border-bottom: 2px solid var(--border-color); margin-bottom: 1.5rem; flex-wrap: wrap;">
    <a href="{{ route('reportes.index', ['tab' => 'ventas', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'per_page' => $perPage]) }}" 
       class="tab-item {{ $tab === 'ventas' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'ventas' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'ventas' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-cash-register"></i> Reporte de Ventas
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'compras', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'per_page' => $perPage]) }}" 
       class="tab-item {{ $tab === 'compras' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'compras' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'compras' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-truck-ramp-box"></i> Reporte de Compras
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'caja', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'per_page' => $perPage]) }}" 
       class="tab-item {{ $tab === 'caja' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'caja' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'caja' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-vault"></i> Reporte de Caja
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'kardex', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'articulo_id' => $articuloId, 'per_page' => $perPage]) }}" 
       class="tab-item {{ $tab === 'kardex' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'kardex' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'kardex' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-timeline"></i> Kardex de Inventario
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'clientes', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'per_page' => $perPage]) }}" 
       class="tab-item {{ $tab === 'clientes' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'clientes' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'clientes' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-users-gear"></i> Reporte de Clientes
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'proveedores', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'per_page' => $perPage]) }}" 
       class="tab-item {{ $tab === 'proveedores' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'proveedores' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'proveedores' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-truck-field"></i> Reporte de Proveedores
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'inventario', 'familia_id' => $familiaId, 'filtro_stock' => $filtroStock, 'per_page' => $perPage]) }}" 
       class="tab-item {{ $tab === 'inventario' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'inventario' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'inventario' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-boxes-stacked"></i> Inventario Comparativo
    </a>
</div>

<!-- FILTROS DE BÚSQUEDA -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem; overflow: visible; position: relative; z-index: 100;">
    <form method="GET" action="{{ route('reportes.index') }}" id="form-filtros-reporte" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="hidden" name="fecha_inicio" id="hidden-fecha-inicio" value="{{ $fechaInicio }}">
        <input type="hidden" name="fecha_fin" id="hidden-fecha-fin" value="{{ $fechaFin }}">
        <input type="hidden" name="per_page" value="{{ $perPage }}">

        @if($tab === 'kardex')
            <!-- DATE RANGE PICKER TRIGGER -->
            <div style="min-width: 220px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">
                    <i class="fa-solid fa-calendar-days"></i> Período
                </label>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <button type="button" id="btn-abrir-datepicker" style="display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 1rem; border: 2px solid var(--primary); border-radius: 8px; background: rgba(37,99,235,0.05); color: var(--primary); font-weight: 700; font-size: 0.9rem; cursor: pointer; white-space: nowrap;">
                        <i class="fa-solid fa-calendar-range"></i>
                        <span id="label-rango-fecha">{{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</span>
                        <i class="fa-solid fa-chevron-down" style="margin-left: 0.25rem; font-size: 0.72rem;"></i>
                    </button>
                    <a href="{{ route('reportes.index', ['tab' => 'kardex']) }}" title="Limpiar filtro de fecha" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border-color); color: var(--text-muted); background: white; text-decoration: none; font-size: 0.9rem; flex-shrink: 0;" onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'" onmouseout="this.style.borderColor='var(--border-color)';this.style.color='var(--text-muted)'">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </div>
            </div>

            <!-- BUSCADOR INTELIGENTE DE PRODUCTO -->
            <div style="flex: 2; min-width: 280px; position: relative; z-index: 1000;" id="container-kardex-search">
                <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">
                    <i class="fa-solid fa-magnifying-glass" style="color: var(--primary);"></i> Buscar Producto (Nombre, SKU o Cód. Proveedor)
                </label>
                <div style="position: relative; display: flex; align-items: center;">
                    <input type="text" 
                           id="kardex-search-input" 
                           class="input-modern" 
                           placeholder="Escribe el nombre, SKU o código de proveedor..." 
                           value="{{ $articuloKardex ? $articuloKardex->descripcion . ' (SKU: ' . $articuloKardex->codigo . ($articuloKardex->codigo_cliente ? ' | Prov: ' . $articuloKardex->codigo_cliente : '') . ')' : '' }}" 
                           autocomplete="off" 
                           style="width: 100%; padding-right: 2.2rem; font-weight: 600;">
                    
                    <input type="hidden" name="articulo_id" id="hidden-kardex-articulo-id" value="{{ $articuloId }}">

                    <button type="button" 
                            id="btn-clear-kardex-search" 
                            onclick="limpiarBusquedaKardex()" 
                            style="position: absolute; right: 10px; background: none; border: none; font-size: 1.1rem; color: #94a3b8; cursor: pointer; display: {{ $articuloId ? 'block' : 'none' }}; line-height: 1;" 
                            title="Limpiar búsqueda y ver rotación general">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </div>

                <!-- Dropdown interactivo de resultados -->
                <div id="kardex-search-results" 
                     style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: white; border: 1px solid var(--border-color); border-radius: 8px; box-shadow: 0 15px 35px -5px rgba(0,0,0,0.3); max-height: 320px; overflow-y: auto; z-index: 99999;">
                </div>
            </div>

            <!-- FILTRO TIPO MOVIMIENTO -->
            <div style="flex: 1; min-width: 170px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Movimientos</label>
                <select name="filtro_movimiento" class="input-modern" onchange="document.getElementById('form-filtros-reporte').submit()">
                    <option value="todos" @selected($filtroMovimiento === 'todos')>Todos los tipos</option>
                    <option value="compra" @selected($filtroMovimiento === 'compra')>🟢 Solo Compras (Entradas)</option>
                    <option value="venta" @selected($filtroMovimiento === 'venta')>🔴 Solo Ventas (Salidas)</option>
                    <option value="devolucion" @selected($filtroMovimiento === 'devolucion')>🟠 Solo Devoluciones (Reingresos)</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn-modern btn-primary" style="width: auto;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
            </div>
        @elseif($tab === 'ventas' || $tab === 'compras' || $tab === 'caja' || $tab === 'clientes' || $tab === 'proveedores')
            <!-- DATE RANGE PICKER TRIGGER -->
            <div style="flex: 1; min-width: 220px;">
                <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">
                    <i class="fa-solid fa-calendar-days"></i> Período
                </label>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <button type="button" id="btn-abrir-datepicker" style="display: flex; align-items: center; gap: 0.6rem; padding: 0.6rem 1rem; border: 2px solid var(--primary); border-radius: 8px; background: rgba(37,99,235,0.05); color: var(--primary); font-weight: 700; font-size: 0.9rem; cursor: pointer; white-space: nowrap;">
                        <i class="fa-solid fa-calendar-range"></i>
                        <span id="label-rango-fecha">{{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</span>
                        <i class="fa-solid fa-chevron-down" style="margin-left: 0.25rem; font-size: 0.72rem;"></i>
                    </button>
                    <a href="{{ route('reportes.index', ['tab' => $tab]) }}" title="Limpiar filtro de fecha" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border-color); color: var(--text-muted); background: white; text-decoration: none; font-size: 0.9rem; flex-shrink: 0;" onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444'" onmouseout="this.style.borderColor='var(--border-color)';this.style.color='var(--text-muted)'">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </div>
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
            <div>
                <button type="submit" class="btn-modern btn-primary" style="width: auto;">
                    <i class="fa-solid fa-filter"></i> Aplicar Filtros
                </button>
            </div>
        @endif
    </form>
</div>

<!-- MODAL DATE RANGE PICKER (LIGHT THEME) -->
<div id="modal-datepicker" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.4); backdrop-filter:blur(3px); z-index:9998; align-items:center; justify-content:center; padding:1rem;">
    <div style="background:white; border-radius:16px; padding:1.5rem 1.75rem; box-shadow:0 20px 60px rgba(0,0,0,0.15); max-width:840px; width:95%; border:1px solid var(--border-color);">
        <!-- Título y rango seleccionado -->
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.25rem; border-bottom:1px solid var(--border-color); padding-bottom:1rem;">
            <div>
                <div style="font-size:0.8rem; font-weight:700; color:var(--text-muted); letter-spacing:0.05em; margin-bottom:0.35rem;">PERÍODO SELECCIONADO</div>
                <div id="dp-label-rango" style="display:inline-block; background:var(--primary); color:white; font-weight:700; font-size:0.95rem; padding:0.4rem 1.1rem; border-radius:8px;">-- / -- / ---- — -- / -- / ----</div>
            </div>
            <button type="button" id="dp-btn-cerrar-x" style="background:none; border:none; font-size:1.25rem; color:var(--text-muted); cursor:pointer; padding:0.25rem 0.5rem; border-radius:6px;" title="Cerrar"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div style="display:flex; gap:1.5rem; flex-wrap:wrap; justify-content:center;">
            <!-- CALENDARIO INICIO -->
            <div>
                <div style="text-align:center; font-size:0.8rem; font-weight:700; color:var(--text-muted); margin-bottom:0.6rem; letter-spacing:0.05em;">Inicio</div>
                <div id="cal-inicio" class="dp-calendar" data-which="inicio"></div>
            </div>

            <!-- CALENDARIO FIN -->
            <div>
                <div style="text-align:center; font-size:0.8rem; font-weight:700; color:var(--text-muted); margin-bottom:0.6rem; letter-spacing:0.05em;">Fin</div>
                <div id="cal-fin" class="dp-calendar" data-which="fin"></div>
            </div>

            <!-- PERÍODOS PREDEFINIDOS -->
            <div style="display:flex; flex-direction:column; gap:0.4rem; min-width:155px;">
                <div style="font-size:0.8rem; font-weight:700; color:var(--text-muted); margin-bottom:0.3rem; letter-spacing:0.05em;">Período predefinido</div>
                <button type="button" class="dp-preset" data-preset="hoy">Hoy</button>
                <button type="button" class="dp-preset" data-preset="ayer">Ayer</button>
                <button type="button" class="dp-preset" data-preset="esta_semana">Esta semana</button>
                <button type="button" class="dp-preset" data-preset="ultima_semana">Última semana</button>
                <button type="button" class="dp-preset" data-preset="este_mes">Este mes</button>
                <button type="button" class="dp-preset" data-preset="ultimo_mes">Último mes</button>
                <button type="button" class="dp-preset" data-preset="este_anio">Este año</button>
                <button type="button" class="dp-preset" data-preset="ultimo_anio">Último año</button>
            </div>
        </div>

        <!-- Botones OK / Cancelar -->
        <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.25rem; border-top:1px solid var(--border-color); padding-top:1rem;">
            <button type="button" id="dp-btn-cancelar" class="btn-modern btn-secondary">
                <i class="fa-solid fa-xmark"></i> Cancelar
            </button>
            <button type="button" id="dp-btn-ok" class="btn-modern btn-primary">
                <i class="fa-solid fa-check"></i> Ok
            </button>
        </div>
    </div>
</div>

<style>
.dp-calendar { user-select: none; }
.dp-calendar table { border-collapse: collapse; }
.dp-calendar th { color: var(--text-muted); font-size: 0.74rem; font-weight: 700; padding: 0.3rem 0.35rem; text-align: center; }
.dp-calendar td { text-align: center; padding: 0.2rem 0.22rem; }
.dp-calendar td span {
    display: inline-flex; align-items: center; justify-content: center;
    width: 31px; height: 31px; border-radius: 50%;
    font-size: 0.82rem; cursor: pointer; color: var(--text-main);
    transition: background 0.12s;
}
.dp-calendar td span:hover { background: #e8f0fe; color: var(--primary); }
.dp-calendar td span.dp-selected { background: var(--primary) !important; color: white !important; font-weight: 700; }
.dp-calendar td span.dp-in-range { background: rgba(37,99,235,0.1); border-radius: 0; color: var(--primary); }
.dp-calendar td span.dp-other-month { color: #c0cad8; }
.dp-calendar td span.dp-today { border: 2px solid var(--primary); font-weight: 700; }
.dp-cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem; }
.dp-cal-header span { color: var(--text-main); font-weight: 700; font-size: 0.88rem; }
.dp-cal-nav { background: none; border: 1px solid var(--border-color); color: var(--text-muted); font-size: 0.8rem; cursor: pointer; padding: 0.2rem 0.5rem; border-radius: 6px; }
.dp-cal-nav:hover { background: #f1f5f9; color: var(--primary); border-color: var(--primary); }
.dp-preset {
    padding: 0.42rem 0.85rem; border-radius: 8px; border: 1px solid var(--border-color);
    background: white; color: var(--text-main); font-size: 0.82rem; font-weight: 600;
    cursor: pointer; text-align: left; transition: background 0.13s, border-color 0.13s, color 0.13s;
}
.dp-preset:hover { background: #f1f5f9; border-color: var(--primary); color: var(--primary); }
.dp-preset.dp-preset-active { background: var(--primary); border-color: var(--primary); color: white; }
</style>

@push('modals')
<script>
(function() {
    var dpStartDate = null;
    var dpEndDate = null;
    var dpCalInicio = { year: new Date().getFullYear(), month: new Date().getMonth() };
    var dpCalFin = { year: new Date().getFullYear(), month: new Date().getMonth() };

    function pad(n) { return String(n).padStart(2, '0'); }
    function fmtYMD(d) { return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()); }
    function fmtDMY(d) { return pad(d.getDate()) + '/' + pad(d.getMonth()+1) + '/' + d.getFullYear(); }
    function parseYMD(s) { var p = s.split('-'); return new Date(+p[0], +p[1]-1, +p[2]); }
    var today = function() { var d = new Date(); d.setHours(0,0,0,0); return d; };

    // Initialize from current values
    var initStart = document.getElementById('hidden-fecha-inicio') ? document.getElementById('hidden-fecha-inicio').value : '';
    var initEnd = document.getElementById('hidden-fecha-fin') ? document.getElementById('hidden-fecha-fin').value : '';
    if (initStart) dpStartDate = parseYMD(initStart);
    if (initEnd) dpEndDate = parseYMD(initEnd);
    if (dpStartDate) { dpCalInicio.year = dpStartDate.getFullYear(); dpCalInicio.month = dpStartDate.getMonth(); }
    if (dpEndDate) { dpCalFin.year = dpEndDate.getFullYear(); dpCalFin.month = dpEndDate.getMonth(); }

    function updateLabel() {
        var s = dpStartDate ? fmtDMY(dpStartDate) : '--/--/----';
        var e = dpEndDate ? fmtDMY(dpEndDate) : '--/--/----';
        var el = document.getElementById('dp-label-rango');
        if (el) el.textContent = s + ' — ' + e;
    }

    var MESES = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    var DIAS = ['DO','LU','MA','MI','JU','VI','SA'];

    function renderCal(containerId, calState, selectFn) {
        var container = document.getElementById(containerId);
        if (!container) return;
        var y = calState.year, m = calState.month;
        var firstDay = new Date(y, m, 1).getDay();
        var daysInMonth = new Date(y, m+1, 0).getDate();
        var daysInPrev = new Date(y, m, 0).getDate();
        var todayD = today();

        var html = '<div class="dp-cal-header">';
        html += '<button type="button" class="dp-cal-nav" data-dir="-1">&#9664;</button>';
        html += '<span>' + MESES[m] + ' de ' + y + '</span>';
        html += '<button type="button" class="dp-cal-nav" data-dir="1">&#9654;</button>';
        html += '</div>';
        html += '<table><thead><tr>';
        DIAS.forEach(function(d) { html += '<th>' + d + '</th>'; });
        html += '</tr></thead><tbody><tr>';

        var day = 1, cellCount = 0;
        for (var i = 0; i < firstDay; i++) {
            html += '<td><span class="dp-other-month">' + (daysInPrev - firstDay + 1 + i) + '</span></td>';
            cellCount++;
        }
        while (day <= daysInMonth) {
            if (cellCount % 7 === 0 && cellCount > 0) html += '</tr><tr>';
            var d = new Date(y, m, day); d.setHours(0,0,0,0);
            var cls = '';
            if (d.getTime() === todayD.getTime()) cls += ' dp-today';
            var inRange = dpStartDate && dpEndDate && d > dpStartDate && d < dpEndDate;
            var isStart = dpStartDate && d.getTime() === dpStartDate.getTime();
            var isEnd = dpEndDate && d.getTime() === dpEndDate.getTime();
            if (isStart || isEnd) cls += ' dp-selected';
            else if (inRange) cls += ' dp-in-range';
            html += '<td><span class="' + cls.trim() + '" data-date="' + fmtYMD(d) + '">' + day + '</span></td>';
            day++; cellCount++;
        }
        var trailing = 1;
        while (cellCount % 7 !== 0) { html += '<td><span class="dp-other-month">' + trailing + '</span></td>'; trailing++; cellCount++; }
        html += '</tr></tbody></table>';
        container.innerHTML = html;

        container.querySelectorAll('.dp-cal-nav').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var dir = parseInt(this.getAttribute('data-dir'));
                calState.month += dir;
                if (calState.month > 11) { calState.month = 0; calState.year++; }
                if (calState.month < 0) { calState.month = 11; calState.year--; }
                renderBoth();
            });
        });
        container.querySelectorAll('span[data-date]').forEach(function(span) {
            span.addEventListener('click', function() { selectFn(parseYMD(this.getAttribute('data-date'))); });
        });
    }

    function renderBoth() {
        renderCal('cal-inicio', dpCalInicio, function(d) {
            dpStartDate = d;
            if (dpEndDate && dpEndDate < dpStartDate) dpEndDate = null;
            renderBoth(); updateLabel(); updatePresetHighlight(null);
        });
        renderCal('cal-fin', dpCalFin, function(d) {
            dpEndDate = d;
            if (dpStartDate && dpStartDate > dpEndDate) dpStartDate = null;
            renderBoth(); updateLabel(); updatePresetHighlight(null);
        });
        updateLabel();
    }

    function updatePresetHighlight(preset) {
        document.querySelectorAll('.dp-preset').forEach(function(b) {
            b.classList.toggle('dp-preset-active', b.getAttribute('data-preset') === preset);
        });
    }

    function closeModal() { document.getElementById('modal-datepicker').style.display = 'none'; }

    var btnAbrir = document.getElementById('btn-abrir-datepicker');
    if (btnAbrir) btnAbrir.addEventListener('click', function() {
        document.getElementById('modal-datepicker').style.display = 'flex';
        renderBoth();
    });

    var btnCerrarX = document.getElementById('dp-btn-cerrar-x');
    if (btnCerrarX) btnCerrarX.addEventListener('click', closeModal);

    var btnCancelar = document.getElementById('dp-btn-cancelar');
    if (btnCancelar) btnCancelar.addEventListener('click', closeModal);

    var btnOk = document.getElementById('dp-btn-ok');
    if (btnOk) btnOk.addEventListener('click', function() {
        if (!dpStartDate || !dpEndDate) { alert('Selecciona una fecha de inicio y fin.'); return; }
        document.getElementById('hidden-fecha-inicio').value = fmtYMD(dpStartDate);
        document.getElementById('hidden-fecha-fin').value = fmtYMD(dpEndDate);
        document.getElementById('label-rango-fecha').textContent = fmtDMY(dpStartDate) + ' — ' + fmtDMY(dpEndDate);
        closeModal();
        document.getElementById('form-filtros-reporte').submit();
    });

    document.querySelectorAll('.dp-preset').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var preset = this.getAttribute('data-preset');
            var t = today(); var s, e;
            if (preset === 'hoy') { s = new Date(t); e = new Date(t); }
            else if (preset === 'ayer') { s = new Date(t); s.setDate(s.getDate()-1); e = new Date(s); }
            else if (preset === 'esta_semana') { s = new Date(t); s.setDate(t.getDate()-t.getDay()); e = new Date(s); e.setDate(s.getDate()+6); }
            else if (preset === 'ultima_semana') { s = new Date(t); s.setDate(t.getDate()-t.getDay()-7); e = new Date(s); e.setDate(s.getDate()+6); }
            else if (preset === 'este_mes') { s = new Date(t.getFullYear(),t.getMonth(),1); e = new Date(t.getFullYear(),t.getMonth()+1,0); }
            else if (preset === 'ultimo_mes') { s = new Date(t.getFullYear(),t.getMonth()-1,1); e = new Date(t.getFullYear(),t.getMonth(),0); }
            else if (preset === 'este_anio') { s = new Date(t.getFullYear(),0,1); e = new Date(t.getFullYear(),11,31); }
            else if (preset === 'ultimo_anio') { s = new Date(t.getFullYear()-1,0,1); e = new Date(t.getFullYear()-1,11,31); }
            dpStartDate = s; dpEndDate = e;
            dpCalInicio.year = s.getFullYear(); dpCalInicio.month = s.getMonth();
            dpCalFin.year = e.getFullYear(); dpCalFin.month = e.getMonth();
            renderBoth(); updatePresetHighlight(preset);
        });
    });

    var mdp = document.getElementById('modal-datepicker');
    if (mdp) mdp.addEventListener('click', function(e) { if (e.target === this) closeModal(); });
})();
</script>
@endpush


<!-- PESTAÑA 1: VENTAS -->
@if($tab === 'ventas')
    <!-- Tarjetas KPI Ventas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">VENTAS NETAS</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">${{ number_format($totalVentasNeto, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                Bruto: ${{ number_format($totalVentasMonto, 2) }} @if($totalDevolucionesMonto > 0) | <span style="color: #dc2626;">Devoluciones: -${{ number_format($totalDevolucionesMonto, 2) }}</span> @endif
            </div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">VOLUMEN VENDIDO</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">{{ number_format($totalPesoVendido, 2) }} <span style="font-size: 1rem;">{{ $unidadPeso }}</span></div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Peso neto comercializado en el rango</div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Nº TRANSACCIONES</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #6366f1; margin-top: 0.25rem;">{{ $numVentas }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                @if($countDevoluciones > 0)
                    <span style="color: #dc2626; font-weight: 600;">{{ $countDevoluciones }} devolución(es)</span>
                @else
                    Tickets/Ventas cobradas
                @endif
            </div>
        </div>

        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">PROMEDIO POR TICKET</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #f59e0b; margin-top: 0.25rem;">${{ number_format($promedioVenta, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Ticket promedio alcanzado</div>
        </div>
    </div>

    <!-- TARJETA: GRÁFICO COMPARATIVO INTELIGENTE (VENTAS) -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.5rem; position: relative;">
        <!-- Fila 1: Cabecera con Navegación de Niveles (Zoom Out / In) -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.85rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <!-- Botón Flecha Atrás (Drill Up) -->
                <button type="button" id="btn-back-level-ventas" onclick="navegarNivelAtras('ventas')" class="btn-modern btn-secondary" style="display: none; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 700; gap: 0.4rem; align-items: center;" title="Regresar al nivel anterior">
                    <i class="fa-solid fa-arrow-left"></i> <span id="label-back-level-ventas">Volver</span>
                </button>

                <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.5rem; color: var(--text-main);">
                    <i class="fa-solid fa-chart-line" style="color: var(--primary);"></i> 
                    <span id="titulo-grafico-ventas">Comparativo de Ventas</span>
                </h3>
            </div>
            
            <!-- Selector de Niveles (Pills) -->
            <div style="display: flex; align-items: center; gap: 0.4rem; background: #f1f5f9; padding: 3px; border-radius: 8px; flex-wrap: wrap;">
                <button type="button" id="pill-nivel-anual-ventas" class="btn-nivel-ventas" onclick="setNivelGrafico('ventas', 'anual')" style="padding: 0.35rem 0.7rem; font-size: 0.78rem; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted);">
                    <i class="fa-solid fa-chart-column"></i> Anual (12 Meses)
                </button>
                <button type="button" id="pill-nivel-mensual-ventas" class="btn-nivel-ventas active" onclick="setNivelGrafico('ventas', 'mensual')" style="padding: 0.35rem 0.7rem; font-size: 0.78rem; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; background: white; color: var(--primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <i class="fa-solid fa-calendar-days"></i> Mensual (Día 1-31)
                </button>
                <button type="button" id="pill-nivel-semanal-ventas" class="btn-nivel-ventas" onclick="setNivelGrafico('ventas', 'semanal')" style="padding: 0.35rem 0.7rem; font-size: 0.78rem; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted);">
                    <i class="fa-solid fa-calendar-week"></i> Semanal (Lun-Dom)
                </button>
            </div>
        </div>

        <!-- Fila 2: Barra de Selección de Período Base y Comparación -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; background: #f8fafc; padding: 0.65rem 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
            <!-- Controles Período Base -->
            <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Período Base:</span>
                
                <button type="button" onclick="periodoPasoAnterior('ventas')" class="btn-modern btn-secondary" style="padding: 0.3rem 0.55rem; font-size: 0.8rem;" title="Período anterior">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <!-- Selector de Meses Base -->
                <select id="select-mes-base-ventas" onchange="onSelectMesBaseChange('ventas', this.value)" class="input-modern" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; font-weight: 700; background: white; color: var(--primary); min-width: 150px;">
                </select>

                <!-- Selector de Semanas Base -->
                <select id="select-semana-base-ventas" onchange="onSelectSemanaBaseChange('ventas', this.value)" class="input-modern" style="display: none; padding: 0.3rem 0.6rem; font-size: 0.85rem; font-weight: 700; background: white; color: var(--primary); min-width: 190px;">
                </select>

                <!-- Selector de Años Base -->
                <select id="select-ano-base-ventas" onchange="onSelectAnoBaseChange('ventas', this.value)" class="input-modern" style="display: none; padding: 0.3rem 0.6rem; font-size: 0.85rem; font-weight: 700; background: white; color: var(--primary); min-width: 100px;">
                </select>

                <button type="button" onclick="periodoPasoSiguiente('ventas')" class="btn-modern btn-secondary" style="padding: 0.3rem 0.55rem; font-size: 0.8rem;" title="Período siguiente">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>

                <!-- Botón Flecha Adelante (Drill Down hacia Semanas cuando está en Mensual) -->
                <button type="button" id="btn-forward-weeks-ventas" onclick="desglosarEnSemanas('ventas')" class="btn-modern btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.8rem; font-weight: 700; gap: 0.35rem; align-items: center; margin-left: 0.35rem;" title="Desglosar este mes en semanas específicas">
                    <span>Ver en Semanas</span> <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>

            <!-- Controles Período a Comparar -->
            <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Comparar contra:</span>
                <select id="select-comparar-ventas" onchange="onSelectCompararChange('ventas', this.value)" class="input-modern" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; font-weight: 600; background: white; min-width: 210px;">
                </select>
            </div>
        </div>

        <!-- Fila 3: Barra Resumen KPI Dinámica -->
        <div id="resumen-comparativo-ventas" style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem; padding: 0.75rem 1rem; background: rgba(37,99,235,0.04); border-radius: 8px; border-left: 4px solid var(--primary);">
            <div style="font-size: 0.85rem;">
                <span style="color: var(--text-muted); font-weight: 600;" id="kpi-label-base-ventas">Período Base:</span>
                <strong style="color: var(--primary); font-size: 1.15rem; margin-left: 0.35rem;" id="kpi-val-base-ventas">$0.00</strong>
            </div>
            <div style="font-size: 0.85rem;" id="kpi-box-comp-ventas">
                <span style="color: var(--text-muted); font-weight: 600;" id="kpi-label-comp-ventas">Período Comparado:</span>
                <strong style="color: #64748b; font-size: 1.15rem; margin-left: 0.35rem;" id="kpi-val-comp-ventas">$0.00</strong>
            </div>
            <div style="font-size: 0.85rem; margin-left: auto; display: flex; align-items: center; gap: 0.5rem;" id="kpi-box-diff-ventas">
                <span style="color: var(--text-muted); font-weight: 600;">Diferencia:</span>
                <strong style="font-size: 1rem;" id="kpi-val-diff-monto-ventas">+$0.00</strong>
                <span class="badge badge-success" id="kpi-badge-diff-ventas" style="font-weight: 800; font-size: 0.9rem;">+0.0%</span>
            </div>
        </div>

        <!-- Fila 4: Contenedor del Gráfico -->
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="canvasChartVentas"></canvas>
            <div id="chart-loading-ventas" style="display: none; position: absolute; inset: 0; background: rgba(255,255,255,0.75); backdrop-filter: blur(2px); align-items: center; justify-content: center; font-weight: 700; color: var(--primary); font-size: 0.95rem; border-radius: 8px;">
                <i class="fa-solid fa-spinner fa-spin" style="margin-right: 0.5rem; font-size: 1.2rem;"></i> Cargando comparativa...
            </div>
        </div>
    </div>

    <!-- Sección Secundaria: Top Productos & Métodos de Pago -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
        <!-- Top Productos -->
        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-trophy" style="color: #f59e0b;"></i> Top 10 Productos Más Vendidos
                </h3>
                <a href="{{ route('reportes.index', ['tab' => 'kardex', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}" 
                   class="btn-modern btn-secondary" 
                   style="padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem;" 
                   title="Ir a la vista completa de rotación y ventas de todo el inventario">
                    <i class="fa-solid fa-arrow-up-right-from-square" style="color: var(--primary);"></i> Ver Todos los del Inventario
                </a>
            </div>

            <div style="overflow-x: auto; flex: 1;">
                <table class="table-modern" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th>Producto / SKU</th>
                            <th style="text-align: right;">Cantidad</th>
                            <th style="text-align: right;">Total ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProductosVendidos as $index => $top)
                            <tr>
                                <td style="text-align: center;">
                                    @if($index === 0)
                                        <span class="badge" style="background: #fef3c7; color: #b45309; font-weight: 800;">🥇 1</span>
                                    @elseif($index === 1)
                                        <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 800;">🥈 2</span>
                                    @elseif($index === 2)
                                        <span class="badge" style="background: #ffedd5; color: #c2410c; font-weight: 800;">🥉 3</span>
                                    @else
                                        <span class="badge badge-info" style="font-weight: 700;">{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-main);">
                                        {{ $top->articulo ? $top->articulo->descripcion : 'Producto Eliminado' }}
                                    </div>
                                    @if($top->articulo)
                                        <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                            SKU: {{ $top->articulo->codigo }}
                                            @if($top->articulo->codigo_cliente)
                                                | Prov: {{ $top->articulo->codigo_cliente }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align: right; font-weight: 700; white-space: nowrap;">
                                    {{ number_format($top->total_cantidad, 3) }} {{ $unidadPeso }}
                                </td>
                                <td style="text-align: right; color: var(--primary); font-weight: 800; white-space: nowrap;">
                                    ${{ number_format($top->total_monto, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                    No hay ventas registradas en el período.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 0.85rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); text-align: right;">
                <a href="{{ route('reportes.index', ['tab' => 'kardex', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}" 
                   style="font-size: 0.82rem; font-weight: 700; color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 0.35rem;">
                    Ver ranking, rotación y cobertura de todos los productos del inventario <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0;">Historial Detallado de Ventas</h3>
            
            <form method="GET" action="{{ route('reportes.index') }}" style="display: flex; gap: 0.5rem; align-items: center; margin: 0;">
                <input type="hidden" name="tab" value="ventas">
                <input type="hidden" name="fecha_inicio" value="{{ $fechaInicio }}">
                <input type="hidden" name="fecha_fin" value="{{ $fechaFin }}">
                <label style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted);">Filtrar por Tipo:</label>
                <select name="filtro_venta" class="input-modern" onchange="this.form.submit()" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 600; min-width: 190px;">
                    <option value="todas" {{ ($filtroVenta ?? 'todas') === 'todas' ? 'selected' : '' }}>Todas las Ventas</option>
                    <option value="contado" {{ ($filtroVenta ?? '') === 'contado' ? 'selected' : '' }}>Ventas al Contado</option>
                    <option value="credito" {{ ($filtroVenta ?? '') === 'credito' ? 'selected' : '' }}>Ventas a Crédito</option>
                    <option value="devolucion" {{ ($filtroVenta ?? '') === 'devolucion' ? 'selected' : '' }}>Ventas con Devolución</option>
                </select>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>N° Ticket</th>
                        <th>Fecha / Hora</th>
                        <th>Estado</th>
                        <th>Método Pago</th>
                        <th style="text-align: right;">Subtotal</th>
                        <th style="text-align: right;">Impuestos</th>
                        <th style="text-align: right;">Total Cobrado</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ventasLista as $v)
                        <tr style="{{ $v->estado === 'devuelta' ? 'background: rgba(239, 68, 68, 0.04);' : ($v->estado === 'parcialmente_devuelta' ? 'background: rgba(245, 158, 11, 0.04);' : ($v->tipo_venta === 'credito' ? 'background: rgba(217, 119, 6, 0.03);' : '')) }}">
                            <td style="font-weight: 700; font-family: monospace; color: var(--primary);">#{{ str_pad($v->id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $v->created_at->format('d/m/Y h:i A') }}</td>
                            <td>
                                @php
                                    $st = $v->estado;
                                    $tipo = $v->tipo_venta;
                                    $crSt = $v->credito_info['estado_credito'] ?? 'pendiente';
                                    $saldoPen = $v->credito_info['saldo_pendiente'] ?? 0;
                                @endphp

                                @if($st === 'devuelta')
                                    <span class="badge" style="background: rgba(220, 38, 38, 0.15); color: #dc2626; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-rotate-left"></i> Devuelto
                                    </span>
                                @elseif($st === 'parcialmente_devuelta')
                                    <span class="badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-rotate-left"></i> Parc. Devuelto
                                    </span>
                                @elseif($tipo === 'credito' && $crSt === 'saldado')
                                    <span class="badge badge-success" style="font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-circle-check"></i> Crédito Cancelado
                                    </span>
                                @elseif($tipo === 'credito' && $crSt === 'parcial')
                                    <span class="badge badge-warning" style="font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-clock-rotate-left"></i> Crédito Parcial (${{ number_format($saldoPen, 2) }} pend.)
                                    </span>
                                @elseif($tipo === 'credito')
                                    <span class="badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-clock"></i> Crédito Pendiente
                                    </span>
                                @else
                                    <span class="badge badge-success" style="font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-check"></i> Pagado (Contado)
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($v->tipo_venta === 'credito')
                                    <span class="badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                        CRÉDITO
                                    </span>
                                @else
                                    <span class="badge badge-info" style="text-transform: uppercase;">{{ $v->metodo_pago }}</span>
                                @endif
                            </td>
                            <td style="text-align: right;">${{ number_format($v->subtotal, 2) }}</td>
                            <td style="text-align: right;">${{ number_format($v->impuestos, 2) }}</td>
                            <td style="text-align: right; font-weight: 800; color: {{ $v->estado === 'devuelta' ? '#dc2626' : 'var(--primary)' }};">
                                @if($v->estado === 'devuelta')
                                    <span style="text-decoration: line-through;">${{ number_format($v->total, 2) }}</span>
                                    <div style="font-size: 0.75rem; color: #dc2626; font-weight: 700;">(Reembolsado)</div>
                                @elseif($v->estado === 'parcialmente_devuelta')
                                    ${{ number_format($v->total, 2) }}
                                    @if($v->devoluciones->isNotEmpty())
                                        <div style="font-size: 0.75rem; color: #dc2626; font-weight: 600;">(-${{ number_format($v->devoluciones->sum('total_reembolsado'), 2) }})</div>
                                    @endif
                                @else
                                    ${{ number_format($v->total, 2) }}
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-modern btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.82rem;" onclick="abrirDocumentoVenta({{ json_encode($v) }})">
                                    <i class="fa-solid fa-file-invoice-dollar" style="color: var(--primary);"></i> Ver Documento
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No se encontraron registros de ventas en este rango de fechas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span>Mostrar:</span>
                <select onchange="cambiarPaginacion(this.value)" class="input-modern" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.85rem; font-weight: 700; background: white;">
                    <option value="10" @selected($perPage == 10)>10 por página</option>
                    <option value="15" @selected($perPage == 15)>15 por página</option>
                    <option value="25" @selected($perPage == 25)>25 por página</option>
                    <option value="50" @selected($perPage == 50)>50 por página</option>
                    <option value="100" @selected($perPage == 100)>100 por página</option>
                    <option value="99999" @selected($perPage >= 99999)>Todos los registros</option>
                </select>
                <span>(Total: {{ $ventasLista->total() }})</span>
            </div>
            <div>
                {{ $ventasLista->appends(['tab' => 'ventas', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'filtro_venta' => $filtroVenta ?? 'todas'])->links() }}
            </div>
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

    <!-- TARJETA: GRÁFICO COMPARATIVO INTELIGENTE (COMPRAS) -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.5rem; position: relative;">
        <!-- Fila 1: Cabecera con Navegación de Niveles (Zoom Out / In) -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.85rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <!-- Botón Flecha Atrás (Drill Up) -->
                <button type="button" id="btn-back-level-compras" onclick="navegarNivelAtras('compras')" class="btn-modern btn-secondary" style="display: none; padding: 0.35rem 0.75rem; font-size: 0.8rem; font-weight: 700; gap: 0.4rem; align-items: center;" title="Regresar al nivel anterior">
                    <i class="fa-solid fa-arrow-left"></i> <span id="label-back-level-compras">Volver</span>
                </button>

                <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.5rem; color: var(--text-main);">
                    <i class="fa-solid fa-chart-area" style="color: var(--accent);"></i> 
                    <span id="titulo-grafico-compras">Comparativo de Inversión en Compras</span>
                </h3>
            </div>
            
            <!-- Selector de Niveles (Pills) -->
            <div style="display: flex; align-items: center; gap: 0.4rem; background: #f1f5f9; padding: 3px; border-radius: 8px; flex-wrap: wrap;">
                <button type="button" id="pill-nivel-anual-compras" class="btn-nivel-compras" onclick="setNivelGrafico('compras', 'anual')" style="padding: 0.35rem 0.7rem; font-size: 0.78rem; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted);">
                    <i class="fa-solid fa-chart-column"></i> Anual (12 Meses)
                </button>
                <button type="button" id="pill-nivel-mensual-compras" class="btn-nivel-compras active" onclick="setNivelGrafico('compras', 'mensual')" style="padding: 0.35rem 0.7rem; font-size: 0.78rem; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; background: white; color: var(--accent); box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <i class="fa-solid fa-calendar-days"></i> Mensual (Día 1-31)
                </button>
                <button type="button" id="pill-nivel-semanal-compras" class="btn-nivel-compras" onclick="setNivelGrafico('compras', 'semanal')" style="padding: 0.35rem 0.7rem; font-size: 0.78rem; font-weight: 700; border: none; border-radius: 6px; cursor: pointer; background: transparent; color: var(--text-muted);">
                    <i class="fa-solid fa-calendar-week"></i> Semanal (Lun-Dom)
                </button>
            </div>
        </div>

        <!-- Fila 2: Barra de Selección de Período Base y Comparación -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem; background: #f8fafc; padding: 0.65rem 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
            <!-- Controles Período Base -->
            <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Período Base:</span>
                
                <button type="button" onclick="periodoPasoAnterior('compras')" class="btn-modern btn-secondary" style="padding: 0.3rem 0.55rem; font-size: 0.8rem;" title="Período anterior">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <!-- Selector de Meses Base -->
                <select id="select-mes-base-compras" onchange="onSelectMesBaseChange('compras', this.value)" class="input-modern" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; font-weight: 700; background: white; color: var(--accent); min-width: 150px;">
                </select>

                <!-- Selector de Semanas Base -->
                <select id="select-semana-base-compras" onchange="onSelectSemanaBaseChange('compras', this.value)" class="input-modern" style="display: none; padding: 0.3rem 0.6rem; font-size: 0.85rem; font-weight: 700; background: white; color: var(--accent); min-width: 190px;">
                </select>

                <!-- Selector de Años Base -->
                <select id="select-ano-base-compras" onchange="onSelectAnoBaseChange('compras', this.value)" class="input-modern" style="display: none; padding: 0.3rem 0.6rem; font-size: 0.85rem; font-weight: 700; background: white; color: var(--accent); min-width: 100px;">
                </select>

                <button type="button" onclick="periodoPasoSiguiente('compras')" class="btn-modern btn-secondary" style="padding: 0.3rem 0.55rem; font-size: 0.8rem;" title="Período siguiente">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>

                <!-- Botón Flecha Adelante (Drill Down hacia Semanas cuando está en Mensual) -->
                <button type="button" id="btn-forward-weeks-compras" onclick="desglosarEnSemanas('compras')" class="btn-modern btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.8rem; font-weight: 700; gap: 0.35rem; align-items: center; margin-left: 0.35rem;" title="Desglosar este mes en semanas específicas">
                    <span>Ver en Semanas</span> <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>

            <!-- Controles Período a Comparar -->
            <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Comparar contra:</span>
                <select id="select-comparar-compras" onchange="onSelectCompararChange('compras', this.value)" class="input-modern" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; font-weight: 600; background: white; min-width: 210px;">
                </select>
            </div>
        </div>

        <!-- Fila 3: Barra Resumen KPI Dinámica -->
        <div id="resumen-comparativo-compras" style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; margin-bottom: 1rem; padding: 0.75rem 1rem; background: rgba(249,115,22,0.04); border-radius: 8px; border-left: 4px solid var(--accent);">
            <div style="font-size: 0.85rem;">
                <span style="color: var(--text-muted); font-weight: 600;" id="kpi-label-base-compras">Período Base:</span>
                <strong style="color: var(--accent); font-size: 1.15rem; margin-left: 0.35rem;" id="kpi-val-base-compras">$0.00</strong>
            </div>
            <div style="font-size: 0.85rem;" id="kpi-box-comp-compras">
                <span style="color: var(--text-muted); font-weight: 600;" id="kpi-label-comp-compras">Período Comparado:</span>
                <strong style="color: #64748b; font-size: 1.15rem; margin-left: 0.35rem;" id="kpi-val-comp-compras">$0.00</strong>
            </div>
            <div style="font-size: 0.85rem; margin-left: auto; display: flex; align-items: center; gap: 0.5rem;" id="kpi-box-diff-compras">
                <span style="color: var(--text-muted); font-weight: 600;">Diferencia:</span>
                <strong style="font-size: 1rem;" id="kpi-val-diff-monto-compras">+$0.00</strong>
                <span class="badge badge-success" id="kpi-badge-diff-compras" style="font-weight: 800; font-size: 0.9rem;">+0.0%</span>
            </div>
        </div>

        <!-- Fila 4: Contenedor del Gráfico -->
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="canvasChartCompras"></canvas>
            <div id="chart-loading-compras" style="display: none; position: absolute; inset: 0; background: rgba(255,255,255,0.75); backdrop-filter: blur(2px); align-items: center; justify-content: center; font-weight: 700; color: var(--accent); font-size: 0.95rem; border-radius: 8px;">
                <i class="fa-solid fa-spinner fa-spin" style="margin-right: 0.5rem; font-size: 1.2rem;"></i> Cargando comparativa...
            </div>
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
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span>Mostrar:</span>
                <select onchange="cambiarPaginacion(this.value)" class="input-modern" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.85rem; font-weight: 700; background: white;">
                    <option value="10" @selected($perPage == 10)>10 por página</option>
                    <option value="15" @selected($perPage == 15)>15 por página</option>
                    <option value="25" @selected($perPage == 25)>25 por página</option>
                    <option value="50" @selected($perPage == 50)>50 por página</option>
                    <option value="100" @selected($perPage == 100)>100 por página</option>
                    <option value="99999" @selected($perPage >= 99999)>Todos los registros</option>
                </select>
                <span>(Total: {{ $comprasLista->total() }})</span>
            </div>
            <div>
                {{ $comprasLista->appends(['tab' => 'compras', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin])->links() }}
            </div>
        </div>
    </div>
@endif

<!-- PESTAÑA REPORTE DE CAJA -->
@if($tab === 'caja')
    <!-- Tarjetas de Resumen KPI de Caja -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--primary);">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">VTAS. EN EFECTIVO</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">${{ number_format($totalCajaEfectivo, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Cobrado en efectivo</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid #10b981;">
            <div style="font-size: 0.85rem; color: #10b981; font-weight: 700;">💳 TARJETA / TRANSFERENCIA</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">${{ number_format($totalCajaTarjeta + $totalCajaTransferencia, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Tarjeta: ${{ number_format($totalCajaTarjeta, 2) }} | Transf: ${{ number_format($totalCajaTransferencia, 2) }}</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid #3b82f6;">
            <div style="font-size: 0.85rem; color: #3b82f6; font-weight: 700;">📥 ENTRADAS / 📤 SALIDAS</div>
            <div style="font-size: 1.4rem; font-weight: 800; color: #1e293b; margin-top: 0.25rem;">
                <span style="color: #10b981;">+${{ number_format($totalCajaEntradas, 2) }}</span> / 
                <span style="color: #ef4444;">-${{ number_format($totalCajaSalidas, 2) }}</span>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Movimientos manuales de caja</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid {{ $totalCajaDiferencia < 0 ? '#ef4444' : ($totalCajaDiferencia > 0 ? '#f59e0b' : '#10b981') }};">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">DESCUADRE ACUMULADO</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: {{ $totalCajaDiferencia < 0 ? '#ef4444' : ($totalCajaDiferencia > 0 ? '#f59e0b' : '#10b981') }}; margin-top: 0.25rem;">
                ${{ number_format($totalCajaDiferencia, 2) }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                Turnos: {{ $numCajasCerradas }} cerrados | {{ $numCajasAbiertas }} activos
            </div>
        </div>
    </div>

    <!-- Tabla Historial de Turnos de Caja -->
    <div class="card" style="padding: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-vault" style="color: var(--primary);"></i> Historial de Turnos y Cierres de Caja</h3>
            <span class="badge badge-info" style="font-size: 0.85rem;">{{ $cajasLista->total() }} turnos registrados</span>
        </div>

        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>ID Turno</th>
                        <th>Cajero / Usuario</th>
                        <th>Estado</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th style="text-align: right;">Fondo Inicial</th>
                        <th style="text-align: right;">Saldo Esperado</th>
                        <th style="text-align: right;">Saldo Real</th>
                        <th style="text-align: right;">Diferencia</th>
                        <th style="text-align: center;">Ticket Z</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cajasLista as $sesion)
                        @php
                            $dif = (float) $sesion->diferencia;
                            $difColor = '#10b981';
                            $difLabel = 'Cuadrada';
                            if ($dif < 0) {
                                $difColor = '#ef4444';
                                $difLabel = 'Faltante';
                            } elseif ($dif > 0) {
                                $difColor = '#f59e0b';
                                $difLabel = 'Sobrante';
                            }
                        @endphp
                        <tr>
                            <td style="font-weight: 700; font-family: monospace;">#{{ $sesion->id }}</td>
                            <td>{{ $sesion->user?->name ?: 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $sesion->estado === 'abierta' ? 'badge-success' : 'badge-warning' }}">
                                    {{ strtoupper($sesion->estado) }}
                                </span>
                            </td>
                            <td style="font-size: 0.85rem; font-family: monospace;">
                                {{ $sesion->fecha_apertura ? $sesion->fecha_apertura->format('Y-m-d H:i') : '-' }}
                            </td>
                            <td style="font-size: 0.85rem; font-family: monospace;">
                                {{ $sesion->fecha_cierre ? $sesion->fecha_cierre->format('Y-m-d H:i') : 'Activa' }}
                            </td>
                            <td style="text-align: right;">${{ number_format($sesion->monto_inicial, 2) }}</td>
                            <td style="text-align: right; font-weight: 600;">${{ number_format($sesion->saldo_esperado, 2) }}</td>
                            <td style="text-align: right; font-weight: 700; color: var(--primary);">${{ number_format($sesion->saldo_real, 2) }}</td>
                            <td style="text-align: right; font-weight: 700; color: {{ $difColor }};">
                                ${{ number_format($dif, 2) }}
                                <div style="font-size: 0.72rem; font-weight: 600;">{{ $difLabel }}</div>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-modern btn-secondary js-btn-ticket-z" data-url="{{ route('caja.ticketCierre', $sesion->id) }}" style="padding: 0.35rem 0.65rem; font-size: 0.8rem; color: #10b981; border-color: #a7f3d0;" title="Ver Ticket Z">
                                    <i class="fa-solid fa-receipt"></i> Ticket Z
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 2rem;">No hay sesiones de caja registradas en el rango de fechas seleccionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span>Mostrar:</span>
                <select onchange="cambiarPaginacion(this.value)" class="input-modern" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.85rem; font-weight: 700; background: white;">
                    <option value="10" @selected($perPage == 10)>10 por página</option>
                    <option value="15" @selected($perPage == 15)>15 por página</option>
                    <option value="25" @selected($perPage == 25)>25 por página</option>
                    <option value="50" @selected($perPage == 50)>50 por página</option>
                    <option value="100" @selected($perPage == 100)>100 por página</option>
                    <option value="99999" @selected($perPage >= 99999)>Todos los registros</option>
                </select>
                <span>(Total: {{ $cajasLista->total() }})</span>
            </div>
            <div>
                {{ $cajasLista->appends(['tab' => 'caja', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin])->links() }}
            </div>
        </div>
    </div>
@endif

<!-- PESTAÑA: KARDEX DE INVENTARIO Y ROTACIÓN -->
@if($tab === 'kardex')
    @if($articuloKardex)
        <!-- RESUMEN DEL ARTÍCULO SELECCIONADO -->
        <div class="card" style="padding: 1.25rem; margin-bottom: 1.5rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: 5px solid var(--primary);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <div style="font-size: 0.8rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em;">
                        <i class="fa-solid fa-barcode"></i> SKU: {{ $articuloKardex->codigo }} @if($articuloKardex->codigo_cliente) | Cód. Proveedor: {{ $articuloKardex->codigo_cliente }} @endif
                    </div>
                    <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--text-main); margin: 0.25rem 0 0.5rem 0;">
                        {{ $articuloKardex->descripcion }}
                    </h2>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; font-size: 0.85rem; color: var(--text-muted);">
                        <span><i class="fa-solid fa-tag"></i> Familia: <strong>{{ $articuloKardex->familia?->nombre ?? 'Sin Familia' }}</strong></span>
                        <span><i class="fa-solid fa-weight-hanging"></i> Unidad: <strong>{{ $unidadPeso }}</strong></span>
                        <span><i class="fa-solid fa-dollar-sign"></i> Costo Compra: <strong>${{ number_format($articuloKardex->precio_compra ?: $articuloKardex->precio_sin_iva, 2) }}</strong></span>
                        <span><i class="fa-solid fa-receipt"></i> PVP: <strong>${{ number_format($articuloKardex->pvp, 2) }}</strong></span>
                    </div>
                </div>
                <div style="text-align: right;">
                    <a href="{{ route('articulos.edit', $articuloKardex->id) }}" class="btn-modern btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.82rem;">
                        <i class="fa-solid fa-pen-to-square"></i> Editar Catálogo
                    </a>
                </div>
            </div>
        </div>

        <!-- TARJETAS BALANCE PROGRESIVO DE STOCK -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div class="card" style="padding: 1.1rem; border-left: 4px solid #64748b;">
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700;">STOCK INICIAL PERÍODO</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #334155; margin-top: 0.25rem;">
                    {{ number_format($stockInicialPeriodo, 3) }} <span style="font-size: 0.85rem; font-weight: 600;">{{ $unidadPeso }}</span>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">Previo al {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }}</div>
            </div>

            <div class="card" style="padding: 1.1rem; border-left: 4px solid #10b981;">
                <div style="font-size: 0.8rem; color: #10b981; font-weight: 700;">(+) ENTRADAS (COMPRAS)</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">
                    +{{ number_format($totalKardexEntradas, 3) }} <span style="font-size: 0.85rem; font-weight: 600;">{{ $unidadPeso }}</span>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">Mercancía recibida</div>
            </div>

            <div class="card" style="padding: 1.1rem; border-left: 4px solid #ef4444;">
                <div style="font-size: 0.8rem; color: #ef4444; font-weight: 700;">(-) SALIDAS (VENTAS)</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #ef4444; margin-top: 0.25rem;">
                    -{{ number_format($totalKardexSalidas, 3) }} <span style="font-size: 0.85rem; font-weight: 600;">{{ $unidadPeso }}</span>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">Ventas contado y crédito</div>
            </div>

            <div class="card" style="padding: 1.1rem; border-left: 4px solid #d97706;">
                <div style="font-size: 0.8rem; color: #d97706; font-weight: 700;">(+) DEVOLUCIONES</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #d97706; margin-top: 0.25rem;">
                    +{{ number_format($totalKardexDevoluciones, 3) }} <span style="font-size: 0.85rem; font-weight: 600;">{{ $unidadPeso }}</span>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">Reingresos al stock</div>
            </div>

            <div class="card" style="padding: 1.1rem; border-left: 4px solid var(--primary); background: rgba(37,99,235,0.03);">
                <div style="font-size: 0.8rem; color: var(--primary); font-weight: 700;">(=) SALDO RESULTANTE</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">
                    {{ number_format($stockFinalKardex, 3) }} <span style="font-size: 0.85rem; font-weight: 600;">{{ $unidadPeso }}</span>
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">Stock actual físico: {{ number_format($articuloKardex->stock, 3) }} {{ $unidadPeso }}</div>
            </div>
        </div>

        <!-- TABLA DETALLE DE KARDEX -->
        <div class="card" style="padding: 1.25rem; margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-timeline" style="color: var(--primary);"></i> Historial Cronológico de Movimientos (Kardex)
                    </h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                        Trazabilidad completa con balance progresivo de existencias.
                    </p>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <a href="{{ route('reportes.exportar', ['tipo' => 'kardex', 'articulo_id' => $articuloKardex->id, 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}" class="btn-modern btn-accent" style="padding: 0.4rem 0.8rem; font-size: 0.82rem;">
                        <i class="fa-solid fa-file-excel"></i> Exportar Kardex
                    </a>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="table-modern" style="width: 100%; font-size: 0.88rem;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th>Fecha / Hora</th>
                            <th>Tipo Movimiento</th>
                            <th>Comprobante / Folio</th>
                            <th>Tercero (Cliente / Prov.)</th>
                            <th>Lote / Serie</th>
                            <th style="text-align: right; color: #10b981;">Entrada ({{ $unidadPeso }})</th>
                            <th style="text-align: right; color: #10b981;">Costo Unit.</th>
                            <th style="text-align: right; color: #10b981;">Total Entrada</th>
                            <th style="text-align: right; color: #ef4444;">Salida ({{ $unidadPeso }})</th>
                            <th style="text-align: right; color: #ef4444;">Precio Unit.</th>
                            <th style="text-align: right; color: #ef4444;">Total Salida</th>
                            <th style="text-align: right; color: var(--primary); font-weight: 800;">Saldo Stock ({{ $unidadPeso }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($stockInicialPeriodo > 0)
                            <tr style="background: rgba(100, 116, 139, 0.05); font-style: italic;">
                                <td style="font-family: monospace; font-size: 0.85rem;">{{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} 00:00</td>
                                <td><span class="badge" style="background: #e2e8f0; color: #475569; font-weight: 700;"><i class="fa-solid fa-flag"></i> SALDO INICIAL</span></td>
                                <td colspan="3" style="color: var(--text-muted);">Stock acumulado previo al período</td>
                                <td style="text-align: right;">-</td>
                                <td style="text-align: right;">-</td>
                                <td style="text-align: right;">-</td>
                                <td style="text-align: right;">-</td>
                                <td style="text-align: right;">-</td>
                                <td style="text-align: right;">-</td>
                                <td style="text-align: right; font-weight: 800; color: #334155; font-size: 0.95rem;">
                                    {{ number_format($stockInicialPeriodo, 3) }}
                                </td>
                            </tr>
                        @endif

                        @forelse($kardexLista as $mov)
                            <tr>
                                <td style="font-family: monospace; font-size: 0.85rem;">
                                    {{ \Carbon\Carbon::parse($mov['fecha'])->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    <span class="badge {{ $mov['badge'] }}" style="display: inline-flex; align-items: center; gap: 0.35rem; font-weight: 700;">
                                        <i class="fa-solid {{ $mov['icon'] }}"></i> {{ $mov['tipo_label'] }}
                                    </span>
                                </td>
                                <td style="font-weight: 700; font-family: monospace;">
                                    {{ $mov['documento'] }}
                                </td>
                                <td>
                                    {{ $mov['tercero'] }}
                                </td>
                                <td style="font-size: 0.8rem; color: var(--text-muted); font-family: monospace;">
                                    @if($mov['lote'] || $mov['serie'])
                                        {{ $mov['lote'] ? 'L: '.$mov['lote'] : '' }} {{ $mov['serie'] ? 'S: '.$mov['serie'] : '' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <!-- ENTRADAS -->
                                <td style="text-align: right; font-weight: 700; color: #10b981;">
                                    {{ $mov['entrada_qty'] > 0 ? '+'.number_format($mov['entrada_qty'], 3) : '-' }}
                                </td>
                                <td style="text-align: right; color: var(--text-muted);">
                                    {{ $mov['entrada_qty'] > 0 ? '$'.number_format($mov['entrada_costo'], 2) : '-' }}
                                </td>
                                <td style="text-align: right; font-weight: 600; color: #10b981;">
                                    {{ $mov['entrada_qty'] > 0 ? '$'.number_format($mov['entrada_total'], 2) : '-' }}
                                </td>
                                <!-- SALIDAS -->
                                <td style="text-align: right; font-weight: 700; color: #ef4444;">
                                    {{ $mov['salida_qty'] > 0 ? '-'.number_format($mov['salida_qty'], 3) : '-' }}
                                </td>
                                <td style="text-align: right; color: var(--text-muted);">
                                    {{ $mov['salida_qty'] > 0 ? '$'.number_format($mov['salida_precio'], 2) : '-' }}
                                </td>
                                <td style="text-align: right; font-weight: 600; color: #ef4444;">
                                    {{ $mov['salida_qty'] > 0 ? '$'.number_format($mov['salida_total'], 2) : '-' }}
                                </td>
                                <!-- SALDO RESULTANTE -->
                                <td style="text-align: right; font-weight: 800; color: var(--primary); font-size: 1rem; background: rgba(37,99,235,0.02);">
                                    {{ number_format($mov['saldo_stock'], 3) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                    <i class="fa-solid fa-box-open" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; opacity: 0.4;"></i>
                                    No se encontraron movimientos registrados para este producto en el período seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINACIÓN DINÁMICA KARDEX -->
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                    <span>Mostrar:</span>
                    <select onchange="cambiarPaginacion(this.value)" class="input-modern" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.85rem; font-weight: 700; background: white;">
                        <option value="10" @selected($perPage == 10)>10 por página</option>
                        <option value="15" @selected($perPage == 15)>15 por página</option>
                        <option value="25" @selected($perPage == 25)>25 por página</option>
                        <option value="50" @selected($perPage == 50)>50 por página</option>
                        <option value="100" @selected($perPage == 100)>100 por página</option>
                        <option value="99999" @selected($perPage >= 99999)>Todos los registros</option>
                    </select>
                    <span>(Total: {{ $kardexLista->total() }})</span>
                </div>
                <div>
                    {{ $kardexLista->links() }}
                </div>
            </div>
        </div>
    @endif

    <!-- SECCIÓN: TABLA Y ANÁLISIS DE ROTACIÓN DE INVENTARIO (TODOS LOS PRODUCTOS) -->
    <div class="card" style="padding: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 style="font-size: 1.2rem; font-weight: 800; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-arrows-spin" style="color: #3b82f6;"></i> Rotación y Rendimiento de Productos
                </h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0.25rem 0 0 0;">
                    Compara compras vs ventas netas para evaluar la velocidad de rotación y los días de stock proyectados.
                </p>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">#</th>
                        <th>Producto / SKU</th>
                        <th>Familia</th>
                        <th style="text-align: right;">Stock Actual ({{ $unidadPeso }})</th>
                        <th style="text-align: right; color: #10b981;">Comprado ({{ $unidadPeso }})</th>
                        <th style="text-align: right; color: #ef4444;">Venta Neta ({{ $unidadPeso }})</th>
                        <th style="text-align: center; width: 140px;">% Rotación</th>
                        <th style="text-align: center;">Velocidad / Nivel</th>
                        <th style="text-align: center;">Días de Cobertura</th>
                        <th style="text-align: center;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rotacionProductos as $index => $art)
                        <tr>
                            <td style="text-align: center; font-weight: 800; color: var(--text-muted);">
                                {{ ($rotacionProductos->currentPage() - 1) * $rotacionProductos->perPage() + $index + 1 }}
                            </td>
                            <td>
                                <a href="{{ route('reportes.index', ['tab' => 'kardex', 'articulo_id' => $art->id, 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'per_page' => $perPage]) }}" style="font-weight: 700; color: var(--primary); text-decoration: none;">
                                    {{ $art->descripcion }}
                                </a>
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-family: monospace;">
                                    SKU: {{ $art->codigo }}
                                </div>
                            </td>
                            <td>{{ $art->familia?->nombre ?? 'Sin Familia' }}</td>
                            <td style="text-align: right; font-weight: 700;">
                                {{ number_format($art->stock_actual_num, 3) }}
                            </td>
                            <td style="text-align: right; color: #10b981; font-weight: 600;">
                                {{ $art->total_comprado_periodo > 0 ? '+'.number_format($art->total_comprado_periodo, 3) : '-' }}
                            </td>
                            <td style="text-align: right; color: #ef4444; font-weight: 700; font-size: 0.95rem;">
                                {{ $art->venta_neta_periodo > 0 ? number_format($art->venta_neta_periodo, 3) : '-' }}
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; justify-content: center;">
                                    <div style="flex: 1; background: #e2e8f0; border-radius: 9999px; height: 8px; overflow: hidden; min-width: 60px;">
                                        <div style="width: {{ min(100, $art->rotacion_pct) }}%; background: {{ $art->rotacion_categoria === 'alta' ? '#10b981' : ($art->rotacion_categoria === 'media' ? '#f59e0b' : '#ef4444') }}; height: 100%;"></div>
                                    </div>
                                    <span style="font-size: 0.8rem; font-weight: 800; min-width: 38px;">{{ $art->rotacion_pct }}%</span>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge {{ $art->rotacion_badge }}" style="font-weight: 700;">
                                    {{ $art->rotacion_label }}
                                </span>
                            </td>
                            <td style="text-align: center; font-weight: 700; color: {{ $art->dias_cobertura <= 3 ? '#ef4444' : ($art->dias_cobertura <= 15 ? '#10b981' : '#d97706') }};">
                                @if($art->dias_cobertura === 999)
                                    <span title="Sin ventas recientes en el período">Sin Salidas</span>
                                @elseif($art->dias_cobertura === 0)
                                    <span style="color: #94a3b8;">Agotado</span>
                                @else
                                    ~{{ $art->dias_cobertura }} días
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('reportes.index', ['tab' => 'kardex', 'articulo_id' => $art->id, 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin, 'per_page' => $perPage]) }}" class="btn-modern btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; white-space: nowrap;">
                                    <i class="fa-solid fa-timeline" style="color: var(--primary);"></i> Ver Kardex
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                No hay productos en catálogo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- PAGINACIÓN DINÁMICA ROTACIÓN -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span>Mostrar:</span>
                <select onchange="cambiarPaginacion(this.value)" class="input-modern" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.85rem; font-weight: 700; background: white;">
                    <option value="10" @selected($perPage == 10)>10 por página</option>
                    <option value="15" @selected($perPage == 15)>15 por página</option>
                    <option value="25" @selected($perPage == 25)>25 por página</option>
                    <option value="50" @selected($perPage == 50)>50 por página</option>
                    <option value="100" @selected($perPage == 100)>100 por página</option>
                    <option value="99999" @selected($perPage >= 99999)>Todos los registros</option>
                </select>
                <span>(Total: {{ $rotacionProductos->total() }})</span>
            </div>
            <div>
                {{ $rotacionProductos->links() }}
            </div>
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
            <span class="badge badge-info" style="font-size: 0.85rem;">{{ $articulosFiltrados->total() }} productos listados</span>
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
                            <td style="text-align: right;">${{ number_format($art->precio_compra ?: $art->precio_sin_iva, 2) }}</td>
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

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span>Mostrar:</span>
                <select onchange="cambiarPaginacion(this.value)" class="input-modern" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.85rem; font-weight: 700; background: white;">
                    <option value="10" @selected($perPage == 10)>10 por página</option>
                    <option value="15" @selected($perPage == 15)>15 por página</option>
                    <option value="25" @selected($perPage == 25)>25 por página</option>
                    <option value="50" @selected($perPage == 50)>50 por página</option>
                    <option value="100" @selected($perPage == 100)>100 por página</option>
                    <option value="99999" @selected($perPage >= 99999)>Todos los registros</option>
                </select>
                <span>(Total: {{ $articulosFiltrados->total() }})</span>
            </div>
            <div>
                {{ $articulosFiltrados->links() }}
            </div>
        </div>
    </div>
@endif

<!-- PESTAÑA 5: REPORTE DE CLIENTES Y CARTERA -->
@if($tab === 'clientes')
    <!-- Tarjetas KPI Resumen de Cartera -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1.25rem; border-left: 4px solid #ef4444;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">TOTAL CARTERA POR COBRAR</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #dc2626; margin-top: 0.25rem;">${{ number_format($carteraTotal, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Deuda pendiente total vigente</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid #f59e0b;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">CRÉDITOS OTORGADOS (PERÍODO)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #d97706; margin-top: 0.25rem;">${{ number_format($creditosOtorgadosPeriodo, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Ventas a crédito en el rango de fechas</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid #10b981;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">ABONOS RECAUDADOS (PERÍODO)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">+${{ number_format($abonosRecaudadosPeriodo, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Ingresos por cobranza de cartera</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid #3b82f6;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">CLIENTES CON DEUDA</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #2563eb; margin-top: 0.25rem;">{{ $clientesConDeuda }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Clientes activos con saldo deudor</div>
        </div>
    </div>

    <!-- SECCIÓN 1: TOP 10 CLIENTES DE MAYOR CONSUMO -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">
            <i class="fa-solid fa-trophy" style="color: #f59e0b;"></i> Top 10 Clientes de Mayor Consumo
        </h3>
        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th>Cliente</th>
                        <th>Identificación / RUC</th>
                        <th style="text-align: center;">Nº de Compras</th>
                        <th style="text-align: right;">Total Comprado ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topClientesConsumo as $index => $item)
                        <tr>
                            <td style="text-align: center; font-weight: 800; color: var(--primary);">{{ $index + 1 }}</td>
                            <td style="font-weight: 700;">
                                <a href="{{ route('clientes.show', $item->cliente_id) }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $item->cliente?->nombre ?: 'Cliente General' }}
                                </a>
                            </td>
                            <td>{{ $item->cliente?->identificacion ?: '-' }}</td>
                            <td style="text-align: center;">
                                <span class="badge badge-info">{{ $item->total_compras }} venta(s)</span>
                            </td>
                            <td style="text-align: right; font-weight: 800; color: #10b981; font-size: 1.05rem;">
                                ${{ number_format($item->monto_total, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                No hay registros de ventas para clientes en este rango de fechas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECCIÓN 2: ESTADO GENERAL DE CARTERA (CLIENTES CON DEUDA) -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">
            <i class="fa-solid fa-file-invoice-dollar" style="color: #ef4444;"></i> Estado General de Cartera y Cuentas por Cobrar
        </h3>
        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Teléfono / Contacto</th>
                        <th style="text-align: right;">Límite Crédito</th>
                        <th style="text-align: right;">Saldo Deudor</th>
                        <th style="text-align: center;">% Uso Crédito</th>
                        <th style="text-align: center;">Estado Riesgo</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientesDeudores as $cli)
                        @php
                            $limite = (float) $cli->limite_credito;
                            $deuda = (float) $cli->saldo_deudor;
                            $pct = $limite > 0 ? min(100, round(($deuda / $limite) * 100)) : 0;
                            
                            $badgeColor = '#10b981';
                            $badgeBg = 'rgba(16, 185, 129, 0.15)';
                            $labelRiesgo = 'Normal';
                            if ($pct >= 100) {
                                $badgeColor = '#dc2626';
                                $badgeBg = 'rgba(220, 38, 38, 0.15)';
                                $labelRiesgo = 'Límite Excedido';
                            } elseif ($pct >= 85) {
                                $badgeColor = '#d97706';
                                $badgeBg = 'rgba(245, 158, 11, 0.15)';
                                $labelRiesgo = 'Riesgo Alto';
                            }
                        @endphp
                        <tr>
                            <td style="font-weight: 700;">
                                <a href="{{ route('clientes.show', $cli->id) }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $cli->nombre }}
                                </a>
                            </td>
                            <td>{{ $cli->telefono ?: '-' }}</td>
                            <td style="text-align: right; color: var(--text-muted);">${{ number_format($limite, 2) }}</td>
                            <td style="text-align: right; font-weight: 800; color: #dc2626; font-size: 1.05rem;">
                                ${{ number_format($deuda, 2) }}
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-block; width: 100px; background: #e2e8f0; border-radius: 9999px; height: 10px; overflow: hidden; vertical-align: middle;">
                                    <div style="width: {{ $pct }}%; background: {{ $badgeColor }}; height: 100%;"></div>
                                </div>
                                <span style="font-size: 0.78rem; font-weight: 700; margin-left: 0.35rem;">{{ $pct }}%</span>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge" style="background: {{ $badgeBg }}; color: {{ $badgeColor }}; font-weight: 700;">
                                    {{ $labelRiesgo }}
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <a href="{{ route('clientes.show', $cli->id) }}" class="btn-modern btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.8rem; text-decoration: none; display: inline-block;">
                                    <i class="fa-solid fa-address-card"></i> Ver Estado
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                🟢 No hay clientes con saldo deudor pendiente en este momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span>Mostrar:</span>
                <select onchange="cambiarPaginacion(this.value)" class="input-modern" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.85rem; font-weight: 700; background: white;">
                    <option value="10" @selected($perPage == 10)>10 por página</option>
                    <option value="15" @selected($perPage == 15)>15 por página</option>
                    <option value="25" @selected($perPage == 25)>25 por página</option>
                    <option value="50" @selected($perPage == 50)>50 por página</option>
                    <option value="100" @selected($perPage == 100)>100 por página</option>
                    <option value="99999" @selected($perPage >= 99999)>Todos los registros</option>
                </select>
                <span>(Total: {{ $clientesDeudores->total() }})</span>
            </div>
            <div>
                {{ $clientesDeudores->appends(['tab' => 'clientes', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin])->links() }}
            </div>
        </div>
    </div>

    <!-- SECCIÓN 3: HISTORIAL DE ABONOS RECAUDADOS EN EL PERÍODO -->
    <div class="card" style="padding: 1.25rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">
            <i class="fa-solid fa-receipt" style="color: #10b981;"></i> Historial de Abonos Recaudados en el Período
        </h3>
        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>N° Abono</th>
                        <th>Fecha / Hora</th>
                        <th>Cliente</th>
                        <th>Método Pago</th>
                        <th style="text-align: right;">Saldo Anterior</th>
                        <th style="text-align: right;">Monto Abonado</th>
                        <th style="text-align: right;">Nuevo Saldo</th>
                        <th>Cajero</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($abonosLista as $ab)
                        <tr>
                            <td style="font-weight: 700; font-family: monospace; color: #10b981;">
                                #AB-{{ str_pad($ab->id, 6, '0', STR_PAD_LEFT) }}
                            </td>
                            <td>{{ $ab->created_at->format('d/m/Y h:i A') }}</td>
                            <td style="font-weight: 700;">
                                <a href="{{ route('clientes.show', $ab->cliente_id) }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $ab->cliente?->nombre ?: 'Cliente' }}
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-info" style="text-transform: uppercase;">{{ $ab->metodo_pago }}</span>
                            </td>
                            <td style="text-align: right; color: var(--text-muted);">${{ number_format($ab->saldo_anterior, 2) }}</td>
                            <td style="text-align: right; font-weight: 800; color: #10b981; font-size: 1.05rem;">
                                +${{ number_format($ab->monto, 2) }}
                            </td>
                            <td style="text-align: right; font-weight: 700; color: {{ $ab->saldo_nuevo > 0 ? '#dc2626' : '#10b981' }};">
                                ${{ number_format($ab->saldo_nuevo, 2) }}
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                {{ $ab->user?->name ?: 'Sistema' }}
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-modern btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.78rem;" onclick="verTicketAbono({{ $ab->id }})">
                                    <i class="fa-solid fa-receipt" style="color: #10b981;"></i> Ticket Abono
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                No se registraron abonos en el período seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span>Mostrar:</span>
                <select onchange="cambiarPaginacion(this.value)" class="input-modern" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.85rem; font-weight: 700; background: white;">
                    <option value="10" @selected($perPage == 10)>10 por página</option>
                    <option value="15" @selected($perPage == 15)>15 por página</option>
                    <option value="25" @selected($perPage == 25)>25 por página</option>
                    <option value="50" @selected($perPage == 50)>50 por página</option>
                    <option value="100" @selected($perPage == 100)>100 por página</option>
                    <option value="99999" @selected($perPage >= 99999)>Todos los registros</option>
                </select>
                <span>(Total: {{ $abonosLista->total() }})</span>
            </div>
            <div>
                {{ $abonosLista->appends(['tab' => 'clientes', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin])->links() }}
            </div>
        </div>
    </div>
@endif

<!-- PESTAÑA 6: REPORTE DE PROVEEDORES Y COMPRAS -->
@if($tab === 'proveedores')
    <!-- Tarjetas KPI Resumen de Proveedores -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--accent);">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">TOTAL INVERSIÓN (PERÍODO)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent); margin-top: 0.25rem;">${{ number_format($totalInversionProveedoresPeriodo, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Compras recibidas en el rango de fechas</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--primary);">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Nº RECEPCIONES / FACTURAS</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">{{ $numRecepcionesPeriodo }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Documentos ingresados</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid #10b981;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">PROMEDIO POR FACTURA</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">${{ number_format($promedioInversionFactura, 2) }}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Costo promedio de compra</div>
        </div>

        <div class="card" style="padding: 1.25rem; border-left: 4px solid #6366f1;">
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">PROVEEDOR PRINCIPAL</div>
            <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-top: 0.35rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $topProveedorPeriodo?->proveedor?->nombre ?: 'Sin registros' }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                ${{ number_format($topProveedorPeriodo?->total_invertido ?: 0, 2) }} invertidos
            </div>
        </div>
    </div>

    <!-- SECCIÓN 1: TOP 10 PROVEEDORES DE MAYOR INVERSIÓN -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">
            <i class="fa-solid fa-award" style="color: #f59e0b;"></i> Top 10 Proveedores de Mayor Inversión
        </h3>
        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th>Proveedor / Razón Social</th>
                        <th>RUC / Identificación</th>
                        <th style="text-align: center;">Nº Facturas Recibidas</th>
                        <th style="text-align: right;">Total Invertido ($)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topProveedoresInversion as $index => $item)
                        <tr>
                            <td style="text-align: center; font-weight: 800; color: var(--primary);">{{ $index + 1 }}</td>
                            <td style="font-weight: 700;">
                                <a href="{{ route('proveedores.show', $item->proveedor_id) }}" style="color: var(--primary); text-decoration: none;">
                                    <i class="fa-solid fa-truck" style="color: var(--text-muted); margin-right: 4px;"></i> {{ $item->proveedor?->nombre ?: 'Proveedor General' }}
                                </a>
                            </td>
                            <td style="font-family: monospace;">{{ $item->proveedor?->identificacion ?: '-' }}</td>
                            <td style="text-align: center;">
                                <span class="badge badge-info">{{ $item->total_facturas }} factura(s)</span>
                            </td>
                            <td style="text-align: right; font-weight: 800; color: var(--accent); font-size: 1.05rem;">
                                ${{ number_format($item->monto_total, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                No hay compras registradas a proveedores en este rango de fechas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECCIÓN 2: DESGLOSE DE COMPRAS POR PROVEEDOR EN EL PERÍODO -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">
            <i class="fa-solid fa-truck-field" style="color: var(--primary);"></i> Desglose de Compras por Proveedor en el Período
        </h3>
        <div style="overflow-x: hidden;">
            <table class="table-modern" style="width: 100%; table-layout: auto;">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th style="text-align: center;">Recepciones</th>
                        <th style="text-align: right;">Total Invertido</th>
                        <th style="text-align: center; white-space: nowrap;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($comprasPorProveedor as $prov)
                        @php
                            $montoProv = (float) ($prov->compras_sum_total ?? 0);
                        @endphp
                        <tr>
                            <td style="font-weight: 700;">
                                <a href="{{ route('proveedores.show', $prov->id) }}" style="color: var(--primary); text-decoration: none;">
                                    {{ $prov->nombre }}
                                </a>
                            </td>
                            <td>{{ $prov->contacto_nombre ?: '-' }}</td>
                            <td>{{ $prov->telefono ?: '-' }}</td>
                            <td style="text-align: center;">
                                <span class="badge badge-info">{{ $prov->compras_count }} factura(s)</span>
                            </td>
                            <td style="text-align: right; font-weight: 800; color: var(--accent); font-size: 1.05rem; white-space: nowrap;">
                                ${{ number_format($montoProv, 2) }}
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="{{ route('proveedores.show', $prov->id) }}" class="btn-modern btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.8rem; text-decoration: none; display: inline-block; white-space: nowrap;">
                                    <i class="fa-solid fa-eye"></i> Ver Expediente
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                No se encontraron proveedores con compras en este rango de fechas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span>Mostrar:</span>
                <select onchange="cambiarPaginacion(this.value)" class="input-modern" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.85rem; font-weight: 700; background: white;">
                    <option value="10" @selected($perPage == 10)>10 por página</option>
                    <option value="15" @selected($perPage == 15)>15 por página</option>
                    <option value="25" @selected($perPage == 25)>25 por página</option>
                    <option value="50" @selected($perPage == 50)>50 por página</option>
                    <option value="100" @selected($perPage == 100)>100 por página</option>
                    <option value="99999" @selected($perPage >= 99999)>Todos los registros</option>
                </select>
                <span>(Total: {{ $comprasPorProveedor->total() }})</span>
            </div>
            <div>
                {{ $comprasPorProveedor->appends(['tab' => 'proveedores', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin])->links() }}
            </div>
        </div>
    </div>

    <!-- SECCIÓN 3: HISTORIAL GENERAL DE RECEPCIONES / COMPRAS EN EL PERÍODO -->
    <div class="card" style="padding: 1.25rem;">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem;">
            <i class="fa-solid fa-boxes-packing" style="color: var(--accent);"></i> Historial General de Recepciones en el Período
        </h3>
        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>N° Factura / Comprobante</th>
                        <th>Fecha de Recepción</th>
                        <th>Proveedor</th>
                        <th style="text-align: center;">Ítems/Cajas</th>
                        <th style="text-align: right;">Subtotal</th>
                        <th style="text-align: right;">Total Invertido</th>
                        <th>Registrado Por</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historialRecepciones as $rec)
                        <tr>
                            <td style="font-weight: 700; font-family: monospace; color: var(--accent);">
                                {{ $rec->numero_factura ?: 'SIN FACTURA (#'.$rec->id.')' }}
                            </td>
                            <td>{{ $rec->fecha_compra ? $rec->fecha_compra->format('d/m/Y h:i A') : 'N/A' }}</td>
                            <td style="font-weight: 600;">{{ $rec->proveedor_nombre ?: 'Proveedor General' }}</td>
                            <td style="text-align: center;">
                                <span class="badge badge-info">{{ $rec->detalles->count() }} artículo(s)</span>
                            </td>
                            <td style="text-align: right;">${{ number_format($rec->subtotal, 2) }}</td>
                            <td style="text-align: right; font-weight: 800; color: var(--accent); font-size: 1.05rem;">
                                ${{ number_format($rec->total, 2) }}
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                {{ $rec->user?->name ?: 'Sistema' }}
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn-modern btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.82rem;" onclick="abrirDocumentoCompra({{ json_encode($rec) }})">
                                    <i class="fa-solid fa-boxes-packing" style="color: var(--accent);"></i> Ver Documento
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                No se encontraron registros de recepciones de mercancía en este rango de fechas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                <span>Mostrar:</span>
                <select onchange="cambiarPaginacion(this.value)" class="input-modern" style="width: auto; padding: 0.25rem 0.5rem; font-size: 0.85rem; font-weight: 700; background: white;">
                    <option value="10" @selected($perPage == 10)>10 por página</option>
                    <option value="15" @selected($perPage == 15)>15 por página</option>
                    <option value="25" @selected($perPage == 25)>25 por página</option>
                    <option value="50" @selected($perPage == 50)>50 por página</option>
                    <option value="100" @selected($perPage == 100)>100 por página</option>
                    <option value="99999" @selected($perPage >= 99999)>Todos los registros</option>
                </select>
                <span>(Total: {{ $historialRecepciones->total() }})</span>
            </div>
            <div>
                {{ $historialRecepciones->appends(['tab' => 'proveedores', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin])->links() }}
            </div>
        </div>
    </div>
@endif

<!-- MODAL DOCUMENTO DE VENTA (TICKET TÉRMICO IDÉNTICO AL TPV) -->
<div id="modal-documento-venta" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; max-width: 480px; width: 95%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color); overflow: hidden;">
        <!-- Header -->
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-receipt" style="color: var(--primary);"></i> <span id="doc-venta-titulo">Ticket de Venta</span>
            </div>
            <button type="button" onclick="cerrarDocumentoVenta()" style="background: none; border: none; font-size: 1.3rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <!-- Área Térmica Imprimible -->
        <div style="padding: 1.25rem; overflow-y: auto; flex: 1; background: #f1f5f9; display: flex; justify-content: center;">
            <div id="printableTicketAreaReportes" style="background: white; width: 80mm; max-width: 100%; padding: 1.25rem; font-family: 'Courier New', Courier, monospace; font-size: 12px; color: black; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-radius: 4px;">
                <!-- Renderizado dinámico vía JS -->
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 0.85rem 1.25rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.5rem 1rem;" onclick="cerrarDocumentoVenta()">Cerrar</button>
            <button type="button" class="btn-modern btn-primary" style="width: auto; padding: 0.5rem 1.25rem;" onclick="imprimirTicketReportes()"><i class="fa-solid fa-print"></i> Imprimir Comprobante</button>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #printableTicketAreaReportes, #printableTicketAreaReportes * {
        visibility: visible !important;
    }
    #printableTicketAreaReportes {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 80mm !important;
        margin: 0 !important;
        padding: 5mm !important;
        background: white !important;
        color: black !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 12px !important;
        z-index: 99999 !important;
    }
}
</style>

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
@push('modals')
<!-- MODAL TICKET Z PREVIEW -->
<div id="modal-ticket-z" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; max-width: 520px; width: 95%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color); overflow: hidden;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="font-weight: 800; font-size: 1.1rem; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-receipt" style="color: #10b981;"></i> Vista Previa Ticket Z
            </div>
            <button type="button" id="btn-cerrar-ticket-z" style="background: none; border: none; font-size: 1.4rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>
        <div style="flex: 1; min-height: 450px; background: #f1f5f9; padding: 0.5rem;">
            <iframe id="iframe-ticket-z" src="" style="width: 100%; height: 450px; border: none; border-radius: 8px; background: white;"></iframe>
        </div>
        <div style="padding: 0.85rem 1.25rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" id="btn-imprimir-ticket-z" class="btn-modern btn-primary"><i class="fa-solid fa-print"></i> Imprimir Ticket</button>
            <button type="button" id="btn-cerrar-ticket-z-2" class="btn-modern btn-secondary">Cerrar</button>
        </div>
    </div>
</div>
<script>
(function() {
    var modal = document.getElementById('modal-ticket-z');
    var iframe = document.getElementById('iframe-ticket-z');

    document.getElementById('btn-cerrar-ticket-z').addEventListener('click', function() {
        modal.style.display = 'none';
        iframe.src = '';
    });
    document.getElementById('btn-cerrar-ticket-z-2').addEventListener('click', function() {
        modal.style.display = 'none';
        iframe.src = '';
    });
    document.getElementById('btn-imprimir-ticket-z').addEventListener('click', function() {
        iframe.contentWindow.print();
    });

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-btn-ticket-z');
        if (btn) {
            e.preventDefault();
            var url = btn.getAttribute('data-url');
            if (url) {
                iframe.src = url;
                modal.style.display = 'flex';
            }
        }
    });
})();
</script>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const unidadPesoGlobal = "{{ $unidadPeso }}";

    function abrirDocumentoVenta(venta) {
        const modal = document.getElementById('modal-documento-venta');
        const ticketNum = String(venta.id).padStart(6, '0');
        document.getElementById('doc-venta-titulo').innerText = `Ticket #${ticketNum}`;

        const empresaNombre = "{{ $settings['empresa_nombre'] ?? 'IntelliCarnic' }}";
        const empresaRuc = "{{ $settings['empresa_ruc'] ?? '000000000' }}";
        const empresaDireccion = "{{ $settings['empresa_direccion'] ?? 'Dirección de la empresa' }}";
        const fecha = venta.created_at ? new Date(venta.created_at).toLocaleString() : new Date().toLocaleString();

        let itemsHtml = '';
        let totalDescuentoCalculado = 0;

        if (venta.detalles && venta.detalles.length > 0) {
            venta.detalles.forEach(d => {
                const desc = d.articulo ? d.articulo.descripcion : 'Producto';
                const cant = parseFloat(d.cantidad || 0);
                const sub = parseFloat(d.subtotal || 0);
                const itemDesc = parseFloat(d.descuento || 0);
                totalDescuentoCalculado += itemDesc;

                let descText = '';
                if (itemDesc > 0) {
                    descText = `<div style="font-size: 11px; color: #475569; font-style: italic;">↳ Desc: -$${itemDesc.toFixed(2)}</div>`;
                }

                itemsHtml += `
                    <tr>
                        <td style="text-align: left; padding: 2px 0; vertical-align: top;">${cant}</td>
                        <td style="text-align: left; padding: 2px 0;">
                            <div>${desc}</div>
                            ${descText}
                        </td>
                        <td style="text-align: right; padding: 2px 0; vertical-align: top;">$${sub.toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        const descFinal = parseFloat(venta.descuento || 0) > 0 ? parseFloat(venta.descuento) : totalDescuentoCalculado;
        const subtotalBruto = parseFloat(venta.subtotal || 0) + descFinal;
        const montoRecibido = parseFloat(venta.monto_recibido || venta.total || 0);
        const vuelto = parseFloat(venta.vuelto || 0);

        let estadoBanner = '';
        if (venta.estado === 'devuelta') {
            estadoBanner = `
                <div style="background: #fef2f2; border: 1.5px dashed #fca5a5; color: #dc2626; font-weight: 800; padding: 5px; margin-top: 6px; font-size: 11px; text-align: center;">
                    *** VENTA TOTALMENTE DEVUELTA ***
                </div>
            `;
        } else if (venta.estado === 'parcialmente_devuelta') {
            estadoBanner = `
                <div style="background: #fffbeb; border: 1.5px dashed #fcd34d; color: #d97706; font-weight: 800; padding: 5px; margin-top: 6px; font-size: 11px; text-align: center;">
                    *** VENTA PARCIALMENTE DEVUELTA ***
                </div>
            `;
        } else if (venta.tipo_venta === 'credito') {
            const crInfo = venta.credito_info || {};
            const stCred = crInfo.estado_credito || 'pendiente';
            const abonado = parseFloat(crInfo.monto_abonado || 0);
            const pendiente = parseFloat(crInfo.saldo_pendiente || venta.total || 0);

            if (stCred === 'saldado' || pendiente <= 0) {
                estadoBanner = `
                    <div style="background: #f0fdf4; border: 1.5px dashed #16a34a; color: #15803d; font-weight: 800; padding: 6px; margin-top: 6px; font-size: 11px; text-align: center;">
                        *** CRÉDITO CANCELADO / SALDADO ***<br>
                        ¡ESTA VENTA FUE TOTALMENTE PAGADA!
                    </div>
                `;
            } else if (stCred === 'parcial') {
                estadoBanner = `
                    <div style="background: #fffbeb; border: 1.5px dashed #fcd34d; color: #d97706; font-weight: 800; padding: 6px; margin-top: 6px; font-size: 11px; text-align: center;">
                        *** CRÉDITO CON ABONO PARCIAL ***<br>
                        ABONADO: $${abonado.toFixed(2)} | PENDIENTE: $${pendiente.toFixed(2)}
                    </div>
                `;
            } else {
                estadoBanner = `
                    <div style="background: #fff7ed; border: 1.5px dashed #fb923c; color: #c2410c; font-weight: 800; padding: 6px; margin-top: 6px; font-size: 11px; text-align: center;">
                        *** CRÉDITO PENDIENTE DE PAGO ***<br>
                        SALDO PENDIENTE: $${parseFloat(venta.total || 0).toFixed(2)}
                    </div>
                `;
            }
        }

        let pagoSectionHtml = '';
        if (venta.tipo_venta === 'credito') {
            const crInfo = venta.credito_info || {};
            const abonado = parseFloat(crInfo.monto_abonado || 0);
            const pendiente = parseFloat(crInfo.saldo_pendiente || venta.total || 0);
            const clienteNom = venta.cliente ? venta.cliente.nombre : 'Cliente';

            pagoSectionHtml = `
                <div style="display: flex; justify-content: space-between;">
                    <span>MÉTODO DE PAGO:</span>
                    <span style="font-weight: bold; color: #d97706;">CRÉDITO</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>TITULAR CRÉDITO:</span>
                    <span style="font-weight: bold;">${clienteNom}</span>
                </div>
                <div style="display: flex; justify-content: space-between; color: #16a34a; font-weight: bold;">
                    <span>(-) ABONADO DE ESTA VENTA:</span>
                    <span>-$${abonado.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 13px; margin-top: 4px;">
                    <span>SALDO PENDIENTE TICKET:</span>
                    <span style="color: ${pendiente > 0 ? '#dc2626' : '#16a34a'};">$${pendiente.toFixed(2)}</span>
                </div>
            `;
        } else {
            pagoSectionHtml = `
                <div style="display: flex; justify-content: space-between;">
                    <span>MÉTODO DE PAGO:</span>
                    <span style="text-transform: uppercase;">${(venta.metodo_pago || 'EFECTIVO').toUpperCase()}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>ENTREGADO:</span>
                    <span>$${montoRecibido.toFixed(2)}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>CAMBIO/VUELTO:</span>
                    <span>$${vuelto.toFixed(2)}</span>
                </div>
            `;
        }

        let html = `
            <div style="text-align: center; margin-bottom: 15px;">
                <h2 style="margin: 0; font-size: 18px; font-weight: 800;">${empresaNombre}</h2>
                <div>RUC/NIT: ${empresaRuc}</div>
                <div>${empresaDireccion}</div>
                <div style="margin-top: 5px; font-weight: bold;">Ticket #${ticketNum}</div>
                <div>Fecha: ${fecha}</div>
                ${estadoBanner}
            </div>
            <hr style="border-top: 1px dashed black; margin: 10px 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid black;">
                        <th style="text-align: left; padding: 2px 0;">CANT</th>
                        <th style="text-align: left; padding: 2px 0;">DESCRIPCIÓN</th>
                        <th style="text-align: right; padding: 2px 0;">IMPORTE</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
            </table>
            <hr style="border-top: 1px dashed black; margin: 10px 0;">
            <div style="display: flex; justify-content: space-between;">
                <span>SUBTOTAL:</span>
                <span>$${subtotalBruto.toFixed(2)}</span>
            </div>
            ${descFinal > 0 ? `
            <div style="display: flex; justify-content: space-between; font-weight: bold; color: #000;">
                <span>DESCUENTO:</span>
                <span>-$${descFinal.toFixed(2)}</span>
            </div>` : ''}
            <div style="display: flex; justify-content: space-between;">
                <span>IMPUESTOS:</span>
                <span>$${parseFloat(venta.impuestos || 0).toFixed(2)}</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-top: 5px;">
                <span>TOTAL VENTA:</span>
                <span>$${parseFloat(venta.total || 0).toFixed(2)}</span>
            </div>
            <hr style="border-top: 1px dashed black; margin: 10px 0;">
            ${pagoSectionHtml}
            <div style="text-align: center; margin-top: 18px; margin-bottom: 5px;">
                <svg id="ticketBarcodeReporte" style="max-width: 100%;"></svg>
            </div>
            <div style="text-align: center; margin-top: 8px; font-size: 11px;">
                ¡Gracias por su compra!
            </div>
        `;

        document.getElementById('printableTicketAreaReportes').innerHTML = html;
        modal.style.display = 'flex';

        setTimeout(() => {
            try {
                if (typeof JsBarcode === 'function') {
                    JsBarcode("#ticketBarcodeReporte", ticketNum, {
                        format: "CODE128",
                        width: 1.6,
                        height: 40,
                        displayValue: true,
                        fontSize: 12,
                        margin: 4
                    });
                }
            } catch (e) {
                console.error("Error Barcode Reporte:", e);
            }
        }, 50);
    }

    function imprimirTicketReportes() {
        window.print();
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

    async function verTicketAbono(abonoId) {
        try {
            const res = await fetch(`/clientes/abono/${abonoId}/ticket`);
            const data = await res.json();
            if (data.success && data.abono) {
                const ab = data.abono;
                const set = data.settings;
                const empresaNom = set.empresa_nombre || 'IntelliCarnic';
                const empresaRuc = set.empresa_ruc || '000000000';
                const empresaDir = set.empresa_direccion || 'Dirección de la empresa';
                const abonoNum = 'AB-' + String(ab.id).padStart(6, '0');
                const fecha = new Date(ab.created_at).toLocaleString();
                const clienteNom = ab.cliente ? ab.cliente.nombre : 'CLIENTE';
                const clienteIden = ab.cliente ? (ab.cliente.identificacion || '-') : '-';
                const cajero = ab.user ? ab.user.name : 'Sistema';
                const saldoAnt = parseFloat(ab.saldo_anterior || 0);
                const montoAbono = parseFloat(ab.monto || 0);
                const saldoNuev = parseFloat(ab.saldo_nuevo || 0);

                let statusBanner = saldoNuev <= 0 ? `
                    <div style="background: #f0fdf4; border: 1.5px dashed #16a34a; color: #15803d; font-weight: 800; padding: 6px; margin: 10px 0; font-size: 12px; text-align: center;">
                        *** DEUDA TOTALMENTE LIQUIDADA ***<br>
                        ¡SU CUENTA HA QUEDADO EN $0.00!
                    </div>
                ` : `
                    <div style="background: #fffbeb; border: 1.5px dashed #fcd34d; color: #d97706; font-weight: 800; padding: 6px; margin: 10px 0; font-size: 11px; text-align: center;">
                        ABONO PARCIAL A CUENTA A CRÉDITO
                    </div>
                `;

                let html = `
                    <div style="text-align: center; margin-bottom: 12px;">
                        <h2 style="margin: 0; font-size: 18px; font-weight: 800;">${empresaNom}</h2>
                        <div>RUC/NIT: ${empresaRuc}</div>
                        <div>${empresaDir}</div>
                        <div style="margin-top: 6px; font-weight: bold; font-size: 13px;">COMPROBANTE DE ABONO</div>
                        <div style="font-weight: bold;">#${abonoNum}</div>
                        <div>Fecha: ${fecha}</div>
                        <div>Cajero: ${cajero}</div>
                    </div>
                    ${statusBanner}
                    <div style="margin-bottom: 8px; font-size: 11px;">
                        <div><strong>CLIENTE:</strong> ${clienteNom}</div>
                        <div><strong>IDENTIFICACIÓN:</strong> ${clienteIden}</div>
                    </div>
                    <hr style="border-top: 1px dashed black; margin: 8px 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                        <span>SALDO ANTERIOR:</span>
                        <span>$${saldoAnt.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 3px; font-weight: bold; color: #000;">
                        <span>MONTO ABONADO:</span>
                        <span>+$${montoAbono.toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                        <span>MÉTODO DE PAGO:</span>
                        <span style="text-transform: uppercase;">${ab.metodo_pago}</span>
                    </div>
                    <hr style="border-top: 1px dashed black; margin: 8px 0;">
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 13px; margin-top: 4px;">
                        <span>NUEVO SALDO PENDIENTE:</span>
                        <span style="color: ${saldoNuev > 0 ? '#dc2626' : '#16a34a'};">$${saldoNuev.toFixed(2)}</span>
                    </div>
                    ${ab.notas ? `<div style="margin-top: 8px; font-size: 11px; font-style: italic;">Notas: ${ab.notas}</div>` : ''}
                    <div style="text-align: center; margin-top: 15px; margin-bottom: 5px;">
                        <svg id="abonoBarcodeSvg" style="max-width: 100%;"></svg>
                    </div>
                    <div style="text-align: center; margin-top: 8px; font-size: 11px;">
                        ¡Gracias por su pago!
                    </div>
                `;

                document.getElementById('printableAbonoArea').innerHTML = html;
                document.getElementById('modalComprobanteAbono').style.display = 'flex';
                setTimeout(() => {
                    try {
                        if (typeof JsBarcode === 'function') {
                            JsBarcode("#abonoBarcodeSvg", abonoNum, { format: "CODE128", width: 1.6, height: 40, displayValue: true, fontSize: 12, margin: 4 });
                        }
                    } catch (e) {}
                }, 50);
            }
        } catch (e) {}
    }

    function closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    }

    function imprimirTicketAbono() {
        window.print();
    }

    function cambiarPaginacion(val) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', val);
        url.searchParams.delete('page_ventas');
        url.searchParams.delete('page_compras');
        url.searchParams.delete('page_caja');
        url.searchParams.delete('page_deudores');
        url.searchParams.delete('page_abonos');
        url.searchParams.delete('page_prov');
        url.searchParams.delete('page_recepciones');
        url.searchParams.delete('page_inv');
        url.searchParams.delete('page_kardex');
        url.searchParams.delete('page_rot');
        window.location.href = url.toString();
    }

    function cambiarTopLimit(val) {
        const url = new URL(window.location.href);
        url.searchParams.set('top_limit', val);
        window.location.href = url.toString();
    }

    // CATÁLOGO DE PRODUCTOS PARA EL BUSCADOR DE KARDEX
    const articulosKardexCatalog = {!! json_encode($articulosKardexJson ?? []) !!};

    const kardexSearchInput = document.getElementById('kardex-search-input');
    const kardexResultsContainer = document.getElementById('kardex-search-results');
    const hiddenKardexArticuloId = document.getElementById('hidden-kardex-articulo-id');
    const btnClearKardex = document.getElementById('btn-clear-kardex-search');

    function renderKardexSearchResults(query = '') {
        if (!kardexResultsContainer) return;
        const q = String(query).trim().toLowerCase();

        let filtered = articulosKardexCatalog;
        if (q !== '') {
            filtered = articulosKardexCatalog.filter(a => {
                const desc = String(a.descripcion || '').toLowerCase();
                const sku = String(a.codigo || '').toLowerCase();
                const prov = String(a.codigo_cliente || '').toLowerCase();
                const item = String(a.item || '').toLowerCase();
                const fam = String(a.familia || '').toLowerCase();
                return desc.includes(q) || sku.includes(q) || prov.includes(q) || item.includes(q) || fam.includes(q);
            });
        }

        let html = '';

        // Opción predeterminada: Todos los productos
        const isAllSelected = !hiddenKardexArticuloId || !hiddenKardexArticuloId.value;
        html += `
            <div class="kardex-item-opt" onclick="seleccionarArticuloKardex('', '')" 
                 style="padding: 0.65rem 1rem; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; align-items: center; justify-content: space-between; background: ${isAllSelected ? 'rgba(37,99,235,0.06)' : 'white'};"
                 onmouseover="this.style.background='rgba(37,99,235,0.08)'" onmouseout="this.style.background='${isAllSelected ? 'rgba(37,99,235,0.06)' : 'white'}'">
                <div style="font-weight: 700; color: var(--primary); font-size: 0.9rem;">
                    <i class="fa-solid fa-arrows-spin"></i> -- Todos los Productos (Análisis de Rotación General) --
                </div>
                <span class="badge badge-info" style="font-size: 0.72rem;">Catálogo General</span>
            </div>
        `;

        if (filtered.length === 0) {
            html += `
                <div style="padding: 1.25rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                    <i class="fa-solid fa-magnifying-glass" style="margin-bottom: 0.35rem; display: block; opacity: 0.4;"></i>
                    No se encontraron productos con "<strong>${q}</strong>"
                </div>
            `;
        } else {
            filtered.forEach(art => {
                const isSelected = hiddenKardexArticuloId && String(hiddenKardexArticuloId.value) === String(art.id);
                const provBadge = art.codigo_cliente ? `<span style="background: rgba(249,115,22,0.12); color: #ea580c; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-family: monospace; font-weight: 700;"><i class="fa-solid fa-truck" style="font-size: 0.7rem;"></i> Prov: ${art.codigo_cliente}</span>` : '';
                const skuBadge = `<span style="background: #e2e8f0; color: #475569; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; font-family: monospace; font-weight: 700;"><i class="fa-solid fa-barcode" style="font-size: 0.7rem;"></i> SKU: ${art.codigo}</span>`;

                const labelEscaped = `${art.descripcion} (SKU: ${art.codigo}${art.codigo_cliente ? ' | Prov: ' + art.codigo_cliente : ''})`.replace(/'/g, "\\'");

                html += `
                    <div class="kardex-item-opt" onclick="seleccionarArticuloKardex(${art.id}, '${labelEscaped}')"
                         style="padding: 0.65rem 1rem; border-bottom: 1px solid #f1f5f9; cursor: pointer; display: flex; align-items: center; justify-content: space-between; background: ${isSelected ? 'rgba(37,99,235,0.06)' : 'white'};"
                         onmouseover="this.style.background='rgba(37,99,235,0.08)'" onmouseout="this.style.background='${isSelected ? 'rgba(37,99,235,0.06)' : 'white'}'">
                        <div style="flex: 1; min-width: 0; padding-right: 0.75rem;">
                            <div style="font-weight: 700; color: var(--text-main); font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                ${art.descripcion}
                            </div>
                            <div style="display: flex; gap: 0.4rem; align-items: center; margin-top: 0.25rem; flex-wrap: wrap;">
                                ${skuBadge}
                                ${provBadge}
                                ${art.familia ? `<span style="font-size: 0.75rem; color: var(--text-muted);"><i class="fa-solid fa-tag" style="font-size: 0.7rem;"></i> ${art.familia}</span>` : ''}
                            </div>
                        </div>
                        <div style="text-align: right; white-space: nowrap;">
                            <span style="font-weight: 800; font-size: 0.95rem; color: ${art.stock <= 0 ? '#ef4444' : '#10b981'};">
                                ${Number(art.stock).toFixed(3)} ${art.unidad}
                            </span>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Stock actual</div>
                        </div>
                    </div>
                `;
            });
        }

        kardexResultsContainer.innerHTML = html;
        kardexResultsContainer.style.display = 'block';
    }

    function seleccionarArticuloKardex(id, label) {
        if (hiddenKardexArticuloId) hiddenKardexArticuloId.value = id || '';
        if (kardexSearchInput) kardexSearchInput.value = label || '';
        if (btnClearKardex) btnClearKardex.style.display = id ? 'block' : 'none';
        if (kardexResultsContainer) kardexResultsContainer.style.display = 'none';

        const form = document.getElementById('form-filtros-reporte');
        if (form) form.submit();
    }

    function limpiarBusquedaKardex() {
        if (hiddenKardexArticuloId) hiddenKardexArticuloId.value = '';
        if (kardexSearchInput) kardexSearchInput.value = '';
        if (btnClearKardex) btnClearKardex.style.display = 'none';
        if (kardexResultsContainer) kardexResultsContainer.style.display = 'none';

        const form = document.getElementById('form-filtros-reporte');
        if (form) form.submit();
    }

    if (kardexSearchInput) {
        kardexSearchInput.addEventListener('input', function() {
            renderKardexSearchResults(this.value);
            if (btnClearKardex) {
                btnClearKardex.style.display = this.value.trim() ? 'block' : 'none';
            }
        });

        kardexSearchInput.addEventListener('focus', function() {
            renderKardexSearchResults(this.value);
        });

        document.addEventListener('click', function(e) {
            const container = document.getElementById('container-kardex-search');
            if (container && !container.contains(e.target) && kardexResultsContainer) {
                kardexResultsContainer.style.display = 'none';
            }
        });
    }

    // -------------------------------------------------------------
    // CONTROLADOR DE GRÁFICOS COMPARATIVOS INTELIGENTES (CHART.JS)
    // -------------------------------------------------------------
    const chartApiUrl = "{{ route('reportes.api.grafico') }}";

    const chartState = {
        ventas: {
            nivel: 'mensual', // Por defecto: Mensual (Día 1-31)
            mesBase: '{{ \Carbon\Carbon::parse($fechaFin)->format("Y-m") }}',
            mesComparar: 'auto',
            semanaFecha: '{{ \Carbon\Carbon::parse($fechaFin)->format("Y-m-d") }}',
            semanaComparar: 'auto',
            anoBase: '{{ \Carbon\Carbon::parse($fechaFin)->format("Y") }}',
            anoComparar: 'auto',
            instance: null,
            data: null
        },
        compras: {
            nivel: 'mensual', // Por defecto: Mensual (Día 1-31)
            mesBase: '{{ \Carbon\Carbon::parse($fechaFin)->format("Y-m") }}',
            mesComparar: 'auto',
            semanaFecha: '{{ \Carbon\Carbon::parse($fechaFin)->format("Y-m-d") }}',
            semanaComparar: 'auto',
            anoBase: '{{ \Carbon\Carbon::parse($fechaFin)->format("Y") }}',
            anoComparar: 'auto',
            instance: null,
            data: null
        }
    };

    function formatCurrency(val) {
        return '$' + Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    async function cargarGraficoComparativo(tipo = 'ventas') {
        const st = chartState[tipo];
        if (!st) return;

        const loadingEl = document.getElementById(`chart-loading-${tipo}`);
        if (loadingEl) loadingEl.style.display = 'flex';

        try {
            const params = new URLSearchParams({
                tipo: tipo,
                nivel: st.nivel,
                mes_base: st.mesBase,
                mes_comparar: st.mesComparar,
                semana_fecha: st.semanaFecha,
                semana_comparar: st.semanaComparar,
                ano_base: st.anoBase,
                ano_comparar: st.anoComparar
            });

            const res = await fetch(`${chartApiUrl}?${params.toString()}`);
            if (!res.ok) throw new Error('Error al cargar datos del gráfico');
            const data = await res.json();
            st.data = data;

            actualizarControlesUI(tipo, data);
            renderizarCanvasChart(tipo, data);
        } catch (err) {
            console.error('Error en cargarGraficoComparativo:', err);
        } finally {
            if (loadingEl) loadingEl.style.display = 'none';
        }
    }

    function actualizarControlesUI(tipo, data) {
        const st = chartState[tipo];
        const primaryColor = tipo === 'ventas' ? 'var(--primary)' : 'var(--accent)';

        // 1. Actualizar Píldoras de Nivel
        ['anual', 'mensual', 'semanal'].forEach(lvl => {
            const pill = document.getElementById(`pill-nivel-${lvl}-${tipo}`);
            if (pill) {
                if (lvl === st.nivel) {
                    pill.style.background = 'white';
                    pill.style.color = primaryColor;
                    pill.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                } else {
                    pill.style.background = 'transparent';
                    pill.style.color = 'var(--text-muted)';
                    pill.style.boxShadow = 'none';
                }
            }
        });

        // 2. Botón Volver / Drill Up
        const btnBack = document.getElementById(`btn-back-level-${tipo}`);
        const labelBack = document.getElementById(`label-back-level-${tipo}`);
        if (btnBack && labelBack) {
            if (st.nivel === 'semanal') {
                btnBack.style.display = 'inline-flex';
                labelBack.innerText = `Volver a ${data.mesPertenecienteLabel || 'Mes'}`;
            } else if (st.nivel === 'mensual') {
                btnBack.style.display = 'inline-flex';
                labelBack.innerText = `Ver Meses de ${data.anoPertenece || 'Año'}`;
            } else {
                btnBack.style.display = 'none';
            }
        }

        // 3. Botón Adelante / Drill Down hacia Semanas
        const btnFwdWeeks = document.getElementById(`btn-forward-weeks-${tipo}`);
        if (btnFwdWeeks) {
            if (st.nivel === 'mensual') {
                btnFwdWeeks.style.display = 'inline-flex';
                btnFwdWeeks.innerHTML = `<span>Ver por Semanas</span> <i class="fa-solid fa-arrow-right"></i>`;
            } else if (st.nivel === 'anual') {
                btnFwdWeeks.style.display = 'inline-flex';
                btnFwdWeeks.innerHTML = `<span>Ver Mes Base</span> <i class="fa-solid fa-arrow-right"></i>`;
            } else {
                btnFwdWeeks.style.display = 'none';
            }
        }

        // 4. Título Principal
        const tituloEl = document.getElementById(`titulo-grafico-${tipo}`);
        if (tituloEl) {
            if (st.nivel === 'mensual') {
                tituloEl.innerText = `Comparativo Mensual: ${data.mesBaseLabel} ${data.hasComparison ? 'vs ' + data.mesCompararLabel : ''}`;
            } else if (st.nivel === 'semanal') {
                tituloEl.innerText = `Comparativo Semanal: ${data.semanaBaseLabel} ${data.hasComparison ? 'vs ' + data.semanaCompararLabel : ''}`;
            } else if (st.nivel === 'anual') {
                tituloEl.innerText = `Comparativo Anual: ${data.anoBaseLabel} ${data.hasComparison ? 'vs ' + data.anoCompararLabel : ''}`;
            }
        }

        // 5. Selectores de Período Base
        const selMes = document.getElementById(`select-mes-base-${tipo}`);
        const selSem = document.getElementById(`select-semana-base-${tipo}`);
        const selAno = document.getElementById(`select-ano-base-${tipo}`);

        if (selMes && selSem && selAno) {
            selMes.style.display = st.nivel === 'mensual' ? 'inline-block' : 'none';
            selSem.style.display = st.nivel === 'semanal' ? 'inline-block' : 'none';
            selAno.style.display = st.nivel === 'anual' ? 'inline-block' : 'none';

            // Llenar select de Meses
            if (data.mesesDisponibles && selMes.options.length === 0) {
                selMes.innerHTML = data.mesesDisponibles.map(m => `<option value="${m.value}">${m.label}</option>`).join('');
            }
            if (data.mesBase) selMes.value = data.mesBase;

            // Llenar select de Semanas
            if (data.semanasDelMes) {
                selSem.innerHTML = data.semanasDelMes.map(s => `<option value="${s.fecha}">${s.label}</option>`).join('');
                if (data.semanaFecha) selSem.value = data.semanaFecha;
            }

            // Llenar select de Años
            if (data.anosDisponibles && selAno.options.length === 0) {
                selAno.innerHTML = data.anosDisponibles.map(a => `<option value="${a}">Año ${a}</option>`).join('');
            }
            if (data.anoBase) selAno.value = String(data.anoBase);
        }

        // 6. Selector de Comparar Contra
        const selComp = document.getElementById(`select-comparar-${tipo}`);
        if (selComp) {
            let compHtml = '';
            if (st.nivel === 'mensual') {
                compHtml += `<option value="auto">Mes Anterior (Automático)</option>`;
                compHtml += `<option value="mismo_ano_anterior">Mismo Mes del Año Anterior</option>`;
                compHtml += `<option value="ventas_vs_compras">${tipo === 'ventas' ? 'Ventas vs Compras (Costos)' : 'Compras vs Ventas'}</option>`;
                compHtml += `<option value="ninguno">Sin Comparación (Solo este mes)</option>`;
                if (data.mesesDisponibles && data.mesesDisponibles.length > 0) {
                    compHtml += `<optgroup label="Comparar con Mes Específico">`;
                    data.mesesDisponibles.forEach(m => {
                        if (m.value !== data.mesBase) {
                            compHtml += `<option value="${m.value}">${m.label}</option>`;
                        }
                    });
                    compHtml += `</optgroup>`;
                }
                selComp.innerHTML = compHtml;
                selComp.value = st.mesComparar;
            } else if (st.nivel === 'semanal') {
                compHtml += `<option value="auto">Semana Anterior (Inmediata)</option>`;
                compHtml += `<option value="mes_anterior">Misma Semana del Mes Anterior</option>`;
                compHtml += `<option value="ventas_vs_compras">${tipo === 'ventas' ? 'Ventas vs Compras (Costos)' : 'Compras vs Ventas'}</option>`;
                compHtml += `<option value="ninguna">Sin Comparación</option>`;
                selComp.innerHTML = compHtml;
                selComp.value = st.semanaComparar;
            } else if (st.nivel === 'anual') {
                compHtml += `<option value="auto">Año Anterior (Automático)</option>`;
                compHtml += `<option value="ventas_vs_compras">${tipo === 'ventas' ? 'Ventas vs Compras (12 Meses)' : 'Compras vs Ventas (12 Meses)'}</option>`;
                compHtml += `<option value="ninguno">Sin Comparación</option>`;
                if (data.anosDisponibles && data.anosDisponibles.length > 0) {
                    compHtml += `<optgroup label="Comparar con Año Específico">`;
                    data.anosDisponibles.forEach(a => {
                        if (String(a) !== String(data.anoBase)) {
                            compHtml += `<option value="${a}">Año ${a}</option>`;
                        }
                    });
                    compHtml += `</optgroup>`;
                }
                selComp.innerHTML = compHtml;
                selComp.value = st.anoComparar;
            }
        }

        // 7. Banda de Resumen KPI
        const kpiLblBase = document.getElementById(`kpi-label-base-${tipo}`);
        const kpiValBase = document.getElementById(`kpi-val-base-${tipo}`);
        const kpiBoxComp = document.getElementById(`kpi-box-comp-${tipo}`);
        const kpiLblComp = document.getElementById(`kpi-label-comp-${tipo}`);
        const kpiValComp = document.getElementById(`kpi-val-comp-${tipo}`);
        const kpiBoxDiff = document.getElementById(`kpi-box-diff-${tipo}`);
        const kpiValDiff = document.getElementById(`kpi-val-diff-monto-${tipo}`);
        const kpiBadgeDiff = document.getElementById(`kpi-badge-diff-${tipo}`);

        if (kpiLblBase) kpiLblBase.innerText = (data.labelBase || 'Base') + ':';
        if (kpiValBase) kpiValBase.innerText = formatCurrency(data.totalBase);

        if (data.hasComparison) {
            if (kpiBoxComp) kpiBoxComp.style.display = 'block';
            if (kpiBoxDiff) kpiBoxDiff.style.display = 'flex';
            if (kpiLblComp) kpiLblComp.innerText = (data.labelComparar || 'Comparado') + ':';
            if (kpiValComp) kpiValComp.innerText = formatCurrency(data.totalComparar);

            if (kpiValDiff) {
                const prefix = data.diffMonto >= 0 ? '+' : '';
                kpiValDiff.innerText = prefix + formatCurrency(data.diffMonto);
                kpiValDiff.style.color = (tipo === 'ventas' ? data.diffMonto >= 0 : data.diffMonto <= 0) ? '#16a34a' : '#dc2626';
            }

            if (kpiBadgeDiff) {
                const isPositive = (tipo === 'ventas') ? (data.diffPct >= 0) : (data.diffPct <= 0);
                kpiBadgeDiff.className = `badge ${isPositive ? 'badge-success' : 'badge-danger'}`;
                kpiBadgeDiff.innerText = (data.diffPct >= 0 ? '+' : '') + data.diffPct + '%';
            }
        } else {
            if (kpiBoxComp) kpiBoxComp.style.display = 'none';
            if (kpiBoxDiff) kpiBoxDiff.style.display = 'none';
        }
    }

    function renderizarCanvasChart(tipo, data) {
        const st = chartState[tipo];
        const canvasId = tipo === 'ventas' ? 'canvasChartVentas' : 'canvasChartCompras';
        const canvas = document.getElementById(canvasId);
        if (!canvas || typeof Chart === 'undefined') return;

        const ctx = canvas.getContext('2d');
        if (st.instance) st.instance.destroy();

        const baseColor = tipo === 'ventas' ? '#2563eb' : '#ea580c';
        const baseBgColor = tipo === 'ventas' ? 'rgba(37, 99, 235, 0.12)' : 'rgba(234, 88, 12, 0.12)';
        const compColor = data.isVentasVsCompras ? (tipo === 'ventas' ? '#ea580c' : '#2563eb') : '#94a3b8';
        const compBgColor = data.isVentasVsCompras ? (tipo === 'ventas' ? 'rgba(234, 88, 12, 0.8)' : 'rgba(37, 99, 235, 0.8)') : 'rgba(148, 163, 184, 0.06)';

        const isBar = st.nivel === 'anual';

        const datasets = [];

        // Dataset Base
        datasets.push({
            type: isBar ? 'bar' : 'line',
            label: data.labelBase,
            data: data.dataBase,
            borderColor: baseColor,
            backgroundColor: isBar ? (tipo === 'ventas' ? 'rgba(37, 99, 235, 0.85)' : 'rgba(234, 88, 12, 0.85)') : baseBgColor,
            borderWidth: isBar ? 1 : 3,
            borderRadius: isBar ? 6 : 0,
            tension: isBar ? 0 : 0.35,
            fill: !isBar,
            pointRadius: isBar ? 0 : 4,
            pointHoverRadius: isBar ? 0 : 6,
            pointBackgroundColor: baseColor
        });

        // Dataset Comparar
        if (data.hasComparison && data.dataComparar && data.dataComparar.length > 0) {
            datasets.push({
                type: isBar ? 'bar' : 'line',
                label: data.labelComparar,
                data: data.dataComparar,
                borderColor: compColor,
                backgroundColor: isBar ? compBgColor : 'rgba(148, 163, 184, 0.05)',
                borderWidth: isBar ? 1 : 2,
                borderRadius: isBar ? 6 : 0,
                borderDash: (!isBar && !data.isVentasVsCompras) ? [5, 5] : [],
                tension: isBar ? 0 : 0.35,
                fill: false,
                pointRadius: isBar ? 0 : 3.5,
                pointHoverRadius: isBar ? 0 : 5.5,
                pointBackgroundColor: compColor
            });
        }

        st.instance = new Chart(ctx, {
            data: {
                labels: data.labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { font: { weight: '600' } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) { return ctx.dataset.label + ': ' + formatCurrency(ctx.parsed.y); }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(val) { return '$' + val; } },
                        grid: { color: '#f1f5f9' }
                    },
                    x: { grid: { display: false } }
                },
                onClick: function(evt, elements) {
                    if (elements.length > 0 && st.nivel === 'anual') {
                        // Click on month bar -> Drill down to that month!
                        const idx = elements[0].index;
                        const monthNum = String(idx + 1).padStart(2, '0');
                        const targetYm = `${data.anoBase}-${monthNum}`;
                        st.mesBase = targetYm;
                        st.nivel = 'mensual';
                        cargarGraficoComparativo(tipo);
                    }
                }
            }
        });
    }

    // Funciones de navegación e interacción
    function setNivelGrafico(tipo, nivel) {
        chartState[tipo].nivel = nivel;
        cargarGraficoComparativo(tipo);
    }

    function navegarNivelAtras(tipo) {
        const st = chartState[tipo];
        if (st.nivel === 'semanal') {
            setNivelGrafico(tipo, 'mensual');
        } else if (st.nivel === 'mensual') {
            setNivelGrafico(tipo, 'anual');
        }
    }

    function desglosarEnSemanas(tipo) {
        const st = chartState[tipo];
        if (st.nivel === 'mensual') {
            setNivelGrafico(tipo, 'semanal');
        } else if (st.nivel === 'anual') {
            setNivelGrafico(tipo, 'mensual');
        }
    }

    function periodoPasoAnterior(tipo) {
        const st = chartState[tipo];
        const data = st.data;
        if (!data) return;

        if (st.nivel === 'mensual' && data.mesBasePrev) {
            st.mesBase = data.mesBasePrev;
        } else if (st.nivel === 'semanal' && data.semanaPrevFecha) {
            st.semanaFecha = data.semanaPrevFecha;
        } else if (st.nivel === 'anual' && data.anoBasePrev) {
            st.anoBase = data.anoBasePrev;
        }
        cargarGraficoComparativo(tipo);
    }

    function periodoPasoSiguiente(tipo) {
        const st = chartState[tipo];
        const data = st.data;
        if (!data) return;

        if (st.nivel === 'mensual' && data.mesBaseNext) {
            st.mesBase = data.mesBaseNext;
        } else if (st.nivel === 'semanal' && data.semanaNextFecha) {
            st.semanaFecha = data.semanaNextFecha;
        } else if (st.nivel === 'anual' && data.anoBaseNext) {
            st.anoBase = data.anoBaseNext;
        }
        cargarGraficoComparativo(tipo);
    }

    function onSelectMesBaseChange(tipo, val) {
        chartState[tipo].mesBase = val;
        cargarGraficoComparativo(tipo);
    }

    function onSelectSemanaBaseChange(tipo, val) {
        chartState[tipo].semanaFecha = val;
        cargarGraficoComparativo(tipo);
    }

    function onSelectAnoBaseChange(tipo, val) {
        chartState[tipo].anoBase = val;
        cargarGraficoComparativo(tipo);
    }

    function onSelectCompararChange(tipo, val) {
        const st = chartState[tipo];
        if (st.nivel === 'mensual') st.mesComparar = val;
        else if (st.nivel === 'semanal') st.semanaComparar = val;
        else if (st.nivel === 'anual') st.anoComparar = val;
        cargarGraficoComparativo(tipo);
    }

    // Inicializar al cargar el DOM
    document.addEventListener('DOMContentLoaded', function() {
        @if($tab === 'ventas')
            cargarGraficoComparativo('ventas');
        @elseif($tab === 'compras')
            cargarGraficoComparativo('compras');
        @endif
    });
</script>
@endpush

@push('modals')
<!-- MODAL COMPROBANTE DE ABONO (TICKET 80mm) -->
<div id="modalComprobanteAbono" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 12px; width: 380px; max-width: 95%; display: flex; flex-direction: column; max-height: 90vh; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <div class="no-print" style="padding: 0.85rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--text-main);">
                <i class="fa-solid fa-receipt" style="color: #10b981;"></i> Comprobante de Abono
            </h3>
            <button type="button" onclick="closeModal('modalComprobanteAbono')" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div id="printableAbonoArea" style="padding: 1.5rem; overflow-y: auto; font-family: monospace; font-size: 12px; color: black; background: white;">
        </div>
        <div class="no-print" style="padding: 0.85rem 1.25rem; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn-modern btn-secondary" onclick="closeModal('modalComprobanteAbono')">Cerrar</button>
            <button type="button" class="btn-modern btn-primary" style="background: #10b981; border-color: #10b981;" onclick="imprimirTicketAbono()">
                <i class="fa-solid fa-print"></i> Imprimir Ticket
            </button>
        </div>
    </div>
</div>
@endpush

@endsection
