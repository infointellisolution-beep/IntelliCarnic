@extends('layouts.app')

@section('title', 'Expediente: ' . $proveedor->nombre . ' — IntelliCarnic')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">

    <!-- BARRA NAVEGACIÓN SUPERIOR -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <a href="{{ route('proveedores.index') }}" class="btn-modern btn-secondary" style="padding: 0.5rem 0.85rem; text-decoration: none;">
                <i class="fa-solid fa-arrow-left"></i> Volver
            </a>
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    Expediente del Proveedor
                </h1>
                <div style="font-size: 0.88rem; color: var(--text-muted);">Historial de compras, recepciones y artículos suministrados</div>
            </div>
        </div>

        <div>
            <button type="button" class="btn-modern btn-primary" style="width: auto; padding: 0.55rem 1.1rem;" onclick="openEditarProveedor({{ json_encode($proveedor) }})">
                <i class="fa-solid fa-pen"></i> Editar Proveedor
            </button>
        </div>
    </div>

    <!-- ALERTAS -->
    @if(session('success'))
        <div class="card" style="border-left: 4px solid #10b981; background: #f0fdf4; color: #166534; padding: 1rem 1.25rem;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    <!-- PERFIL Y TARJETAS KPI RESUMEN -->
    <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 1.25rem;">
        
        <!-- Tarjeta Perfil -->
        <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span class="badge {{ $proveedor->estado === 'activo' ? 'badge-success' : 'badge-danger' }}" style="font-weight: 700;">
                        {{ $proveedor->estado === 'activo' ? '🟢 Activo' : '🔴 Inactivo' }}
                    </span>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">ID: #PROV-{{ str_pad($proveedor->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                
                <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--primary); margin: 0 0 0.5rem;">
                    {{ $proveedor->nombre }}
                </h2>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1rem; font-size: 0.88rem; color: var(--text-main); margin-top: 0.75rem;">
                    <div><strong>Contacto:</strong> {{ $proveedor->contacto_nombre ?: '-' }}</div>
                    <div><strong>RUC/Identificación:</strong> <span style="font-family: monospace;">{{ $proveedor->identificacion ?: '-' }}</span></div>
                    <div><strong>Teléfono:</strong> {{ $proveedor->telefono ?: '-' }}</div>
                    <div><strong>Correo:</strong> {{ $proveedor->correo ?: '-' }}</div>
                </div>

                @if($proveedor->direccion)
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.65rem;">
                        <i class="fa-solid fa-location-dot"></i> {{ $proveedor->direccion }}
                    </div>
                @endif

                @if($proveedor->notas)
                    <div style="margin-top: 0.75rem; padding: 0.65rem; background: #fff7ed; border-radius: 8px; border: 1px solid #ffedd5; font-size: 0.83rem; color: #9a3412;">
                        <strong>Notas:</strong> {{ $proveedor->notas }}
                    </div>
                @endif
            </div>
        </div>

        <!-- KPI: Total Invertido -->
        <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--accent); display: flex; flex-direction: column; justify-content: center;">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--accent); text-transform: uppercase;">Total Invertido</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent); margin-top: 0.25rem;">
                ${{ number_format($totalInvertido, 2) }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Suma de todas las recepciones</div>
        </div>

        <!-- KPI: Compras Realizadas -->
        <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--primary); display: flex; flex-direction: column; justify-content: center;">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary); text-transform: uppercase;">Compras Realizadas</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">
                {{ $totalComprasCount }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Facturas de recepción</div>
        </div>

        <!-- KPI: Promedio por Compra -->
        <div class="card" style="padding: 1.25rem; border-left: 4px solid #10b981; display: flex; flex-direction: column; justify-content: center;">
            <div style="font-size: 0.8rem; font-weight: 700; color: #10b981; text-transform: uppercase;">Promedio por Compra</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">
                ${{ number_format($promedioCompra, 2) }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                Última: {{ $ultimaCompra ? $ultimaCompra->fecha_compra->format('d/m/Y') : 'N/A' }}
            </div>
        </div>

    </div>

    <!-- SECCIONES DE HISTORIAL (TABLA CON NAVEGACIÓN POR PESTAÑAS SIMPLES) -->
    <div class="card" style="padding: 1.5rem;">
        
        <!-- Headers de Pestañas -->
        <div style="display: flex; gap: 1rem; border-bottom: 2px solid var(--border-color); margin-bottom: 1.5rem;">
            <button type="button" id="tab-btn-compras" onclick="switchProvTab('compras')" style="padding: 0.65rem 1.25rem; font-weight: 700; font-size: 0.95rem; border: none; background: none; border-bottom: 3px solid var(--accent); color: var(--accent); cursor: pointer;">
                <i class="fa-solid fa-boxes-packing"></i> Historial de Compras ({{ $totalComprasCount }})
            </button>
            <button type="button" id="tab-btn-productos" onclick="switchProvTab('productos')" style="padding: 0.65rem 1.25rem; font-weight: 700; font-size: 0.95rem; border: none; background: none; border-bottom: 3px solid transparent; color: var(--text-muted); cursor: pointer;">
                <i class="fa-solid fa-box"></i> Artículos Suministrados ({{ $articulosAdquiridos->count() }})
            </button>
        </div>

        <!-- CONTENIDO PESTAÑA 1: HISTORIAL DE COMPRAS -->
        <div id="prov-tab-compras" style="display: block;">
            <div style="overflow-x: auto;">
                <table class="table-modern" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>N° Factura / Documento</th>
                            <th>Fecha de Recepción</th>
                            <th style="text-align: center;">Ítems/Cajas</th>
                            <th style="text-align: right;">Subtotal</th>
                            <th style="text-align: right;">Impuesto (IVA)</th>
                            <th style="text-align: right;">Total Invertido</th>
                            <th>Registrado Por</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proveedor->compras as $c)
                            <tr>
                                <td style="font-weight: 700; font-family: monospace; color: var(--accent);">
                                    {{ $c->numero_factura ?: 'SIN FACTURA (#'.$c->id.')' }}
                                </td>
                                <td>{{ $c->fecha_compra ? $c->fecha_compra->format('d/m/Y h:i A') : 'N/A' }}</td>
                                <td style="text-align: center;">
                                    <span class="badge badge-info">{{ $c->detalles->count() }} artículo(s)</span>
                                </td>
                                <td style="text-align: right;">${{ number_format($c->subtotal, 2) }}</td>
                                <td style="text-align: right;">${{ number_format($c->iva, 2) }}</td>
                                <td style="text-align: right; font-weight: 800; color: var(--accent); font-size: 1.05rem;">
                                    ${{ number_format($c->total, 2) }}
                                </td>
                                <td style="font-size: 0.85rem; color: var(--text-muted);">
                                    {{ $c->user?->name ?: 'Sistema' }}
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn-modern btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.82rem;" onclick="abrirDocumentoCompra({{ json_encode($c) }})">
                                        <i class="fa-solid fa-receipt" style="color: var(--accent);"></i> Ver Documento
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    No hay compras o recepciones registradas para este proveedor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CONTENIDO PESTAÑA 2: ARTÍCULOS SUMINISTRADOS -->
        <div id="prov-tab-productos" style="display: none;">
            <div style="overflow-x: auto;">
                <table class="table-modern" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción del Producto</th>
                            <th style="text-align: right;">Precio de Venta</th>
                            <th style="text-align: center;">Volumen Adquirido</th>
                            <th style="text-align: right;">Monto Invertido ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articulosAdquiridos as $artItem)
                            @php
                                $art = $artItem->articulo;
                            @endphp
                            <tr>
                                <td style="font-family: monospace; font-weight: 700;">
                                    {{ $art?->codigo ?: 'S/C' }}
                                </td>
                                <td style="font-weight: 700;">
                                    {{ $art?->descripcion ?: 'Artículo Eliminado' }}
                                </td>
                                <td style="text-align: right;">${{ number_format($art?->pvp ?: ($art?->precio_sin_iva ?: 0), 2) }}</td>
                                <td style="text-align: center; font-weight: 700; color: var(--primary);">
                                    {{ number_format($artItem->total_peso, 2) }} UNID/LB
                                </td>
                                <td style="text-align: right; font-weight: 800; color: var(--accent); font-size: 1.05rem;">
                                    ${{ number_format($artItem->total_monto, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    No hay registro de artículos comprados a este proveedor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<!-- MODAL VER DOCUMENTO DE COMPRA -->
<div id="modal-documento-compra" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; max-width: 900px; width: 95%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color);">
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

@include('proveedores._modals')

@push('scripts')
<script>
function switchProvTab(tab) {
    const btnCompras = document.getElementById('tab-btn-compras');
    const btnProductos = document.getElementById('tab-btn-productos');
    const contentCompras = document.getElementById('prov-tab-compras');
    const contentProductos = document.getElementById('prov-tab-productos');

    if (tab === 'compras') {
        btnCompras.style.borderBottom = '3px solid var(--accent)';
        btnCompras.style.color = 'var(--accent)';
        btnProductos.style.borderBottom = '3px solid transparent';
        btnProductos.style.color = 'var(--text-muted)';
        contentCompras.style.display = 'block';
        contentProductos.style.display = 'none';
    } else {
        btnProductos.style.borderBottom = '3px solid var(--accent)';
        btnProductos.style.color = 'var(--accent)';
        btnCompras.style.borderBottom = '3px solid transparent';
        btnCompras.style.color = 'var(--text-muted)';
        contentProductos.style.display = 'block';
        contentCompras.style.display = 'none';
    }
}

function openModal(id) {
    const m = document.getElementById(id);
    if (m) m.style.display = 'flex';
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.style.display = 'none';
}

function openEditarProveedor(prov) {
    document.getElementById('formEditarProveedor').action = `/proveedores/${prov.id}`;
    document.getElementById('edit_prov_nombre').value = prov.nombre || '';
    document.getElementById('edit_prov_contacto').value = prov.contacto_nombre || '';
    document.getElementById('edit_prov_identificacion').value = prov.identificacion || '';
    document.getElementById('edit_prov_telefono').value = prov.telefono || '';
    document.getElementById('edit_prov_correo').value = prov.correo || '';
    document.getElementById('edit_prov_estado').value = prov.estado || 'activo';
    document.getElementById('edit_prov_direccion').value = prov.direccion || '';
    document.getElementById('edit_prov_notas').value = prov.notas || '';

    openModal('modalEditarProveedor');
}

function abrirDocumentoCompra(compra) {
    const modal = document.getElementById('modal-documento-compra');
    document.getElementById('doc-compra-titulo').innerText = `Factura: ${compra.numero_factura || 'SIN FACTURA (#000' + compra.id + ')'}`;
    document.getElementById('doc-compra-fecha').innerText = `Fecha de Recepción: ${compra.fecha_compra ? new Date(compra.fecha_compra).toLocaleString() : 'N/A'}`;

    let html = `
        <div style="background: #f8fafc; border-radius: 10px; padding: 1.25rem; border: 1px solid var(--border-color); margin-bottom: 1.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div>
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">PROVEEDOR</div>
                <div style="font-weight: 800; font-size: 1.05rem; color: var(--primary); margin-top: 0.2rem;">${compra.proveedor_nombre || 'Proveedor General'}</div>
            </div>
            <div>
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">COMPROBANTE N°</div>
                <div style="font-weight: 800; font-size: 1.05rem; color: var(--accent); margin-top: 0.2rem; font-family: monospace;">${compra.numero_factura || 'N/A'}</div>
            </div>
            <div>
                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">REGISTRADO POR</div>
                <div style="font-weight: 700; margin-top: 0.2rem;">${compra.user ? compra.user.name : 'Sistema'}</div>
            </div>
        </div>

        <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.85rem;">Detalle de Artículos Recibidos</h3>
        <div style="overflow-x: auto; margin-bottom: 1.5rem;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Artículo</th>
                        <th style="text-align: center;">Cajas/Cajas</th>
                        <th style="text-align: center;">Cant. / Peso</th>
                        <th style="text-align: right;">Costo U.</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
    `;

    if (compra.detalles && compra.detalles.length > 0) {
        compra.detalles.forEach(d => {
            const cod = d.articulo ? (d.articulo.codigo || d.articulo.codigo_cliente || '-') : '-';
            const nom = d.articulo ? (d.articulo.descripcion || d.articulo.nombre || 'Artículo General') : 'Artículo General';
            const peso = parseFloat(d.cantidad_peso || 0).toFixed(2);
            const cajas = d.cajas || 1;
            const costo = parseFloat(d.costo_unitario || 0).toFixed(2);
            const sub = parseFloat(d.subtotal || 0).toFixed(2);

            html += `
                <tr>
                    <td style="font-family: monospace; font-weight: 600;">${cod}</td>
                    <td style="font-weight: 700;">${nom}</td>
                    <td style="text-align: center;"><span class="badge badge-info">${cajas} caja(s)</span></td>
                    <td style="text-align: center; font-weight: 700;">${peso} LB/U</td>
                    <td style="text-align: right;">$${costo}</td>
                    <td style="text-align: right; font-weight: 800; color: var(--accent);">$${sub}</td>
                </tr>
            `;
        });
    } else {
        html += `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">Sin detalles registrados.</td></tr>`;
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
