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
    <a href="{{ route('transferencias.index', ['tab' => 'nube']) }}"
       class="tab-btn {{ $tab === 'nube' ? 'active' : '' }}"
       style="padding: 0.75rem 1.5rem; font-weight: 600; font-size: 0.9rem; color: {{ $tab === 'nube' ? '#0284c7' : 'var(--text-muted)' }}; border-bottom: 2px solid {{ $tab === 'nube' ? '#0284c7' : 'transparent' }}; text-decoration: none; transition: all 0.2s; position: relative;">
        <i class="fa-solid fa-cloud"></i> Buzón en la Nube
        @if(isset($totalNubeCount) && $totalNubeCount > 0)
            <span style="background:#0284c7;color:#fff;border-radius:10px;font-size:0.7rem;font-weight:800;padding:2px 7px;margin-left:4px;">{{ $totalNubeCount }}</span>
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 0.5rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                <h3 style="margin: 0;"><i class="fa-solid fa-paper-plane" style="color: var(--accent);"></i> Nueva Transferencia de Salida</h3>
                <span class="badge" style="background: {{ $modoInventario === 'dinamico' ? '#dbeafe' : '#f1f5f9' }}; color: {{ $modoInventario === 'dinamico' ? '#1e40af' : '#475569' }}; font-weight: 700; padding: 0.35rem 0.75rem; font-size: 0.8rem; border-radius: 8px;">
                    <i class="fa-solid {{ $modoInventario === 'dinamico' ? 'fa-cubes' : 'fa-cube' }}"></i> Modo: {{ $modoInventario === 'dinamico' ? 'Dinámico (Por Lotes)' : 'Simple (Stock General)' }}
                </span>
            </div>
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

        {{-- Buscador de artículos inteligente y dinámico --}}
        <div style="margin-bottom: 1.5rem; position: relative;" id="contenedorBuscadorEnvio">
            <label style="font-size: 0.88rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.45rem; display: flex; justify-content: space-between; align-items: center;">
                <span><i class="fa-solid fa-barcode" style="color: var(--accent);"></i> Buscar y Agregar Productos al Envío</span>
                <span style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted);">
                    @if($modoInventario === 'dinamico')
                        <i class="fa-solid fa-cubes"></i> Búsqueda de Lotes por Nombre, Código Proveedor / Barras, Lote o Cliente
                    @else
                        <i class="fa-solid fa-cube"></i> Búsqueda de Stock General por Nombre, Código o Cliente
                    @endif
                </span>
            </label>
            <div style="position: relative;">
                <input type="text" id="buscadorArticulo" class="input-modern" placeholder="🔍 Escriba nombre, escanee código de barras de proveedor, lote o código cliente..." autocomplete="off" style="padding-left: 2.75rem; padding-right: 2.5rem; width: 100%; font-size: 0.95rem; height: 46px; border-radius: 10px; border: 1.5px solid var(--border-color); background: #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 1.1rem;"></i>
                <button type="button" id="btnLimpiarBuscador" onclick="limpiarBuscador()" style="display: none; position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1.1rem; padding: 4px;">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>
            <div id="resultadosBusqueda" style="display: none; position: absolute; top: calc(100% + 6px); left: 0; right: 0; z-index: 9999; background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 10px; max-height: 380px; overflow-y: auto; box-shadow: 0 16px 36px rgba(0,0,0,0.18);"></div>
        </div>

        {{-- Tabla de productos agregados --}}
        <div style="overflow-x: auto;">
            <table class="modern-table" id="tablaProductosEnvio">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        @if($modoInventario === 'dinamico')
                            <th>Lote / Serie</th>
                            <th>Vencimiento</th>
                        @endif
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
                        <td colspan="{{ $modoInventario === 'dinamico' ? '10' : '8' }}" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            <i class="fa-solid fa-box-open" style="font-size: 2rem; margin-bottom: 0.5rem; display: block; opacity: 0.5;"></i>
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
                                @if($modoInventario === 'dinamico')
                                    <th>Lote / Serie</th>
                                    <th>Vencimiento</th>
                                @endif
                                <th>Cantidad</th>
                                <th>Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pend->detalles as $det)
                            <tr>
                                <td>{{ $det->codigo ?? '—' }}</td>
                                <td>{{ $det->descripcion }}</td>
                                @if($modoInventario === 'dinamico')
                                    <td>
                                        @if($det->lote)
                                            <span class="badge" style="background: #e0e7ff; color: #4338ca; font-weight: 700; font-size: 0.75rem;">L: {{ $det->lote }}</span>
                                        @else
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">—</span>
                                        @endif
                                        @if($det->numero_lote)
                                            <span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 2px;">S: {{ $det->numero_lote }}</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 0.82rem; font-weight: 600;">{{ $det->fecha_vencimiento_lote ? $det->fecha_vencimiento_lote->format('d/m/Y') : 'S/V' }}</td>
                                @endif
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

{{-- ═══════════════════════════════════════════════════════════ --}}
{{--  TAB 3: BUZÓN EN LA NUBE (Monitoreo de Hostinger Cloud)     --}}
{{-- ═══════════════════════════════════════════════════════════ --}}
@if($tab === 'nube')
<div id="panelNube">
    {{-- Cabecera con estado de Hostinger y botón de refresco --}}
    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem 1.5rem; background: linear-gradient(to right, #f8fafc, #ffffff); border-left: 4px solid #0284c7;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <div style="width: 10px; height: 10px; border-radius: 50%; background: {{ ($cloudStatus['success'] ?? false) ? '#10b981' : '#ef4444' }};"></div>
                    <strong style="font-size: 1rem; color: var(--text-main);">Servidor de Enlace en Hostinger (Cloud Hub)</strong>
                    <span class="badge" style="background: {{ ($cloudStatus['success'] ?? false) ? '#dcfce7' : '#fee2e2' }}; color: {{ ($cloudStatus['success'] ?? false) ? '#166534' : '#991b1b' }}; font-weight: 700; font-size: 0.75rem;">
                        {{ ($cloudStatus['success'] ?? false) ? 'ONLINE' : 'DESCONECTADO' }}
                    </span>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                    <i class="fa-solid fa-link"></i> Endpoint: <code>{{ $settings['cloud_sync_endpoint'] ?? 'https://intellicarnicsync.intellisolution.net' }}</code>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <button onclick="refrescarBuzonNube()" class="btn-modern btn-secondary" id="btnRefrescarBuzonNube" style="padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 700;">
                    <i class="fa-solid fa-rotate"></i> Actualizar Buzón Cloud
                </button>
            </div>
        </div>
    </div>

    {{-- Filtros y estadísticas del buzón --}}
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-boxes-packing" style="color: #0284c7;"></i> Paquetes Alojados en la Nube
            <span class="badge" style="background: #e0f2fe; color: #0369a1; font-weight: 800;" id="contadorNubeHeader">{{ count($transferenciasNube) }}</span>
        </h3>
        <div>
            <select id="filtroDestinoNube" class="input-modern" onchange="filtrarTransferenciasNube()" style="font-size: 0.85rem; padding: 0.4rem 0.85rem; font-weight: 600;">
                <option value="todos">🌐 Todas las Sucursales Destino</option>
                @foreach($sucursales as $s)
                    <option value="{{ $s->codigo }}">{{ $s->nombre }} ({{ $s->codigo }})</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Listado de transferencias en Hostinger --}}
    <div id="contenedorTarjetasNube">
        @if(empty($transferenciasNube))
            <div class="card" style="text-align: center; padding: 3rem;" id="sinTransferenciasNube">
                <i class="fa-solid fa-cloud-arrow-up" style="font-size: 3rem; color: #94a3b8; margin-bottom: 1rem; opacity: 0.5;"></i>
                <h3 style="margin-bottom: 0.5rem;">Buzón en la Nube al Día</h3>
                <p style="color: var(--text-muted);">No hay paquetes pendientes de recepción alojados en el servidor cloud de Hostinger.</p>
                <div style="margin-top: 1rem; font-size: 0.82rem; color: #64748b;">
                    Las transferencias que envíes a otras sucursales se reflejarán aquí hasta que sean recibidas en destino.
                </div>
            </div>
        @else
            @foreach($transferenciasNube as $trn)
            @php
                $payloadItems = is_array($trn['payload']) ? ($trn['payload']['items'] ?? $trn['payload']) : [];
                $totalCostoTrn = 0;
                $totalPesoTrn = 0;
                $totalUndTrn = 0;
                if (is_array($payloadItems)) {
                    foreach ($payloadItems as $it) {
                        $totalCostoTrn += (float) ($it['subtotal_costo'] ?? (($it['cantidad_enviada'] ?? $it['cantidad'] ?? 0) * ($it['costo_unitario'] ?? $it['costo'] ?? 0)));
                        if (($it['tipo_articulo'] ?? 'pesable') === 'unidad') {
                            $totalUndTrn += (int) ($it['cantidad_enviada'] ?? $it['cantidad'] ?? 0);
                        } else {
                            $totalPesoTrn += (float) ($it['cantidad_enviada'] ?? $it['cantidad'] ?? 0);
                        }
                    }
                }
            @endphp
            <div class="card item-tarjeta-nube" data-destino="{{ $trn['sucursal_destino'] ?? '' }}" style="margin-bottom: 1rem; padding: 1.25rem 1.5rem; transition: box-shadow 0.2s;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                            <span style="font-size: 1.05rem; font-weight: 800; color: var(--text-main); font-family: monospace;">
                                <i class="fa-solid fa-cloud" style="color: #0284c7;"></i> {{ $trn['folio'] }}
                            </span>
                            <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 700; font-size: 0.75rem;">
                                <i class="fa-solid fa-clock"></i> Pendiente en Nube
                            </span>
                            @if(($trn['sucursal_origen'] ?? '') === ($sucursalActual->codigo ?? ''))
                                <span class="badge" style="background: #e0e7ff; color: #4338ca; font-weight: 700; font-size: 0.72rem;">
                                    Enviado por este equipo
                                </span>
                            @endif
                        </div>
                        
                        <div style="display: flex; gap: 1rem; align-items: center; margin-top: 0.5rem; font-size: 0.85rem; color: #475569; flex-wrap: wrap;">
                            <div>
                                <span style="color: var(--text-muted);">Ruta:</span>
                                <strong>{{ $trn['sucursal_origen_nombre'] ?? $trn['sucursal_origen'] }}</strong>
                                <i class="fa-solid fa-arrow-right" style="color: var(--accent); font-size: 0.75rem; margin: 0 4px;"></i>
                                <strong style="color: #0369a1;">{{ $trn['sucursal_destino_nombre'] ?? $trn['sucursal_destino'] }}</strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted);"><i class="fa-regular fa-calendar"></i> Subido:</span>
                                <b>{{ $trn['created_at'] ?? '—' }}</b>
                            </div>
                            @if(!empty($trn['usuario_envio']))
                            <div>
                                <span style="color: var(--text-muted);"><i class="fa-regular fa-user"></i> Remitente:</span>
                                <b>{{ $trn['usuario_envio'] }}</b>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                        <div style="text-align: right;">
                            <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Valorizado en Nube</div>
                            <div style="font-size: 1.15rem; font-weight: 800; color: var(--accent);">${{ number_format($totalCostoTrn, 2) }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                {{ count($payloadItems) }} ítem(s) {{ $totalPesoTrn > 0 ? '• ' . number_format($totalPesoTrn, 3) . ' LB' : '' }}
                            </div>
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            <button type="button" class="btn-modern btn-secondary" style="padding: 0.45rem 0.85rem; font-size: 0.82rem; font-weight: 700;"
                                    onclick='abrirModalDetalleCloud(@json($trn))'>
                                <i class="fa-solid fa-eye"></i> Ver Contenido
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════════════════ --}}
{{--  MODALES DEL SISTEMA DE TRANSFERENCIAS                       --}}
{{-- ═══════════════════════════════════════════════════════════ --}}

