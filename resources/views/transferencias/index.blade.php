@extends('layouts.app')

@section('title', 'Transferencias Multisucursal')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Transferencias entre Sucursales</h1>
        <p class="page-subtitle">Envío, recepción y seguimiento de mercancía entre sucursales.
            @if($sucursalActual)
                <span style="background: var(--accent); color: #fff; padding: 2px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 600; margin-left: 6px;">
                    <i class="fa-solid fa-store"></i> {{ $sucursalActual->nombre }} ({{ $sucursalActual->codigo }})
                </span>
            @else
                <a href="{{ route('configuracion.index', ['tab' => 'sucursales']) }}" style="background: #fbbf24; color: #78350f; padding: 2px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 600; margin-left: 6px; text-decoration: none;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Asignar Sucursal en Configuración
                </a>
            @endif
        </p>
    </div>
</div>

{{-- Pestañas --}}
<div class="tabs-bar" style="display: flex; gap: 0; border-bottom: 2px solid var(--border); margin-bottom: 1.5rem;">
    <a href="{{ route('transferencias.index', ['tab' => 'enviar']) }}"
       class="tab-btn {{ $tab === 'enviar' ? 'active' : '' }}"
       style="padding: 0.75rem 1.5rem; font-weight: 600; font-size: 0.9rem; color: {{ $tab === 'enviar' ? 'var(--accent)' : 'var(--text-muted)' }}; border-bottom: 2px solid {{ $tab === 'enviar' ? 'var(--accent)' : 'transparent' }}; text-decoration: none; transition: all 0.2s;">
        <i class="fa-solid fa-paper-plane"></i> Enviar Mercancía
    </a>
    <a href="{{ route('transferencias.index', ['tab' => 'recibir']) }}"
       class="tab-btn {{ $tab === 'recibir' ? 'active' : '' }}"
       style="padding: 0.75rem 1.5rem; font-weight: 600; font-size: 0.9rem; color: {{ $tab === 'recibir' ? 'var(--accent)' : 'var(--text-muted)' }}; border-bottom: 2px solid {{ $tab === 'recibir' ? 'var(--accent)' : 'transparent' }}; text-decoration: none; transition: all 0.2s; position: relative;">
        <i class="fa-solid fa-inbox"></i> Recepciones
        @if($pendientesCount > 0)
            <span style="background:#ef4444;color:#fff;border-radius:50%;font-size:0.65rem;font-weight:700;padding:2px 6px;margin-left:4px;">{{ $pendientesCount }}</span>
        @endif
    </a>
</div>

