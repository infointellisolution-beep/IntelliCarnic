@extends('layouts.app')

@section('title', 'Historial de Compras - IntelliCarnic')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Módulo de Compras (Abastecimiento)</h1>
        <p class="page-subtitle">Recepción de mercancía, abastecimiento de inventario y actualización de costos.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="{{ route('compras.create') }}" class="btn-modern btn-accent">
            <i class="fa-solid fa-cart-flatbed"></i> Registrar Nueva Compra
        </a>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success" style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-circle-check"></i> {{ session('status') }}
    </div>
@endif

<!-- Filtros de búsqueda -->
<div class="card" style="margin-bottom: 1.5rem;">
    <form method="GET" action="{{ route('compras.index') }}" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 240px;">
            <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.35rem; display: block;">Buscar Factura / Proveedor</label>
            <input type="text" name="search" class="input-modern" placeholder="Ej. FAC-1002 o San Martín..." value="{{ request('search') }}">
        </div>
        <div>
            <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.35rem; display: block;">Desde</label>
            <input type="date" name="fecha_desde" class="input-modern" value="{{ request('fecha_desde') }}">
        </div>
        <div>
            <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.35rem; display: block;">Hasta</label>
            <input type="date" name="fecha_hasta" class="input-modern" value="{{ request('fecha_hasta') }}">
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn-modern btn-secondary">
                <i class="fa-solid fa-filter"></i> Filtrar
            </button>
            @if(request()->hasAny(['search', 'fecha_desde', 'fecha_hasta']))
                <a href="{{ route('compras.index') }}" class="btn-modern btn-secondary" style="background: transparent; border-color: var(--border-color);">
                    <i class="fa-solid fa-rotate-left"></i> Limpiar
                </a>
            @endif
        </div>
    </form>
</div>

<!-- Tabla de Compras -->
<div class="card">
    <div class="table-responsive">
        <table class="table-modern">
            <thead>
                <tr>
                    <th>Nº Factura / Comp.</th>
                    <th>Proveedor</th>
                    <th>Fecha de Compra</th>
                    <th>Items / Peso Total</th>
                    <th>Total Compra</th>
                    <th>Registrado Por</th>
                    <th style="text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($compras as $compra)
                    @php
                        $pesoTotal = $compra->detalles->sum('cantidad_peso');
                    @endphp
                    <tr>
                        <td style="font-weight: 700; font-family: monospace;">
                            {{ $compra->numero_factura ?: 'S/N (' . str_pad($compra->id, 5, '0', STR_PAD_LEFT) . ')' }}
                        </td>
                        <td style="font-weight: 600;">
                            {{ $compra->proveedor_nombre }}
                        </td>
                        <td style="color: var(--text-muted);">
                            {{ $compra->fecha_compra->format('d/m/Y h:i A') }}
                        </td>
                        <td>
                            <span class="badge badge-info" style="background: var(--surface-bg); border: 1px solid var(--border-color); color: var(--text-main);">
                                {{ $compra->detalles->count() }} ítems / {{ number_format($pesoTotal, 2) }} {{ $settings['unidad_peso'] ?? 'lb' }}
                            </span>
                        </td>
                        <td style="font-weight: 700; color: var(--accent); font-size: 1.05rem;">
                            ${{ number_format($compra->total, 2) }}
                        </td>
                        <td style="font-size: 0.9rem; color: var(--text-muted);">
                            <i class="fa-solid fa-user-circle"></i> {{ $compra->user?->name ?? 'Sistema' }}
                        </td>
                        <td style="text-align: center;">
                            <button class="btn-modern btn-secondary js-ver-compra" data-id="{{ $compra->id }}" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">
                                <i class="fa-solid fa-eye"></i> Detalle
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            <i class="fa-solid fa-truck-ramp-box" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.4;"></i>
                            <p style="font-size: 1.1rem; font-weight: 600;">No se encontraron registros de compras</p>
                            <p style="font-size: 0.9rem;">Empieza registrando tu primera factura de compras para abastecer tu inventario.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($compras->hasPages())
        <div style="margin-top: 1.5rem;">
            {{ $compras->links() }}
        </div>
    @endif
