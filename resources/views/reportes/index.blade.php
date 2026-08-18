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
    <a href="{{ route('reportes.index', ['tab' => 'caja', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin]) }}" 
       class="tab-item {{ $tab === 'caja' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'caja' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'caja' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-vault"></i> Reporte de Caja
    </a>
    <a href="{{ route('reportes.index', ['tab' => 'inventario', 'familia_id' => $familiaId, 'filtro_stock' => $filtroStock]) }}" 
       class="tab-item {{ $tab === 'inventario' ? 'active' : '' }}" 
       style="padding: 0.75rem 1.25rem; font-weight: 700; text-decoration: none; border-bottom: 3px solid {{ $tab === 'inventario' ? 'var(--primary)' : 'transparent' }}; color: {{ $tab === 'inventario' ? 'var(--primary)' : 'var(--text-muted)' }};">
        <i class="fa-solid fa-boxes-stacked"></i> Inventario Comparativo (Físico vs Mínimo)
    </a>
</div>

<!-- FILTROS DE BÚSQUEDA -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="GET" action="{{ route('reportes.index') }}" id="form-filtros-reporte" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <input type="hidden" name="fecha_inicio" id="hidden-fecha-inicio" value="{{ $fechaInicio }}">
        <input type="hidden" name="fecha_fin" id="hidden-fecha-fin" value="{{ $fechaFin }}">

        @if($tab === 'ventas' || $tab === 'compras' || $tab === 'caja')
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

        <div style="margin-top: 1rem;">
            {{ $cajasLista->appends(['tab' => 'caja', 'fecha_inicio' => $fechaInicio, 'fecha_fin' => $fechaFin])->links() }}
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

        let html = `
            <div style="text-align: center; margin-bottom: 15px;">
                <h2 style="margin: 0; font-size: 18px; font-weight: 800;">${empresaNombre}</h2>
                <div>RUC/NIT: ${empresaRuc}</div>
                <div>${empresaDireccion}</div>
                <div style="margin-top: 5px; font-weight: bold;">Ticket #${ticketNum}</div>
                <div>Fecha: ${fecha}</div>
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
                <span>TOTAL:</span>
                <span>$${parseFloat(venta.total || 0).toFixed(2)}</span>
            </div>
            <hr style="border-top: 1px dashed black; margin: 10px 0;">
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
</script>
@endpush

@endsection