{{-- 1. Modal de Selección de Lotes para el Artículo --}}
<div id="modalSeleccionarLote" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 99999; justify-content: center; align-items: center; padding: 1rem;">
    <div class="modal-card" style="background: #ffffff; border-radius: 16px; max-width: 850px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; animation: modalFadeIn 0.2s ease-out;">
        {{-- Cabecera del modal --}}
        <div style="padding: 1.25rem 1.5rem; background: linear-gradient(135deg, #1e293b, #0f172a); color: #ffffff; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: #fbbf24;">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #ffffff;">Seleccionar Lote a Transferir</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.78rem; color: #94a3b8;">Filtre o escanee el código de proveedor / lote que desea enviar</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalLotes()" style="background: none; border: none; color: #94a3b8; font-size: 1.6rem; cursor: pointer; padding: 4px; line-height: 1; transition: color 0.15s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">&times;</button>
        </div>
        
        <div style="padding: 1.25rem 1.5rem;">
            {{-- Info del producto --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.85rem 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);" id="modalLoteArticuloNombre">—</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 3px; display: flex; gap: 0.75rem; flex-wrap: wrap;" id="modalLoteArticuloCodigos">—</div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Stock Total Disponible</div>
                    <div style="font-size: 1.2rem; font-weight: 800; color: #10b981;" id="modalLoteArticuloStockTotal">—</div>
                </div>
            </div>

            {{-- Buscador específico de lotes por código largo de proveedor --}}
            <div style="position: relative; margin-bottom: 1rem;">
                <input type="text" id="buscadorLoteModal" class="input-modern" placeholder="🔍 Escanee o escriba código largo de proveedor, lote o serie..." autocomplete="off" style="padding-left: 2.75rem; padding-right: 2.5rem; width: 100%; font-size: 0.9rem; height: 42px; border-radius: 8px; border: 1.5px solid var(--border-color); background: #ffffff; box-shadow: inset 0 1px 3px rgba(0,0,0,0.03);">
                <i class="fa-solid fa-barcode" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--accent); font-size: 1.1rem;"></i>
                <button type="button" id="btnLimpiarBuscadorLote" onclick="limpiarBuscadorLote()" style="display: none; position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 1rem; padding: 4px;">
                    <i class="fa-solid fa-circle-xmark"></i>
                </button>
            </div>

            {{-- Tabla de Lotes --}}
            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
                <table class="modern-table" style="font-size: 0.85rem; margin: 0; width: 100%;">
                    <thead>
                        <tr style="background: #f1f5f9; position: sticky; top: 0; z-index: 10;">
                            <th>Lote / Código Proveedor</th>
                            <th>Serie</th>
                            <th>Vencimiento</th>
                            <th style="text-align: right;">Stock Disp.</th>
                            <th style="text-align: right;">Costo</th>
                            <th style="text-align: center; width: 140px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="modalLoteTbody">
                        {{-- Inyectado dinámicamente --}}
                    </tbody>
                </table>
            </div>
        </div>

        <div style="padding: 0.85rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 0.78rem; color: var(--text-muted);">
                <i class="fa-solid fa-lightbulb" style="color: #f59e0b;"></i> Pulse <kbd style="background: #e2e8f0; padding: 1px 5px; border-radius: 4px; font-weight: 700;">Enter</kbd> para seleccionar el primer lote coincidente.
            </div>
            <button type="button" onclick="cerrarModalLotes()" class="btn-modern btn-secondary" style="font-size: 0.85rem; padding: 0.5rem 1.25rem;">
                Cerrar
            </button>
        </div>
    </div>
</div>