</div>

<!-- Modal Ver Detalle de Compra -->
<div id="modal-detalle-compra" class="modal-overlay" style="display: none;">
    <div class="modal-card" style="max-width: 800px; width: 95%;">
        <div class="modal-header">
            <h2 id="modal-compra-title" style="font-size: 1.25rem; font-weight: 700;">Detalle de Compra</h2>
            <button type="button" class="modal-close js-modal-compra-close">&times;</button>
        </div>
        <div class="modal-body" id="modal-compra-body">
            <div style="text-align: center; padding: 2rem;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: var(--accent);"></i>
                <p style="margin-top: 0.5rem; color: var(--text-muted);">Cargando detalle...</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('modal-detalle-compra');
        const modalBody = document.getElementById('modal-compra-body');
        const modalTitle = document.getElementById('modal-compra-title');
        const closeBtns = document.querySelectorAll('.js-modal-compra-close');

        closeBtns.forEach(btn => btn.addEventListener('click', () => {
            modal.style.display = 'none';
        }));

        document.querySelectorAll('.js-ver-compra').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                modal.style.display = 'flex';
                modalBody.innerHTML = `
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: var(--accent);"></i>
                        <p style="margin-top: 0.5rem; color: var(--text-muted);">Cargando detalle...</p>
                    </div>
                `;

                try {
                    const res = await fetch(`/compras/${id}`);
                    const data = await res.json();
                    const c = data.compra;

                    modalTitle.innerText = `Factura: ${c.numero_factura || 'S/N (#'+c.id+')'}`;

                    let html = `
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; background: var(--surface-bg); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border-color);">
                            <div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Proveedor</div>
                                <div style="font-weight: 700; font-size: 1.05rem;">${c.proveedor_nombre}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Fecha</div>
                                <div style="font-weight: 600;">${new Date(c.fecha_compra).toLocaleString()}</div>
                            </div>
                            <div>
                                <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Registrado Por</div>
                                <div style="font-weight: 600;">${c.user ? c.user.name : 'Sistema'}</div>
                            </div>
                        </div>

                        <h4 style="font-weight: 700; margin-bottom: 0.75rem;">Ítems Recibidos</h4>
                        <div class="table-responsive">
                            <table class="table-modern" style="font-size: 0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Lote / Serie</th>
                                        <th>Peso/Cant</th>
                                        <th>Costo Unit.</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                    `;

                    c.detalles.forEach(d => {
                        html += `
                            <tr>
                                <td>
                                    <div style="font-weight: 600;">${d.articulo ? d.articulo.descripcion : 'Artículo Eliminado'}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">SKU: ${d.articulo ? d.articulo.codigo : '-'}</div>
                                </td>
                                <td style="font-size: 0.85rem; font-family: monospace;">
                                    ${d.lote ? '<div>Lote: '+d.lote+'</div>' : ''}
                                    ${d.serie ? '<div style="color: var(--text-muted);">Serie: '+d.serie+'</div>' : ''}
                                    ${!d.lote && !d.serie ? '-' : ''}
                                </td>
                                <td style="font-weight: 600;">${parseFloat(d.cantidad_peso).toFixed(2)}</td>
                                <td>$${parseFloat(d.costo_unitario).toFixed(2)}</td>
                                <td style="font-weight: 700;">$${parseFloat(d.subtotal).toFixed(2)}</td>
                            </tr>
                        `;
                    });

                    html += `
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 1.5rem; text-align: right; font-size: 1.2rem; font-weight: 700;">
                            Total General: <span style="color: var(--accent);">$${parseFloat(c.total).toFixed(2)}</span>
                        </div>
                    `;

                    modalBody.innerHTML = html;
                } catch (e) {
                    modalBody.innerHTML = `<div class="alert alert-danger">Error al cargar el detalle de la compra.</div>`;
                }
            });
        });
    });
</script>
@endpush
@endsection