{{-- ═══════════════════════════════════════════════════════════ --}}
{{--  TAB 1: ENVIAR MERCANCÍA                                  --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if($tab === 'enviar')
<div id="panelEnviar">
    @if(!$sucursalActual)
        <div class="card" style="text-align: center; padding: 3rem;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 3rem; color: #fbbf24; margin-bottom: 1rem;"></i>
            <h3 style="margin-bottom: 0.5rem;">Sucursal no configurada</h3>
            <p style="color: var(--text-muted);">Debe asignar la sucursal actual de este equipo antes de poder enviar mercancía.</p>
            <a href="{{ route('configuracion.index', ['tab' => 'sucursales']) }}" class="btn-modern btn-accent" style="margin-top: 1rem;">
                <i class="fa-solid fa-building"></i> Configurar Sucursal
            </a>
        </div>
    @elseif($sucursalesDestino->isEmpty())
        <div class="card" style="text-align: center; padding: 3rem;">
            <i class="fa-solid fa-store-slash" style="font-size: 3rem; color: #94a3b8; margin-bottom: 1rem;"></i>
            <h3 style="margin-bottom: 0.5rem;">No hay sucursales destino</h3>
            <p style="color: var(--text-muted);">Registre al menos una sucursal adicional para poder enviar mercancía.</p>
            <a href="{{ route('configuracion.index', ['tab' => 'sucursales']) }}" class="btn-modern btn-accent" style="margin-top: 1rem;">
                <i class="fa-solid fa-plus"></i> Registrar Sucursal en Configuración
            </a>
        </div>
    @else
    <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="margin: 0;"><i class="fa-solid fa-paper-plane" style="color: var(--accent);"></i> Nueva Transferencia de Salida</h3>
            <span style="font-size: 0.85rem; color: var(--text-muted);">Folio se generará automáticamente</span>
        </div>

        {{-- Sucursal destino --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.35rem; display: block;">Sucursal Destino *</label>
                <select id="selectDestinoId" class="input-modern" required>
                    <option value="">— Seleccione sucursal destino —</option>
                    @foreach($sucursalesDestino as $suc)
                        <option value="{{ $suc->id }}">{{ $suc->nombre }} ({{ $suc->codigo }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.35rem; display: block;">Notas / Observaciones</label>
                <input type="text" id="inputNotas" class="input-modern" placeholder="Ej: Envío de cortes especiales para pedido...">
            </div>
        </div>

        {{-- Buscador de artículos --}}
        <div style="margin-bottom: 1rem;">
            <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.35rem; display: block;">Agregar Productos al Envío</label>
            <div style="position: relative;">
                <input type="text" id="buscadorArticulo" class="input-modern" placeholder="🔍 Buscar por nombre, código o descripción..." autocomplete="off" style="padding-left: 2.5rem;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 0.85rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
            </div>
            <div id="resultadosBusqueda" style="display: none; position: absolute; z-index: 100; background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius-md); max-height: 250px; overflow-y: auto; width: calc(100% - 3rem); box-shadow: 0 8px 24px rgba(0,0,0,0.15);"></div>
        </div>

        {{-- Tabla de productos agregados --}}
        <div style="overflow-x: auto;">
            <table class="modern-table" id="tablaProductosEnvio">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Stock Disp.</th>
                        <th style="width: 130px;">Cantidad a Enviar</th>
                        <th>Costo Unit.</th>
                        <th>Subtotal</th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody id="bodyProductosEnvio">
                    <tr id="filaSinProductos">
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            <i class="fa-solid fa-box-open" style="font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                            Busque y agregue productos para enviar
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Resumen y botón de envío --}}
        <div id="resumenEnvio" style="display: none; margin-top: 1.5rem; padding: 1rem 1.5rem; background: var(--bg-main); border-radius: var(--radius-md); border: 1px solid var(--border);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; gap: 2rem;">
                    <div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600;">TOTAL PESO</div>
                        <div style="font-size: 1.15rem; font-weight: 700;" id="totalPesoEnvio">0.000 {{ strtoupper($settings['unidad_peso'] ?? 'LB') }}</div>
                    </div>
                    <div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600;">TOTAL UNIDADES</div>
                        <div style="font-size: 1.15rem; font-weight: 700;" id="totalUnidadesEnvio">0</div>
                    </div>
                    <div>
                        <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 600;">COSTO TOTAL</div>
                        <div style="font-size: 1.15rem; font-weight: 700; color: var(--accent);" id="totalCostoEnvio">$0.00</div>
                    </div>
                </div>
                <button onclick="procesarEnvio()" class="btn-modern btn-accent" style="padding: 0.75rem 2rem; font-size: 1rem;">
                    <i class="fa-solid fa-paper-plane"></i> Procesar Envío
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{--  TAB 2: RECEPCIONES (Entradas)                            --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if($tab === 'recibir')
<div id="panelRecibir">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h3 style="margin: 0;"><i class="fa-solid fa-inbox" style="color: var(--accent);"></i> Transferencias Entrantes</h3>
        <div style="display: flex; gap: 0.75rem;">
            <button onclick="sincronizarNube()" class="btn-modern btn-secondary" id="btnSyncNube">
                <i class="fa-solid fa-cloud-arrow-down"></i> Sincronizar con Nube
            </button>
            <label class="btn-modern btn-secondary" style="cursor: pointer; margin: 0;">
                <i class="fa-solid fa-file-import"></i> Importar .TRN
                <input type="file" accept=".trn" onchange="importarArchivoTrn(this)" style="display: none;">
            </label>
        </div>
    </div>

    {{-- Estado de conexión --}}
    <div id="estadoConexionNube" style="display: none; margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem;"></div>

    {{-- Lista de transferencias pendientes locales --}}
    <div id="listaPendientes">
        @php
            $pendientesLocales = $sucursalActual
                ? \App\Models\Transferencia::where('sucursal_destino_id', $sucursalActual->id)
                    ->where('estado', 'en_transito')
                    ->with(['sucursalOrigen', 'detalles', 'usuario'])
                    ->orderBy('fecha_envio', 'desc')
                    ->get()
                : collect();
        @endphp

        @if($pendientesLocales->isEmpty())
            <div class="card" style="text-align: center; padding: 3rem;">
                <i class="fa-solid fa-inbox" style="font-size: 3rem; color: #94a3b8; margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 0.5rem;">Sin transferencias pendientes</h3>
                <p style="color: var(--text-muted);">No hay envíos pendientes de recibir. Presione <strong>"Sincronizar con Nube"</strong> para consultar.</p>
            </div>
        @else
            @foreach($pendientesLocales as $pend)
            <div class="card" style="margin-bottom: 1rem; border-left: 4px solid #fbbf24;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="font-size: 1.1rem; font-weight: 700;">{{ $pend->folio }}</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                            <i class="fa-solid fa-store"></i> Desde: <strong>{{ $pend->sucursalOrigen->nombre ?? 'Desconocida' }}</strong>
                            &nbsp;|&nbsp;
                            <i class="fa-solid fa-user"></i> {{ $pend->usuario->name ?? 'N/A' }}
                            &nbsp;|&nbsp;
                            <i class="fa-solid fa-calendar"></i> {{ $pend->fecha_envio ? $pend->fecha_envio->format('d/m/Y H:i') : 'N/A' }}
                        </div>
                        <div style="margin-top: 8px; display: flex; gap: 1.5rem; font-size: 0.85rem;">
                            <span><strong>{{ number_format($pend->total_peso, 3) }}</strong> {{ strtoupper($settings['unidad_peso'] ?? 'LB') }}</span>
                            <span><strong>{{ $pend->total_unidades }}</strong> UND</span>
                            <span style="color: var(--accent); font-weight: 700;">${{ number_format($pend->costo_total, 2) }}</span>
                        </div>
                        @if($pend->notas)
                            <div style="margin-top: 6px; font-size: 0.82rem; color: var(--text-muted);"><i class="fa-solid fa-note-sticky"></i> {{ $pend->notas }}</div>
                        @endif
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button onclick="verDetalleTransferencia({{ $pend->id }})" class="btn-modern btn-secondary" style="font-size: 0.82rem;">
                            <i class="fa-solid fa-eye"></i> Ver Detalle
                        </button>
                        <button onclick="confirmarRecepcion({{ $pend->id }}, '{{ $pend->folio }}')" class="btn-modern btn-accent" style="font-size: 0.82rem;">
                            <i class="fa-solid fa-check-double"></i> Confirmar Recepción
                        </button>
                    </div>
                </div>

                {{-- Detalle colapsable --}}
                <div id="detalle-{{ $pend->id }}" style="display: none; margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 1rem;">
                    <table class="modern-table" style="font-size: 0.85rem;">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pend->detalles as $det)
                            <tr>
                                <td>{{ $det->codigo ?? '—' }}</td>
                                <td>{{ $det->descripcion }}</td>
                                <td>{{ number_format($det->cantidad_enviada, $det->tipo_articulo === 'unidad' ? 0 : 3) }} {{ $det->unidad_medida }}</td>
                                <td>${{ number_format($det->subtotal_costo, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
        @endif
    </div>

    {{-- Contenedor para transferencias desde la nube (AJAX) --}}
    <div id="transferenciasNubeContainer"></div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Datos de artículos para el buscador ───
    const articulos = @json($articulos);
    const unidadPeso = '{{ strtoupper($settings["unidad_peso"] ?? "LB") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let productosEnvio = [];

    // ─── Buscador de Artículos ───
    const buscador = document.getElementById('buscadorArticulo');
    const resultados = document.getElementById('resultadosBusqueda');

    if (buscador) {
        buscador.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            if (query.length < 2) { resultados.style.display = 'none'; return; }

            const filtrados = articulos.filter(a => {
                const yaAgregado = productosEnvio.some(p => p.articulo_id === a.id);
                if (yaAgregado) return false;
                return (a.descripcion && a.descripcion.toLowerCase().includes(query)) ||
                       (a.codigo && a.codigo.toLowerCase().includes(query)) ||
                       (a.codigo_cliente && a.codigo_cliente.toLowerCase().includes(query));
            }).slice(0, 10);

            if (filtrados.length === 0) {
                resultados.innerHTML = '<div style="padding: 1rem; text-align: center; color: var(--text-muted);">Sin resultados</div>';
            } else {
                resultados.innerHTML = filtrados.map(a => `
                    <div onclick="agregarProducto(${a.id})" style="padding: 0.65rem 1rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);"
                         onmouseover="this.style.background='var(--bg-main)'" onmouseout="this.style.background='transparent'">
                        <div>
                            <strong>${a.descripcion}</strong>
                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-left: 8px;">${a.codigo || a.codigo_cliente || ''}</span>
                        </div>
                        <span style="font-size: 0.82rem; font-weight: 600; color: var(--accent);">
                            Stock: ${parseFloat(a.stock).toFixed(a.tipo_articulo === 'unidad' ? 0 : 3)} ${a.tipo_articulo === 'unidad' ? 'UND' : unidadPeso}
                        </span>
                    </div>
                `).join('');
            }
            resultados.style.display = 'block';
        });

        document.addEventListener('click', function(e) {
            if (!buscador.contains(e.target) && !resultados.contains(e.target)) {
                resultados.style.display = 'none';
            }
        });
    }

    // ─── Agregar Producto a la Tabla ───
    window.agregarProducto = function(articuloId) {
        const art = articulos.find(a => a.id === articuloId);
        if (!art) return;

        const esUnidad = art.tipo_articulo === 'unidad';
        productosEnvio.push({
            articulo_id: art.id,
            codigo: art.codigo || art.codigo_cliente || '',
            descripcion: art.descripcion,
            tipo_articulo: art.tipo_articulo || 'pesable',
            stock: parseFloat(art.stock),
            cantidad: esUnidad ? 1 : 1.000,
            costo_unitario: parseFloat(art.precio_compra || 0),
            compra_detalle_id: null,
        });

        renderTablaEnvio();
        buscador.value = '';
        resultados.style.display = 'none';
        buscador.focus();
    };

    window.renderTablaEnvio = function() {
        const tbody = document.getElementById('bodyProductosEnvio');
        const filaSin = document.getElementById('filaSinProductos');
        const resumen = document.getElementById('resumenEnvio');

        if (productosEnvio.length === 0) {
            filaSin.style.display = '';
            resumen.style.display = 'none';
            return;
        }

        filaSin.style.display = 'none';
        resumen.style.display = '';

        // Reconstruir filas
        tbody.innerHTML = '';
        let totalPeso = 0, totalUnd = 0, totalCosto = 0;

        productosEnvio.forEach((p, idx) => {
            const esUnidad = p.tipo_articulo === 'unidad';
            const subtotal = p.cantidad * p.costo_unitario;
            if (esUnidad) totalUnd += parseInt(p.cantidad);
            else totalPeso += p.cantidad;
            totalCosto += subtotal;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${p.codigo || '—'}</td>
                <td>${p.descripcion}</td>
                <td><span style="font-size:0.78rem;padding:2px 8px;border-radius:12px;background:${esUnidad ? '#dbeafe;color:#1e40af' : '#fef3c7;color:#92400e'};font-weight:600;">${esUnidad ? 'Unidad' : 'Pesable'}</span></td>
                <td>${p.stock.toFixed(esUnidad ? 0 : 3)} ${esUnidad ? 'UND' : unidadPeso}</td>
                <td>
                    <input type="number" value="${esUnidad ? parseInt(p.cantidad) : p.cantidad.toFixed(3)}"
                           min="${esUnidad ? 1 : 0.001}" max="${p.stock}" step="${esUnidad ? 1 : 0.001}"
                           class="input-modern" style="width: 110px; text-align: center;"
                           onchange="actualizarCantidad(${idx}, this.value)">
                </td>
                <td>$${p.costo_unitario.toFixed(2)}</td>
                <td style="font-weight:600;">$${subtotal.toFixed(2)}</td>
                <td><button onclick="quitarProducto(${idx})" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:1.1rem;" title="Quitar"><i class="fa-solid fa-circle-xmark"></i></button></td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('totalPesoEnvio').textContent = totalPeso.toFixed(3) + ' ' + unidadPeso;
        document.getElementById('totalUnidadesEnvio').textContent = totalUnd;
        document.getElementById('totalCostoEnvio').textContent = '$' + totalCosto.toFixed(2);
    };

    window.actualizarCantidad = function(idx, val) {
        const p = productosEnvio[idx];
        let cantidad = parseFloat(val);
        if (isNaN(cantidad) || cantidad <= 0) cantidad = p.tipo_articulo === 'unidad' ? 1 : 0.001;
        if (cantidad > p.stock) cantidad = p.stock;
        p.cantidad = cantidad;
        renderTablaEnvio();
    };

    window.quitarProducto = function(idx) {
        productosEnvio.splice(idx, 1);
        renderTablaEnvio();
    };

    // ─── Procesar Envío ───
    window.procesarEnvio = function() {
        const destinoId = document.getElementById('selectDestinoId')?.value;
        const notas = document.getElementById('inputNotas')?.value || '';

        if (!destinoId) { alert('Seleccione la sucursal destino.'); return; }
        if (productosEnvio.length === 0) { alert('Agregue al menos un producto.'); return; }

        if (!confirm('¿Está seguro de procesar este envío? El stock será descontado de inmediato.')) return;

        const payload = {
            sucursal_destino_id: parseInt(destinoId),
            notas: notas,
            detalles: productosEnvio.map(p => ({
                articulo_id: p.articulo_id,
                cantidad: p.cantidad,
                compra_detalle_id: p.compra_detalle_id,
            })),
        };

        fetch('{{ route("transferencias.store") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                let msg = data.message;
                if (data.sync && !data.sync.success) {
                    msg += '\n\n⚠️ Aviso de sincronización: ' + (data.sync.error || 'No se pudo enviar a la nube.');
                    if (data.sync.offline) {
                        msg += '\nSe ha guardado localmente. Descargue el archivo .TRN desde el Historial como respaldo.';
                    }
                } else if (data.sync && data.sync.success) {
                    msg += '\n\n☁️ Sincronizado con la nube exitosamente.';
                }
                alert(msg);

                // Abrir ticket en nueva ventana
                if (data.transferencia_id) {
                    window.open('/transferencias/' + data.transferencia_id + '/ticket', '_blank', 'width=350,height=600');
                }
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(err => alert('Error de conexión: ' + err.message));
    };

    // ─── Sincronizar con Nube ───
    window.sincronizarNube = function() {
        const btn = document.getElementById('btnSyncNube');
        const estadoDiv = document.getElementById('estadoConexionNube');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Consultando...';

        fetch('{{ route("transferencias.api.sync") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Sincronizar con Nube';

            if (data.cloud && data.cloud.success && data.cloud.count > 0) {
                // Hay transferencias nuevas en la nube, importarlas
                const transferencias = data.cloud.transferencias;
                let importadas = 0;

                const importarSiguiente = (idx) => {
                    if (idx >= transferencias.length) {
                        estadoDiv.style.display = 'block';
                        estadoDiv.style.background = '#dcfce7';
                        estadoDiv.style.color = '#166534';
                        estadoDiv.innerHTML = `<i class="fa-solid fa-circle-check"></i> Se importaron ${importadas} transferencia(s) desde la nube.`;
                        if (importadas > 0) setTimeout(() => location.reload(), 1500);
                        return;
                    }

                    const t = transferencias[idx];
                    fetch('{{ route("transferencias.api.importar-nube") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                        body: JSON.stringify({ folio: t.folio, payload: t }),
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) importadas++;
                        importarSiguiente(idx + 1);
                    })
                    .catch(() => importarSiguiente(idx + 1));
                };

                importarSiguiente(0);
            } else {
                estadoDiv.style.display = 'block';
                estadoDiv.style.background = '#f0f9ff';
                estadoDiv.style.color = '#0c4a6e';
                estadoDiv.innerHTML = '<i class="fa-solid fa-cloud"></i> No hay transferencias nuevas pendientes en la nube.';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Sincronizar con Nube';
            estadoDiv.style.display = 'block';
            estadoDiv.style.background = '#fee2e2';
            estadoDiv.style.color = '#991b1b';
            estadoDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Error al conectar con la nube: ' + err.message;
        });
    };

    // ─── Importar Archivo .TRN ───
    window.importarArchivoTrn = function(input) {
        if (!input.files.length) return;
        const formData = new FormData();
        formData.append('archivo_trn', input.files[0]);

        fetch('{{ route("transferencias.importar-trn") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: formData,
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Archivo inválido'));
            }
        })
        .catch(err => alert('Error: ' + err.message));

        input.value = '';
    };

    // ─── Ver Detalle de Transferencia Pendiente ───
    window.verDetalleTransferencia = function(id) {
        const detalle = document.getElementById('detalle-' + id);
        if (detalle) {
            detalle.style.display = detalle.style.display === 'none' ? '' : 'none';
        }
    };

    // ─── Confirmar Recepción ───
    window.confirmarRecepcion = function(id, folio) {
        if (!confirm(`¿Confirmar la recepción de la transferencia ${folio}?\n\nEl inventario de esta sucursal será actualizado automáticamente.`)) return;

        fetch(`/transferencias/${id}/recibir`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({}),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'No se pudo confirmar'));
            }
        })
        .catch(err => alert('Error: ' + err.message));
    };

    // ─── Cancelar Transferencia ───
    window.cancelarTransferencia = function(id, folio) {
        if (!confirm(`¿Cancelar la transferencia ${folio}?\n\nEl stock será devuelto al inventario.`)) return;

        fetch(`/transferencias/${id}/cancelar`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { alert(data.message); location.reload(); }
            else alert('Error: ' + (data.error || 'Error'));
        })
        .catch(err => alert('Error: ' + err.message));
    };

    // ─── Gestión de Sucursales ───
    };
});
</script>
@endpush