{{-- 2. Modal de Confirmación de Envío Personalizado del Sistema --}}
<div id="modalConfirmarEnvio" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 99999; justify-content: center; align-items: center; padding: 1rem;">
    <div class="modal-card" style="background: #ffffff; border-radius: 16px; max-width: 540px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; animation: modalFadeIn 0.2s ease-out;">
        {{-- Cabecera --}}
        <div style="padding: 1.25rem 1.5rem; background: linear-gradient(135deg, #1e293b, #0f172a); color: #ffffff; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(249, 115, 22, 0.2); border: 1px solid rgba(249, 115, 22, 0.4); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: #f97316;">
                    <i class="fa-solid fa-paper-plane"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #ffffff;">Confirmar Envío de Transferencia</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.78rem; color: #94a3b8;">Verifique los detalles antes de registrar la salida</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalConfirmarEnvio()" style="background: none; border: none; color: #94a3b8; font-size: 1.6rem; cursor: pointer; padding: 4px; line-height: 1; transition: color 0.15s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">&times;</button>
        </div>
        
        <div style="padding: 1.5rem;">
            {{-- Sucursal Destino --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1.25rem;">
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Sucursal Destino</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-top: 4px; display: flex; align-items: center; gap: 0.5rem;" id="confirmDestinoNombre">
                    <i class="fa-solid fa-store" style="color: var(--accent);"></i> <span>—</span>
                </div>
                <div id="confirmNotasPreview" style="font-size: 0.82rem; color: #64748b; margin-top: 0.5rem; display: none; padding-top: 0.5rem; border-top: 1px dashed #e2e8f0;">
                    <strong>Notas:</strong> <span id="confirmNotasTexto">—</span>
                </div>
            </div>

            {{-- Grid de Totales --}}
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                <div style="background: #f1f5f9; padding: 0.85rem 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Productos</div>
                    <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-top: 2px;" id="confirmTotalItems">0 artículos</div>
                </div>
                <div style="background: #f1f5f9; padding: 0.85rem 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Costo Valorizado</div>
                    <div style="font-size: 1.15rem; font-weight: 800; color: var(--accent); margin-top: 2px;" id="confirmTotalCosto">$0.00</div>
                </div>
            </div>

            {{-- Alerta de descuento inmediato --}}
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 0.85rem 1rem; display: flex; gap: 0.75rem; align-items: flex-start;">
                <i class="fa-solid fa-shield-halved" style="color: #d97706; font-size: 1.2rem; margin-top: 2px;"></i>
                <div style="font-size: 0.82rem; color: #92400e; line-height: 1.4;">
                    <strong>Descuento automático:</strong> El stock será rebajado inmediatamente del inventario local y la transferencia quedará en tránsito hacia la sucursal receptora.
                </div>
            </div>
        </div>

        <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" id="btnCancelarEnvio" onclick="cerrarModalConfirmarEnvio()" class="btn-modern btn-secondary" style="padding: 0.6rem 1.25rem;">
                Cancelar
            </button>
            <button type="button" id="btnEjecutarEnvioFinal" onclick="ejecutarEnvioFinal()" class="btn-modern btn-accent" style="padding: 0.6rem 1.75rem; font-weight: 700;">
                <i class="fa-solid fa-paper-plane"></i> Confirmar y Enviar
            </button>
        </div>
    </div>
</div>

{{-- 3. Modal de Resultado / Éxito Personalizado --}}
<div id="modalResultadoTransferencia" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 99999; justify-content: center; align-items: center; padding: 1rem;">
    <div class="modal-card" style="background: #ffffff; border-radius: 16px; max-width: 520px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; text-align: center; animation: modalFadeIn 0.2s ease-out;">
        <div style="padding: 2.25rem 1.5rem 1.5rem 1.5rem;">
            <div style="width: 68px; height: 68px; border-radius: 50%; background: #dcfce7; color: #16a34a; font-size: 2.2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem auto;">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.35rem; font-weight: 800; color: var(--text-main);">¡Transferencia Procesada!</h3>
            <div style="display: inline-block; background: #e0e7ff; color: #3730a3; font-weight: 800; font-size: 1.15rem; padding: 0.4rem 1.2rem; border-radius: 8px; margin-bottom: 0.85rem;" id="resultadoFolio">
                TRN-0000
            </div>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin: 0 0 1rem 0;" id="resultadoMensaje">
                La transferencia fue registrada exitosamente y el stock ha sido descontado.
            </p>
            
            <div id="resultadoSyncBadge" style="margin-bottom: 0.5rem; font-size: 0.85rem; padding: 0.5rem 1rem; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.5rem;"></div>
        </div>

        <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: center; gap: 0.75rem;">
            <button type="button" id="btnImprimirTicketResultado" class="btn-modern btn-secondary" style="padding: 0.65rem 1.25rem; font-weight: 700;">
                <i class="fa-solid fa-print"></i> Imprimir Ticket
            </button>
            <button type="button" onclick="location.reload()" class="btn-modern btn-accent" style="padding: 0.65rem 1.5rem; font-weight: 700;">
                <i class="fa-solid fa-check"></i> Finalizar
            </button>
        </div>
    </div>
</div>

{{-- 4. Modal de Confirmación de Acción Genérica (Recepción / Cancelación) --}}
<div id="modalConfirmarAccion" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 99999; justify-content: center; align-items: center; padding: 1rem;">
    <div class="modal-card" style="background: #ffffff; border-radius: 16px; max-width: 480px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; animation: modalFadeIn 0.2s ease-out;">
        <div style="padding: 1.75rem 1.5rem 1.25rem 1.5rem; text-align: center;">
            <div id="modalAccionIcono" style="width: 58px; height: 58px; border-radius: 50%; font-size: 1.75rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem auto;"></div>
            <h3 id="modalAccionTitulo" style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 800; color: var(--text-main);"></h3>
            <p id="modalAccionMensaje" style="color: #64748b; font-size: 0.9rem; margin: 0; line-height: 1.45;"></p>
        </div>
        <div style="padding: 1rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: center; gap: 0.75rem;">
            <button type="button" onclick="cerrarModalAccion()" class="btn-modern btn-secondary" style="padding: 0.6rem 1.25rem;">Cancelar</button>
            <button type="button" id="btnEjecutarAccion" class="btn-modern" style="padding: 0.6rem 1.5rem; font-weight: 700;"></button>
        </div>
    </div>
</div>

{{-- 5. Modal de Inspección de Transferencia en la Nube --}}
<div id="modalDetalleCloud" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 99999; justify-content: center; align-items: center; padding: 1rem;">
    <div class="modal-card" style="background: #ffffff; border-radius: 16px; max-width: 780px; width: 100%; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; animation: modalFadeIn 0.2s ease-out;">
        <div style="padding: 1.25rem 1.5rem; background: linear-gradient(135deg, #0369a1, #0f172a); color: #ffffff; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(255,255,255,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.15rem; color: #38bdf8;">
                    <i class="fa-solid fa-cloud"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 700; color: #ffffff;" id="modalCloudFolio">TRN-0000</h3>
                    <p style="margin: 2px 0 0 0; font-size: 0.78rem; color: #94a3b8;">Paquete de datos alojado en el Servidor de Hostinger</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalDetalleCloud()" style="background: none; border: none; color: #94a3b8; font-size: 1.6rem; cursor: pointer; padding: 4px; line-height: 1;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='#94a3b8'">&times;</button>
        </div>
        
        <div style="padding: 1.25rem 1.5rem;">
            {{-- Info del paquete --}}
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.85rem 1.25rem; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div style="font-size: 0.85rem; color: #334155;">
                    <div><strong>Origen:</strong> <span id="modalCloudOrigen">—</span> ➔ <strong>Destino:</strong> <span id="modalCloudDestino">—</span></div>
                    <div style="color: var(--text-muted); font-size: 0.78rem; margin-top: 3px;"><strong>Fecha en nube:</strong> <span id="modalCloudFecha">—</span></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Estado en Servidor</div>
                    <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 700; font-size: 0.8rem;" id="modalCloudEstado">Pendiente</span>
                </div>
            </div>

            {{-- Tabla de productos del paquete --}}
            <div style="max-height: 280px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
                <table class="modern-table" style="font-size: 0.85rem; margin: 0; width: 100%;">
                    <thead>
                        <tr style="background: #f1f5f9; position: sticky; top: 0; z-index: 10;">
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Lote / Serie</th>
                            <th>Vencimiento</th>
                            <th style="text-align: right;">Cantidad</th>
                            <th style="text-align: right;">Costo</th>
                        </tr>
                    </thead>
                    <tbody id="modalCloudTbody">
                        {{-- Inyectado dinámicamente --}}
                    </tbody>
                </table>
            </div>
        </div>

        <div style="padding: 0.85rem 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; text-align: right;">
            <button type="button" onclick="cerrarModalDetalleCloud()" class="btn-modern btn-secondary" style="font-size: 0.85rem; padding: 0.5rem 1.25rem;">
                Cerrar
            </button>
        </div>
    </div>
</div>

{{-- Contenedor de Notificaciones Toast Flotantes --}}
<div id="toastContainerTransferencias" style="position: fixed; top: 1.5rem; right: 1.5rem; z-index: 999999; display: flex; flex-direction: column; gap: 0.5rem; pointer-events: none;"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Datos de artículos y configuración ───
    const articulos = @json($articulos);
    const modoInventario = '{{ $modoInventario }}';
    const unidadPeso = '{{ strtoupper($settings["unidad_peso"] ?? "LB") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let productosEnvio = [];
    let isSubmittingTransfer = false; // Candado de seguridad anti-doble envío
    let currentModalArticulo = null;
    let filteredModalLotes = [];

    // ─── Toast / Notificaciones Flotantes del Sistema ───
    window.mostrarNotificacion = function(tipo, mensaje) {
        const container = document.getElementById('toastContainerTransferencias');
        if (!container) return;

        const toast = document.createElement('div');
        toast.style.pointerEvents = 'auto';
        toast.style.padding = '0.85rem 1.25rem';
        toast.style.borderRadius = '10px';
        toast.style.fontSize = '0.88rem';
        toast.style.fontWeight = '600';
        toast.style.boxShadow = '0 10px 25px -5px rgba(0,0,0,0.2)';
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.gap = '0.75rem';
        toast.style.minWidth = '280px';
        toast.style.maxWidth = '420px';
        toast.style.animation = 'modalFadeIn 0.25s ease-out';
        toast.style.transition = 'opacity 0.3s ease-out, transform 0.3s ease-out';

        let iconHtml = '<i class="fa-solid fa-circle-info"></i>';
        if (tipo === 'success') {
            toast.style.background = '#065f46';
            toast.style.color = '#ffffff';
            iconHtml = '<i class="fa-solid fa-circle-check" style="color: #34d399; font-size: 1.15rem;"></i>';
        } else if (tipo === 'error') {
            toast.style.background = '#991b1b';
            toast.style.color = '#ffffff';
            iconHtml = '<i class="fa-solid fa-circle-exclamation" style="color: #f87171; font-size: 1.15rem;"></i>';
        } else if (tipo === 'warning') {
            toast.style.background = '#92400e';
            toast.style.color = '#ffffff';
            iconHtml = '<i class="fa-solid fa-triangle-exclamation" style="color: #fbbf24; font-size: 1.15rem;"></i>';
        } else {
            toast.style.background = '#1e293b';
            toast.style.color = '#ffffff';
        }

        toast.innerHTML = `${iconHtml}<div style="flex: 1;">${mensaje}</div>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    };

    // ─── Buscador Principal Dinámico Multi-Criterio ───
    const buscador = document.getElementById('buscadorArticulo');
    const resultados = document.getElementById('resultadosBusqueda');
    const btnLimpiar = document.getElementById('btnLimpiarBuscador');
    let searchSelectedIndex = -1;
    let filteredArticulos = [];

    function normalizeText(str) {
        if (!str) return '';
        return String(str).toLowerCase().trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function highlightMatch(text, query) {
        if (!text || !query) return text || '';
        const strText = String(text);
        const normText = normalizeText(strText);
        const normQuery = normalizeText(query);
        const idx = normText.indexOf(normQuery);
        if (idx === -1) return strText;
        const originalMatch = strText.substr(idx, query.length);
        return strText.substring(0, idx) + `<span style="background: #fef08a; color: #854d0e; font-weight: 800; padding: 0 2px; border-radius: 3px;">${originalMatch}</span>` + strText.substring(idx + query.length);
    }

    window.limpiarBuscador = function() {
        if (buscador) {
            buscador.value = '';
            buscador.focus();
        }
        if (resultados) resultados.style.display = 'none';
        if (btnLimpiar) btnLimpiar.style.display = 'none';
        searchSelectedIndex = -1;
        filteredArticulos = [];
    };

    function ejecutarBusqueda() {
        if (!buscador || !resultados) return;
        const rawQuery = buscador.value.trim();
        const query = normalizeText(rawQuery);

        if (btnLimpiar) {
            btnLimpiar.style.display = rawQuery.length > 0 ? 'block' : 'none';
        }

        if (query.length === 0) {
            resultados.style.display = 'none';
            searchSelectedIndex = -1;
            filteredArticulos = [];
            return;
        }

        // 1. Extraer peso y SKU en caso de escaneo GS1 / balanza
        let parsedWeight = null;
        let searchSku = query;
        let gtinMatch = rawQuery.match(/(?:01)(\d{14})/);
        let weightMatch = rawQuery.match(/(?:320[0-5]|310[0-5])(\d{6})/);

        if (gtinMatch && weightMatch && rawQuery.length >= 24) {
            searchSku = normalizeText(gtinMatch[1]);
            const decimals = parseInt(weightMatch[0].charAt(3), 10);
            const rawWeight = parseInt(weightMatch[1], 10);
            parsedWeight = rawWeight / Math.pow(10, decimals);
        } else if ((rawQuery.length === 11 || rawQuery.length === 12) && /^\d+$/.test(rawQuery)) {
            searchSku = normalizeText(rawQuery.substring(0, 6));
        } else if (rawQuery.length === 13 && /^2\d{12}$/.test(rawQuery)) {
            searchSku = normalizeText(rawQuery.substring(1, 6));
        }

        const queryNumbersOnly = query.replace(/\D/g, '');

        // 2. Comprobar si hay coincidencia exacta de lote o código escaneado en Modo Dinámico (pistola directa)
        if (modoInventario === 'dinamico') {
            for (const art of articulos) {
                if (art.tipo_articulo !== 'unidad' && art.lotes && art.lotes.length > 0) {
                    const exactLot = art.lotes.find(l => 
                        (l.codigo_escaneado && l.codigo_escaneado === rawQuery) ||
                        (l.serie && l.serie === rawQuery) ||
                        (l.lote && l.lote === rawQuery)
                    );
                    if (exactLot && rawQuery.length >= 4) {
                        // Coincidencia exacta de lote escaneado -> agregar directamente
                        agregarProducto(art.id, exactLot.id, parsedWeight);
                        return;
                    }
                }
            }
        }

        // 3. Comprobar coincidencia EXACTA por código de barras de producto simple (ej: salsa 750100012345 con pistola)
        if (rawQuery.length >= 3) {
            const exactSimpleArt = articulos.find(a => 
                (a.tipo_articulo === 'unidad' || !a.lotes || a.lotes.length === 0) &&
                (a.codigo === rawQuery || a.codigo_cliente === rawQuery || a.item === rawQuery)
            );
            if (exactSimpleArt) {
                // Producto simple sin lotes escaneado directamente -> auto-agregar al envío
                agregarProducto(exactSimpleArt.id, null, parsedWeight);
                return;
            }
        }

        // 4. Filtrar artículos por múltiples criterios
        filteredArticulos = articulos.filter(a => {
            const desc = normalizeText(a.descripcion);
            const codProv = normalizeText(a.codigo);
            const codCli = normalizeText(a.codigo_cliente);
            const item = normalizeText(a.item);
            const fam = normalizeText(a.familia_nombre);

            // Coincidencia en texto
            const matchesText = desc.includes(query) || fam.includes(query);

            // Coincidencias en códigos
            const matchesCodProv = codProv.includes(query) || (queryNumbersOnly && codProv.replace(/\D/g, '').includes(queryNumbersOnly));
            const matchesCodCli = codCli.includes(query) || (queryNumbersOnly && codCli.replace(/\D/g, '').includes(queryNumbersOnly));
            const matchesItem = item.includes(query) || (queryNumbersOnly && item.replace(/\D/g, '').includes(queryNumbersOnly));
            const matchesSku = searchSku && (codProv.includes(searchSku) || codCli.includes(searchSku));

            // En modo dinámico, buscar también si el query coincide con algún lote
            let matchesLote = false;
            if (modoInventario === 'dinamico' && a.tipo_articulo !== 'unidad' && a.lotes && a.lotes.length > 0) {
                matchesLote = a.lotes.some(l => 
                    normalizeText(l.lote).includes(query) || 
                    normalizeText(l.serie).includes(query) ||
                    normalizeText(l.codigo_escaneado).includes(query)
                );
            }

            return matchesText || matchesCodProv || matchesCodCli || matchesItem || matchesSku || matchesLote;
        }).slice(0, 20);

        if (filteredArticulos.length === 0) {
            resultados.innerHTML = `
                <div style="padding: 1.5rem; text-align: center; color: var(--text-muted);">
                    <i class="fa-solid fa-box-open" style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #cbd5e1; display: block;"></i>
                    <span style="font-weight: 600;">No se encontraron artículos con stock para "<strong>${rawQuery}</strong>"</span>
                    <div style="font-size: 0.78rem; margin-top: 0.25rem;">Verifique que el artículo esté activo y tenga inventario disponible.</div>
                </div>
            `;
            searchSelectedIndex = -1;
        } else {
            searchSelectedIndex = 0;
            renderResultadosHTML(rawQuery);
        }
        resultados.style.display = 'block';
    }

    function renderResultadosHTML(rawQuery) {
        if (!resultados) return;

        // Renderizado limpio de artículos sin desplegar los lotes en la lista
        resultados.innerHTML = filteredArticulos.map((a, index) => {
            const esUnidad = a.tipo_articulo === 'unidad';
            const isSelected = index === searchSelectedIndex;
            const stockVal = parseFloat(a.stock);
            const stockText = stockVal.toFixed(esUnidad ? 0 : 3) + ' ' + (esUnidad ? 'UND' : unidadPeso);
            const stockColor = stockVal > 10 ? '#10b981' : (stockVal > 0 ? '#f59e0b' : '#ef4444');
            const bgStyle = isSelected ? 'background: #eff6ff; border-left: 4px solid var(--primary);' : 'background: #ffffff; border-left: 4px solid transparent;';
            const yaAgregado = productosEnvio.some(p => p.articulo_id === a.id);
            const hasLotes = modoInventario === 'dinamico' && a.lotes && a.lotes.length > 0;

            return `
                <div onclick="seleccionarArticulo(${a.id})" class="item-resultado-busqueda" data-index="${index}"
                     style="padding: 0.85rem 1.25rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; transition: all 0.15s; ${bgStyle}"
                     onmouseover="highlightItemByIndex(${index})">
                    <div style="flex: 1; padding-right: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <strong style="color: var(--text-main); font-size: 0.95rem;">${highlightMatch(a.descripcion, rawQuery)}</strong>
                            ${a.familia_nombre ? `<span style="font-size: 0.72rem; background: #f1f5f9; color: #475569; padding: 1px 6px; border-radius: 4px;"><i class="fa-solid fa-layer-group"></i> ${highlightMatch(a.familia_nombre, rawQuery)}</span>` : ''}
                            ${hasLotes ? `<span class="badge" style="background: #e0e7ff; color: #4338ca; font-weight: 700; font-size: 0.72rem; padding: 2px 7px; border-radius: 10px;"><i class="fa-solid fa-layer-group"></i> ${a.lotes.length} lote${a.lotes.length > 1 ? 's' : ''}</span>` : ''}
                            ${yaAgregado ? '<span style="font-size: 0.7rem; background: #dcfce7; color: #166534; padding: 1px 6px; border-radius: 10px; font-weight: 700;">✓ En lista</span>' : ''}
                        </div>
                        <div style="display: flex; gap: 0.85rem; align-items: center; margin-top: 0.3rem; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap;">
                            ${a.codigo ? `<span style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1px 6px; border-radius: 4px;"><i class="fa-solid fa-barcode"></i> Prov: <b>${highlightMatch(a.codigo, rawQuery)}</b></span>` : ''}
                            ${a.codigo_cliente ? `<span style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1px 6px; border-radius: 4px;"><i class="fa-solid fa-hashtag"></i> Cliente: <b>${highlightMatch(a.codigo_cliente, rawQuery)}</b></span>` : ''}
                            ${a.item ? `<span style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1px 6px; border-radius: 4px;">Item: <b>${highlightMatch(a.item, rawQuery)}</b></span>` : ''}
                        </div>
                    </div>
                    <div style="text-align: right; white-space: nowrap;">
                        <div style="font-size: 0.9rem; font-weight: 800; color: ${stockColor};">
                            Stock: ${stockText}
                        </div>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                            Costo: <b>$${parseFloat(a.precio_compra || 0).toFixed(2)}</b>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    window.highlightItemByIndex = function(index) {
        searchSelectedIndex = index;
        document.querySelectorAll('.item-resultado-busqueda').forEach((el, i) => {
            if (i === index) {
                el.style.background = '#eff6ff';
                el.style.borderLeftColor = 'var(--primary)';
            } else {
                el.style.background = '#ffffff';
                el.style.borderLeftColor = 'transparent';
            }
        });
    };

    // ─── Seleccionar Artículo desde la Búsqueda ───
    window.seleccionarArticulo = function(articuloId) {
        const art = articulos.find(a => a.id === articuloId);
        if (!art) return;

        if (modoInventario === 'dinamico' && art.tipo_articulo !== 'unidad' && art.lotes && art.lotes.length > 0) {
            // Abrir modal de selección de lotes
            abrirModalLotes(art);
            limpiarBuscador();
        } else {
            // Modo Simple o Producto Simple sin lotes (salsas, abarrotes) -> agregar directo al envío
            agregarProducto(art.id);
            limpiarBuscador();
        }
    };

    // ─── Modal de Selección de Lotes con Buscador de Código Largo ───
    const buscadorLoteInput = document.getElementById('buscadorLoteModal');
    const btnLimpiarBuscadorLote = document.getElementById('btnLimpiarBuscadorLote');

    window.abrirModalLotes = function(art) {
        const modal = document.getElementById('modalSeleccionarLote');
        const nombreEl = document.getElementById('modalLoteArticuloNombre');
        const codigosEl = document.getElementById('modalLoteArticuloCodigos');
        const stockTotalEl = document.getElementById('modalLoteArticuloStockTotal');

        if (!modal || !art) return;

        currentModalArticulo = art;
        const esUnidad = art.tipo_articulo === 'unidad';
        nombreEl.textContent = art.descripcion;
        
        let codigosHtml = '';
        if (art.codigo) codigosHtml += `<span><i class="fa-solid fa-barcode"></i> Prov: <b>${art.codigo}</b></span>`;
        if (art.codigo_cliente) codigosHtml += `<span><i class="fa-solid fa-hashtag"></i> Cliente: <b>${art.codigo_cliente}</b></span>`;
        if (art.familia_nombre) codigosHtml += `<span><i class="fa-solid fa-layer-group"></i> ${art.familia_nombre}</span>`;
        codigosEl.innerHTML = codigosHtml || '—';

        stockTotalEl.textContent = parseFloat(art.stock).toFixed(esUnidad ? 0 : 3) + ' ' + (esUnidad ? 'UND' : unidadPeso);

        // Limpiar buscador de lote
        if (buscadorLoteInput) {
            buscadorLoteInput.value = '';
            if (btnLimpiarBuscadorLote) btnLimpiarBuscadorLote.style.display = 'none';
        }

        renderModalLotesHTML('');
        modal.style.display = 'flex';

        // Auto-focus en el buscador de código de proveedor dentro del modal
        setTimeout(() => {
            if (buscadorLoteInput) buscadorLoteInput.focus();
        }, 150);
    };

    window.limpiarBuscadorLote = function() {
        if (buscadorLoteInput) {
            buscadorLoteInput.value = '';
            buscadorLoteInput.focus();
        }
        if (btnLimpiarBuscadorLote) btnLimpiarBuscadorLote.style.display = 'none';
        renderModalLotesHTML('');
    };

    function renderModalLotesHTML(rawQuery = '') {
        const tbody = document.getElementById('modalLoteTbody');
        if (!tbody || !currentModalArticulo || !currentModalArticulo.lotes) return;

        const art = currentModalArticulo;
        const esUnidad = art.tipo_articulo === 'unidad';
        const query = normalizeText(rawQuery);
        const queryNumbersOnly = query.replace(/\D/g, '');

        if (btnLimpiarBuscadorLote) {
            btnLimpiarBuscadorLote.style.display = rawQuery.trim().length > 0 ? 'block' : 'none';
        }

        // Filtrar específicamente por Código de Proveedor largo, Código Escaneado, Lote o Serie
        filteredModalLotes = art.lotes.filter(l => {
            if (!query) return true;
            const codEsc = normalizeText(l.codigo_escaneado);
            const loteNum = normalizeText(l.lote);
            const serieNum = normalizeText(l.serie);

            const matchCodEsc = codEsc.includes(query) || (queryNumbersOnly && codEsc.replace(/\D/g, '').includes(queryNumbersOnly));
            const matchLote = loteNum.includes(query) || (queryNumbersOnly && loteNum.replace(/\D/g, '').includes(queryNumbersOnly));
            const matchSerie = serieNum.includes(query) || (queryNumbersOnly && serieNum.replace(/\D/g, '').includes(queryNumbersOnly));

            return matchCodEsc || matchLote || matchSerie;
        });

        if (filteredModalLotes.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <i class="fa-solid fa-barcode" style="font-size: 2rem; color: #cbd5e1; margin-bottom: 0.5rem; display: block;"></i>
                        No se encontró ningún lote con el código de proveedor "<strong>${rawQuery}</strong>".
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = filteredModalLotes.map(l => {
            const lStock = parseFloat(l.cantidad_peso);
            const lStockText = lStock.toFixed(esUnidad ? 0 : 3) + ' ' + (esUnidad ? 'UND' : unidadPeso);
            const yaAgregadoLote = productosEnvio.some(p => p.articulo_id === art.id && p.compra_detalle_id === l.id);

            return `
                <tr style="background: ${yaAgregadoLote ? '#f0fdf4' : '#ffffff'};">
                    <td>
                        <div style="display: flex; flex-direction: column; gap: 3px;">
                            <div style="display: flex; align-items: center; gap: 0.4rem;">
                                <span class="badge" style="background: #e0e7ff; color: #4338ca; font-weight: 700; font-size: 0.8rem;">
                                    Lote: ${l.lote ? highlightMatch(l.lote, rawQuery) : 'S/N'}
                                </span>
                                ${yaAgregadoLote ? '<span style="font-size: 0.7rem; background: #dcfce7; color: #166534; padding: 1px 6px; border-radius: 4px; font-weight: 700;">✓ Agregado</span>' : ''}
                            </div>
                            ${l.codigo_escaneado ? `
                                <div style="font-size: 0.72rem; font-family: monospace; color: #64748b; word-break: break-all; margin-top: 2px;">
                                    <i class="fa-solid fa-barcode" style="color: var(--accent);"></i> ${highlightMatch(l.codigo_escaneado, rawQuery)}
                                </div>
                            ` : ''}
                        </div>
                    </td>
                    <td><span style="font-size: 0.82rem; color: var(--text-main); font-weight: 600;">${l.serie ? highlightMatch(l.serie, rawQuery) : '—'}</span></td>
                    <td>
                        <span style="font-size: 0.82rem; color: #475569;">
                            <i class="fa-regular fa-calendar" style="color: #94a3b8;"></i> ${l.fecha_vencimiento_format}
                        </span>
                    </td>
                    <td style="text-align: right; font-weight: 800; color: #10b981; font-size: 0.9rem;">
                        ${lStockText}
                    </td>
                    <td style="text-align: right; font-size: 0.85rem; font-weight: 600; color: var(--text-main);">
                        $${parseFloat(l.costo_unitario || 0).toFixed(2)}
                    </td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-modern btn-accent" style="padding: 0.35rem 0.85rem; font-size: 0.8rem; font-weight: 700; border-radius: 6px;"
                                onclick="agregarProducto(${art.id}, ${l.id}); cerrarModalLotes();">
                            <i class="fa-solid fa-plus"></i> ${yaAgregadoLote ? 'Agregar Otro' : 'Transferir Lote'}
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    if (buscadorLoteInput) {
        buscadorLoteInput.addEventListener('input', function() {
            renderModalLotesHTML(this.value.trim());
        });

        buscadorLoteInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (filteredModalLotes && filteredModalLotes.length > 0 && currentModalArticulo) {
                    agregarProducto(currentModalArticulo.id, filteredModalLotes[0].id);
                    cerrarModalLotes();
                }
            } else if (e.key === 'Escape') {
                cerrarModalLotes();
            }
        });
    }

    window.cerrarModalLotes = function() {
        const modal = document.getElementById('modalSeleccionarLote');
        if (modal) modal.style.display = 'none';
        currentModalArticulo = null;
        filteredModalLotes = [];
    };

    // ─── Control de Teclado y Clic Exterior para Modales ───
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modalLotes = document.getElementById('modalSeleccionarLote');
            if (modalLotes && modalLotes.style.display === 'flex') cerrarModalLotes();

            const modalConfirm = document.getElementById('modalConfirmarEnvio');
            if (modalConfirm && modalConfirm.style.display === 'flex') cerrarModalConfirmarEnvio();

            const modalAccion = document.getElementById('modalConfirmarAccion');
            if (modalAccion && modalAccion.style.display === 'flex') cerrarModalAccion();
        }
    });

    const modalLote = document.getElementById('modalSeleccionarLote');
    if (modalLote) {
        modalLote.addEventListener('click', function(e) {
            if (e.target === modalLote) cerrarModalLotes();
        });
    }

    const modalConfirmEnvio = document.getElementById('modalConfirmarEnvio');
    if (modalConfirmEnvio) {
        modalConfirmEnvio.addEventListener('click', function(e) {
            if (e.target === modalConfirmEnvio && !isSubmittingTransfer) cerrarModalConfirmarEnvio();
        });
    }

    const modalAccionEl = document.getElementById('modalConfirmarAccion');
    if (modalAccionEl) {
        modalAccionEl.addEventListener('click', function(e) {
            if (e.target === modalAccionEl) cerrarModalAccion();
        });
    }

    if (buscador) {
        buscador.addEventListener('input', ejecutarBusqueda);
        buscador.addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                ejecutarBusqueda();
            }
        });

        // Navegación por teclado y selección con Enter
        buscador.addEventListener('keydown', function(e) {
            if (resultados.style.display === 'block' && filteredArticulos.length > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    searchSelectedIndex = (searchSelectedIndex + 1) % filteredArticulos.length;
                    highlightItemByIndex(searchSelectedIndex);
                    const selectedEl = resultados.children[searchSelectedIndex];
                    if (selectedEl) selectedEl.scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    searchSelectedIndex = (searchSelectedIndex - 1 + filteredArticulos.length) % filteredArticulos.length;
                    highlightItemByIndex(searchSelectedIndex);
                    const selectedEl = resultados.children[searchSelectedIndex];
                    if (selectedEl) selectedEl.scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (searchSelectedIndex >= 0 && searchSelectedIndex < filteredArticulos.length) {
                        seleccionarArticulo(filteredArticulos[searchSelectedIndex].id);
                    } else if (filteredArticulos.length > 0) {
                        seleccionarArticulo(filteredArticulos[0].id);
                    }
                } else if (e.key === 'Escape') {
                    limpiarBuscador();
                }
            } else if (e.key === 'Enter' && this.value.trim().length > 0) {
                e.preventDefault();
                const rawVal = this.value.trim();
                const directMatch = articulos.find(a => 
                    a.codigo === rawVal || a.codigo_cliente === rawVal || a.item === rawVal
                );
                if (directMatch) {
                    seleccionarArticulo(directMatch.id);
                } else {
                    ejecutarBusqueda();
                    if (filteredArticulos.length > 0) {
                        seleccionarArticulo(filteredArticulos[0].id);
                    }
                }
            }
        });

        document.addEventListener('click', function(e) {
            const container = document.getElementById('contenedorBuscadorEnvio');
            if (container && !container.contains(e.target)) {
                if (resultados) resultados.style.display = 'none';
            }
        });
    }

    // ─── Agregar Producto a la Tabla ───
    window.agregarProducto = function(articuloId, compraDetalleId = null, cantidadCustom = null) {
        const art = articulos.find(a => a.id === articuloId);
        if (!art) return;

        const esUnidad = art.tipo_articulo === 'unidad';

        if (modoInventario === 'dinamico' && !esUnidad && art.lotes && art.lotes.length > 0) {
            // Manejo en Modo Dinámico por Lotes (artículos pesables con lotes)
            let lote = null;
            if (compraDetalleId) {
                lote = art.lotes.find(l => l.id === compraDetalleId);
            } else {
                lote = art.lotes[0];
            }

            const stockDisp = lote ? parseFloat(lote.cantidad_peso) : parseFloat(art.stock);
            const costoUnit = lote ? parseFloat(lote.costo_unitario) : parseFloat(art.precio_compra || 0);
            const targetLotId = lote ? lote.id : null;

            const indexExistente = productosEnvio.findIndex(p => p.articulo_id === art.id && p.compra_detalle_id === targetLotId);

            if (indexExistente !== -1) {
                const prod = productosEnvio[indexExistente];
                const inc = cantidadCustom ? cantidadCustom : 1.000;
                prod.cantidad = Math.min(prod.stock, prod.cantidad + inc);
            } else {
                const initialQty = cantidadCustom 
                    ? Math.min(cantidadCustom, stockDisp) 
                    : Math.min(1.000, stockDisp);

                productosEnvio.push({
                    articulo_id: art.id,
                    compra_detalle_id: targetLotId,
                    lote: lote ? lote.lote : null,
                    serie: lote ? lote.serie : null,
                    fecha_vencimiento: lote ? lote.fecha_vencimiento_format : 'S/V',
                    codigo: art.codigo || art.codigo_cliente || '',
                    codigo_cliente: art.codigo_cliente || '',
                    descripcion: art.descripcion,
                    tipo_articulo: art.tipo_articulo || 'pesable',
                    stock: stockDisp,
                    cantidad: initialQty,
                    costo_unitario: costoUnit,
                });
            }
        } else {
            // Manejo para Artículos Simples (Unidad / Salsas / sin lotes) o Modo Simple
            const stockDisp = parseFloat(art.stock);
            const indexExistente = productosEnvio.findIndex(p => p.articulo_id === art.id);

            if (indexExistente !== -1) {
                const prod = productosEnvio[indexExistente];
                const inc = cantidadCustom ? cantidadCustom : (esUnidad ? 1 : 1.000);
                prod.cantidad = Math.min(prod.stock, prod.cantidad + inc);
            } else {
                const initialQty = cantidadCustom 
                    ? Math.min(cantidadCustom, stockDisp) 
                    : (esUnidad ? 1 : Math.min(1.000, stockDisp));

                productosEnvio.push({
                    articulo_id: art.id,
                    compra_detalle_id: null,
                    lote: null,
                    serie: null,
                    fecha_vencimiento: 'S/V',
                    codigo: art.codigo || art.codigo_cliente || '',
                    codigo_cliente: art.codigo_cliente || '',
                    descripcion: art.descripcion,
                    tipo_articulo: art.tipo_articulo || 'pesable',
                    stock: stockDisp,
                    cantidad: initialQty,
                    costo_unitario: parseFloat(art.precio_compra || 0),
                });
            }
        }

        renderTablaEnvio();
        limpiarBuscador();
        mostrarNotificacion('success', `Se agregó <strong>${art.descripcion}</strong> al envío.`);
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

        tbody.innerHTML = '';
        let totalPeso = 0, totalUnd = 0, totalCosto = 0;

        productosEnvio.forEach((p, idx) => {
            const esUnidad = p.tipo_articulo === 'unidad';
            const subtotal = p.cantidad * p.costo_unitario;
            if (esUnidad) totalUnd += parseInt(p.cantidad);
            else totalPeso += p.cantidad;
            totalCosto += subtotal;

            const tr = document.createElement('tr');
            tr.id = `fila-prod-${idx}`;
            if (modoInventario === 'dinamico') {
                tr.innerHTML = `
                    <td>${p.codigo || '—'}</td>
                    <td><strong style="color: var(--text-main);">${p.descripcion}</strong></td>
                    <td>
                        ${p.lote ? `<span class="badge" style="background: #e0e7ff; color: #4338ca; font-weight: 700; font-size: 0.75rem;">L: ${p.lote}</span>` : '<span style="color: var(--text-muted); font-size: 0.8rem;">—</span>'}
                        ${p.serie ? `<span style="font-size: 0.75rem; color: var(--text-muted); display: block; margin-top: 2px;">S: ${p.serie}</span>` : ''}
                    </td>
                    <td style="font-size: 0.82rem; font-weight: 600; color: var(--text-main);">${p.fecha_vencimiento || 'S/V'}</td>
                    <td><span style="font-size: 0.78rem; padding: 2px 8px; border-radius: 12px; background: ${esUnidad ? '#dbeafe; color: #1e40af' : '#fef3c7; color: #92400e'}; font-weight: 600;">${esUnidad ? 'Unidad' : 'Pesable'}</span></td>
                    <td style="font-weight: 700; color: #10b981;">${p.stock.toFixed(esUnidad ? 0 : 3)} ${esUnidad ? 'UND' : unidadPeso}</td>
                    <td>
                        <input type="number" value="${esUnidad ? parseInt(p.cantidad) : p.cantidad.toFixed(3)}"
                               min="${esUnidad ? 1 : 0.001}" max="${p.stock}" step="${esUnidad ? 1 : 0.001}"
                               class="input-modern" style="width: 110px; text-align: center; font-weight: 700;"
                               oninput="actualizarCantidad(${idx}, this.value, false)"
                               onchange="actualizarCantidad(${idx}, this.value, true)">
                    </td>
                    <td>$${p.costo_unitario.toFixed(2)}</td>
                    <td class="subtotal-cell" style="font-weight: 700; color: var(--text-main);">$${subtotal.toFixed(2)}</td>
                    <td style="text-align: center;"><button onclick="quitarProducto(${idx})" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.15rem; padding: 4px;" title="Quitar"><i class="fa-solid fa-circle-xmark"></i></button></td>
                `;
            } else {
                tr.innerHTML = `
                    <td>${p.codigo || '—'}</td>
                    <td><strong style="color: var(--text-main);">${p.descripcion}</strong></td>
                    <td><span style="font-size: 0.78rem; padding: 2px 8px; border-radius: 12px; background: ${esUnidad ? '#dbeafe; color: #1e40af' : '#fef3c7; color: #92400e'}; font-weight: 600;">${esUnidad ? 'Unidad' : 'Pesable'}</span></td>
                    <td style="font-weight: 700; color: #10b981;">${p.stock.toFixed(esUnidad ? 0 : 3)} ${esUnidad ? 'UND' : unidadPeso}</td>
                    <td>
                        <input type="number" value="${esUnidad ? parseInt(p.cantidad) : p.cantidad.toFixed(3)}"
                               min="${esUnidad ? 1 : 0.001}" max="${p.stock}" step="${esUnidad ? 1 : 0.001}"
                               class="input-modern" style="width: 110px; text-align: center; font-weight: 700;"
                               oninput="actualizarCantidad(${idx}, this.value, false)"
                               onchange="actualizarCantidad(${idx}, this.value, true)">
                    </td>
                    <td>$${p.costo_unitario.toFixed(2)}</td>
                    <td class="subtotal-cell" style="font-weight: 700; color: var(--text-main);">$${subtotal.toFixed(2)}</td>
                    <td style="text-align: center;"><button onclick="quitarProducto(${idx})" style="background: none; border: none; color: #ef4444; cursor: pointer; font-size: 1.15rem; padding: 4px;" title="Quitar"><i class="fa-solid fa-circle-xmark"></i></button></td>
                `;
            }
            tbody.appendChild(tr);
        });

        document.getElementById('totalPesoEnvio').textContent = totalPeso.toFixed(3) + ' ' + unidadPeso;
        document.getElementById('totalUnidadesEnvio').textContent = totalUnd;
        document.getElementById('totalCostoEnvio').textContent = '$' + totalCosto.toFixed(2);
    };

    window.actualizarCantidad = function(idx, val, fullRebuild = false) {
        const p = productosEnvio[idx];
        if (!p) return;
        let cantidad = parseFloat(val);
        if (isNaN(cantidad) || cantidad <= 0) cantidad = p.tipo_articulo === 'unidad' ? 1 : 0.001;
        if (cantidad > p.stock) {
            cantidad = p.stock;
            mostrarNotificacion('warning', `Cantidad ajustada al stock disponible máximo (${p.stock}).`);
        }
        p.cantidad = cantidad;

        if (fullRebuild) {
            renderTablaEnvio();
        } else {
            // Actualización reactiva instantánea en DOM sin perder foco del input
            const fila = document.getElementById(`fila-prod-${idx}`);
            if (fila) {
                const subtotalEl = fila.querySelector('.subtotal-cell');
                if (subtotalEl) subtotalEl.textContent = '$' + (p.cantidad * p.costo_unitario).toFixed(2);
            }
            let totalPeso = 0, totalUnd = 0, totalCosto = 0;
            productosEnvio.forEach(prod => {
                const sub = prod.cantidad * prod.costo_unitario;
                if (prod.tipo_articulo === 'unidad') totalUnd += parseInt(prod.cantidad);
                else totalPeso += prod.cantidad;
                totalCosto += sub;
            });
            document.getElementById('totalPesoEnvio').textContent = totalPeso.toFixed(3) + ' ' + unidadPeso;
            document.getElementById('totalUnidadesEnvio').textContent = totalUnd;
            document.getElementById('totalCostoEnvio').textContent = '$' + totalCosto.toFixed(2);
        }
    };

    window.quitarProducto = function(idx) {
        const removed = productosEnvio.splice(idx, 1);
        renderTablaEnvio();
        if (removed && removed[0]) {
            mostrarNotificacion('info', `Se quitó <strong>${removed[0].descripcion}</strong> de la lista.`);
        }
    };

    // ─── Modal de Confirmación y Procesar Envío con Candado Anti-Doble Clic ───
    window.procesarEnvio = function() {
        const destinoSelect = document.getElementById('selectDestinoId');
        const destinoId = destinoSelect?.value;
        const notas = document.getElementById('inputNotas')?.value || '';

        if (!destinoId) {
            mostrarNotificacion('error', 'Seleccione la sucursal destino para continuar.');
            destinoSelect?.focus();
            return;
        }
        if (productosEnvio.length === 0) {
            mostrarNotificacion('error', 'Agregue al menos un producto a la lista de envío.');
            buscador?.focus();
            return;
        }

        // Llenar datos en el Modal de Confirmación
        const modal = document.getElementById('modalConfirmarEnvio');
        const destinoNombreEl = document.getElementById('confirmDestinoNombre');
        const totalItemsEl = document.getElementById('confirmTotalItems');
        const totalCostoEl = document.getElementById('confirmTotalCosto');
        const notasPreviewEl = document.getElementById('confirmNotasPreview');
        const notasTextoEl = document.getElementById('confirmNotasTexto');

        const destinoTexto = destinoSelect.options[destinoSelect.selectedIndex]?.text || 'Sucursal Destino';
        if (destinoNombreEl) destinoNombreEl.innerHTML = `<i class="fa-solid fa-store" style="color: var(--accent);"></i> <span>${destinoTexto}</span>`;

        let totalCosto = 0;
        productosEnvio.forEach(p => totalCosto += (p.cantidad * p.costo_unitario));

        if (totalItemsEl) totalItemsEl.textContent = `${productosEnvio.length} producto${productosEnvio.length > 1 ? 's' : ''}`;
        if (totalCostoEl) totalCostoEl.textContent = '$' + totalCosto.toFixed(2);

        if (notas.trim().length > 0) {
            if (notasTextoEl) notasTextoEl.textContent = notas.trim();
            if (notasPreviewEl) notasPreviewEl.style.display = 'block';
        } else {
            if (notasPreviewEl) notasPreviewEl.style.display = 'none';
        }

        modal.style.display = 'flex';
    };

    window.cerrarModalConfirmarEnvio = function() {
        if (isSubmittingTransfer) return;
        const modal = document.getElementById('modalConfirmarEnvio');
        if (modal) modal.style.display = 'none';
    };

    window.ejecutarEnvioFinal = function() {
        if (isSubmittingTransfer) return; // CANDADO DE SEGURIDAD CONTRA DOBLE ENVÍO
        isSubmittingTransfer = true;

        const destinoId = document.getElementById('selectDestinoId')?.value;
        const notas = document.getElementById('inputNotas')?.value || '';
        const btnEjecutar = document.getElementById('btnEjecutarEnvioFinal');
        const btnCancelar = document.getElementById('btnCancelarEnvio');

        if (btnEjecutar) {
            btnEjecutar.disabled = true;
            btnEjecutar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando Envío Seguro...';
        }
        if (btnCancelar) btnCancelar.disabled = true;

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
                // Cerrar modal de confirmación
                cerrarModalConfirmarEnvio();

                // Abrir Modal de Éxito
                const modalRes = document.getElementById('modalResultadoTransferencia');
                const folioEl = document.getElementById('resultadoFolio');
                const msgEl = document.getElementById('resultadoMensaje');
                const badgeEl = document.getElementById('resultadoSyncBadge');
                const btnTicket = document.getElementById('btnImprimirTicketResultado');

                if (folioEl) folioEl.textContent = 'TRN-' + (data.folio || (data.transferencia_id ? String(data.transferencia_id).padStart(4, '0') : ''));
                if (msgEl) msgEl.textContent = data.message || 'La transferencia fue registrada exitosamente.';

                if (badgeEl) {
                    if (data.sync && data.sync.success) {
                        badgeEl.style.background = '#dcfce7';
                        badgeEl.style.color = '#166534';
                        badgeEl.innerHTML = '<i class="fa-solid fa-cloud-check"></i> Sincronizado con la nube exitosamente.';
                    } else if (data.sync && !data.sync.success) {
                        badgeEl.style.background = '#fef3c7';
                        badgeEl.style.color = '#92400e';
                        badgeEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Guardado localmente (Offline).';
                    } else {
                        badgeEl.style.display = 'none';
                    }
                }

                if (btnTicket && data.transferencia_id) {
                    btnTicket.onclick = () => window.open('/transferencias/' + data.transferencia_id + '/ticket', '_blank', 'width=350,height=600');
                }

                if (modalRes) modalRes.style.display = 'flex';
            } else {
                isSubmittingTransfer = false;
                if (btnEjecutar) {
                    btnEjecutar.disabled = false;
                    btnEjecutar.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Confirmar y Enviar';
                }
                if (btnCancelar) btnCancelar.disabled = false;
                mostrarNotificacion('error', 'Error: ' + (data.error || 'No se pudo procesar la transferencia.'));
            }
        })
        .catch(err => {
            isSubmittingTransfer = false;
            if (btnEjecutar) {
                btnEjecutar.disabled = false;
                btnEjecutar.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Confirmar y Enviar';
            }
            if (btnCancelar) btnCancelar.disabled = false;
            mostrarNotificacion('error', 'Error de red o comunicación: ' + err.message);
        });
    };

    // ─── Modal Genérico de Acciones (Recepción / Cancelación) ───
    let accionPendiente = null;

    window.abrirModalAccion = function(tipo, titulo, mensaje, textoBoton, callback) {
        const modal = document.getElementById('modalConfirmarAccion');
        const iconEl = document.getElementById('modalAccionIcono');
        const titEl = document.getElementById('modalAccionTitulo');
        const msgEl = document.getElementById('modalAccionMensaje');
        const btnEl = document.getElementById('btnEjecutarAccion');

        if (!modal) return;

        titEl.textContent = titulo;
        msgEl.innerHTML = mensaje;

        if (tipo === 'success' || tipo === 'recibir') {
            iconEl.style.background = '#dcfce7';
            iconEl.style.color = '#16a34a';
            iconEl.innerHTML = '<i class="fa-solid fa-inbox"></i>';
            btnEl.className = 'btn-modern btn-accent';
            btnEl.innerHTML = `<i class="fa-solid fa-check"></i> ${textoBoton}`;
        } else if (tipo === 'danger' || tipo === 'cancelar') {
            iconEl.style.background = '#fee2e2';
            iconEl.style.color = '#dc2626';
            iconEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
            btnEl.className = 'btn-modern';
            btnEl.style.background = '#dc2626';
            btnEl.style.borderColor = '#dc2626';
            btnEl.style.color = '#ffffff';
            btnEl.innerHTML = `<i class="fa-solid fa-ban"></i> ${textoBoton}`;
        }

        accionPendiente = callback;
        btnEl.onclick = function() {
            btnEl.disabled = true;
            btnEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
            if (accionPendiente) accionPendiente();
        };

        modal.style.display = 'flex';
    };

    window.cerrarModalAccion = function() {
        const modal = document.getElementById('modalConfirmarAccion');
        if (modal) modal.style.display = 'none';
        accionPendiente = null;
    };

    // ─── Confirmar Recepción (Modal Personalizado) ───
    window.confirmarRecepcion = function(id, folio) {
        abrirModalAccion(
            'recibir',
            'Confirmar Recepción de Mercancía',
            `¿Desea confirmar la entrada de la transferencia <strong>${folio}</strong>?<br><br>El stock se sumará de inmediato al inventario local de esta sucursal.`,
            'Confirmar Entrada',
            function() {
                fetch(`/transferencias/${id}/recibir`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({}),
                })
                .then(r => r.json())
                .then(data => {
                    cerrarModalAccion();
                    if (data.success) {
                        mostrarNotificacion('success', data.message || 'Transferencia recibida con éxito.');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        mostrarNotificacion('error', data.error || 'No se pudo confirmar la recepción.');
                    }
                })
                .catch(err => {
                    cerrarModalAccion();
                    mostrarNotificacion('error', 'Error de comunicación: ' + err.message);
                });
            }
        );
    };

    // ─── Cancelar Transferencia (Modal Personalizado) ───
    window.cancelarTransferencia = function(id, folio) {
        abrirModalAccion(
            'cancelar',
            'Cancelar Transferencia',
            `¿Está seguro de anular la transferencia <strong>${folio}</strong>?<br><br>El inventario será restaurado al almacén de origen.`,
            'Sí, Cancelar Transferencia',
            function() {
                fetch(`/transferencias/${id}/cancelar`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                })
                .then(r => r.json())
                .then(data => {
                    cerrarModalAccion();
                    if (data.success) {
                        mostrarNotificacion('success', data.message || 'Transferencia cancelada.');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        mostrarNotificacion('error', data.error || 'No se pudo cancelar.');
                    }
                })
                .catch(err => {
                    cerrarModalAccion();
                    mostrarNotificacion('error', 'Error de comunicación: ' + err.message);
                });
            }
        );
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
                const transferencias = data.cloud.transferencias;
                let importadas = 0;

                const importarSiguiente = (idx) => {
                    if (idx >= transferencias.length) {
                        estadoDiv.style.display = 'block';
                        estadoDiv.style.background = '#dcfce7';
                        estadoDiv.style.color = '#166534';
                        estadoDiv.innerHTML = `<i class="fa-solid fa-circle-check"></i> Se importaron ${importadas} transferencia(s) desde la nube.`;
                        mostrarNotificacion('success', `Se sincronizaron ${importadas} transferencia(s) desde la nube.`);
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
                mostrarNotificacion('info', 'No hay transferencias pendientes en la nube.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cloud-arrow-down"></i> Sincronizar con Nube';
            estadoDiv.style.display = 'block';
            estadoDiv.style.background = '#fee2e2';
            estadoDiv.style.color = '#991b1b';
            estadoDiv.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Error al conectar con la nube: ' + err.message;
            mostrarNotificacion('error', 'Error al sincronizar con la nube: ' + err.message);
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
                mostrarNotificacion('success', data.message || 'Archivo .TRN importado exitosamente.');
                setTimeout(() => location.reload(), 1200);
            } else {
                mostrarNotificacion('error', data.error || 'Archivo .TRN inválido.');
            }
        })
        .catch(err => mostrarNotificacion('error', 'Error al leer el archivo: ' + err.message));

        input.value = '';
    };

    // ─── Ver Detalle de Transferencia Pendiente ───
    window.verDetalleTransferencia = function(id) {
        const detalle = document.getElementById('detalle-' + id);
        if (detalle) {
            detalle.style.display = detalle.style.display === 'none' ? '' : 'none';
        }
    };

    // ─── Modal de Inspección de Paquete Cloud (Hostinger) ───
    window.abrirModalDetalleCloud = function(trn) {
        const modal = document.getElementById('modalDetalleCloud');
        const folioEl = document.getElementById('modalCloudFolio');
        const origenEl = document.getElementById('modalCloudOrigen');
        const destinoEl = document.getElementById('modalCloudDestino');
        const fechaEl = document.getElementById('modalCloudFecha');
        const estadoEl = document.getElementById('modalCloudEstado');
        const tbody = document.getElementById('modalCloudTbody');

        if (!modal || !trn) return;

        folioEl.textContent = trn.folio || 'TRN-0000';
        origenEl.textContent = trn.sucursal_origen_nombre || trn.sucursal_origen || 'Origen';
        destinoEl.textContent = trn.sucursal_destino_nombre || trn.sucursal_destino || 'Destino';
        fechaEl.textContent = trn.created_at || '—';
        estadoEl.textContent = (trn.estado === 'pendiente' ? '🟡 Pendiente en Nube (En Tránsito)' : '🟢 Recibida');

        const items = Array.isArray(trn.payload) ? trn.payload : (trn.payload?.items || []);

        if (!items || items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:1.5rem; color:var(--text-muted);">Sin detalles de productos</td></tr>';
        } else {
            tbody.innerHTML = items.map(it => {
                const esUnidad = (it.tipo_articulo || 'pesable') === 'unidad';
                const cant = parseFloat(it.cantidad_enviada || it.cantidad || 0);
                const costo = parseFloat(it.costo_unitario || it.costo || 0);
                const subtotal = parseFloat(it.subtotal_costo || (cant * costo));
                const lote = it.lote ? `<span class="badge" style="background:#e0e7ff; color:#4338ca; font-weight:700; font-size:0.75rem;">L: ${it.lote}</span>` : '—';
                const serie = it.numero_lote || it.serie ? `<span style="font-size:0.75rem; color:var(--text-muted); display:block;">S: ${it.numero_lote || it.serie}</span>` : '';
                const venc = it.fecha_vencimiento_lote || it.fecha_vencimiento || 'S/V';

                return `
                    <tr>
                        <td><code>${it.codigo || '—'}</code></td>
                        <td><strong>${it.descripcion || 'Producto'}</strong></td>
                        <td>${lote}${serie}</td>
                        <td style="font-size:0.82rem; color:#475569;">${venc}</td>
                        <td style="text-align:right; font-weight:800; color:#10b981;">
                            ${cant.toFixed(esUnidad ? 0 : 3)} ${it.unidad_medida || (esUnidad ? 'UND' : unidadPeso)}
                        </td>
                        <td style="text-align:right; font-weight:700;">$${subtotal.toFixed(2)}</td>
                    </tr>
                `;
            }).join('');
        }

        modal.style.display = 'flex';
    };

    window.cerrarModalDetalleCloud = function() {
        const modal = document.getElementById('modalDetalleCloud');
        if (modal) modal.style.display = 'none';
    };

    // ─── Filtrar Transferencias por Destino en Pestaña Nube ───
    window.filtrarTransferenciasNube = function() {
        const select = document.getElementById('filtroDestinoNube');
        if (!select) return;
        const val = select.value;
        const items = document.querySelectorAll('.item-tarjeta-nube');
        let count = 0;

        items.forEach(it => {
            const dest = it.getAttribute('data-destino');
            if (val === 'todos' || dest === val) {
                it.style.display = 'block';
                count++;
            } else {
                it.style.display = 'none';
            }
        });

        const contadorHeader = document.getElementById('contadorNubeHeader');
        if (contadorHeader) contadorHeader.textContent = count;
    };

    // ─── Refrescar Buzón en la Nube ───
    window.refrescarBuzonNube = function() {
        const btn = document.getElementById('btnRefrescarBuzonNube');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-rotate fa-spin"></i> Consultando Nube...';
        }

        fetch('{{ route("transferencias.api.consultar-todas-nube") }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
        })
        .then(r => r.json())
        .then(data => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Actualizar Buzón Cloud';
            }
            if (data.success) {
                mostrarNotificacion('success', `Buzón Cloud actualizado: ${data.count} transferencia(s) en la nube.`);
                setTimeout(() => location.reload(), 800);
            } else {
                mostrarNotificacion('error', 'Error al consultar la nube: ' + (data.error || 'Desconocido'));
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-rotate"></i> Actualizar Buzón Cloud';
            }
            mostrarNotificacion('error', 'Error de conexión: ' + err.message);
        });
    };
});
</script>
@endpush
