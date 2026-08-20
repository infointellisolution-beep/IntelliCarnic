@extends('layouts.app')

@section('title', 'Directorio de Proveedores — IntelliCarnic')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">

    <!-- ENCABEZADO DE PÁGINA -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0;">
                <i class="fa-solid fa-truck-field" style="color: var(--accent);"></i> Módulo de Proveedores
            </h1>
            <p style="color: var(--text-muted); margin: 0.25rem 0 0; font-size: 0.92rem;">
                Gestión comercial, compras e historial de suministros con proveedores
            </p>
        </div>
        <div>
            <button type="button" class="btn-modern btn-accent" style="width: auto; padding: 0.65rem 1.25rem;" onclick="openModal('modalCrearProveedor')">
                <i class="fa-solid fa-plus"></i> Nuevo Proveedor
            </button>
        </div>
    </div>

    <!-- ALERTAS -->
    @if(session('success'))
        <div class="card" style="border-left: 4px solid #10b981; background: #f0fdf4; color: #166534; padding: 1rem 1.25rem;">
            <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="card" style="border-left: 4px solid #ef4444; background: #fef2f2; color: #991b1b; padding: 1rem 1.25rem;">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}
        </div>
    @endif

    <!-- TARJETAS KPI RESUMEN DE PROVEEDORES -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
        <!-- Total Proveedores -->
        <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--primary);">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Total Proveedores</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">
                {{ number_format($totalProveedores) }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">En catálogo de suministros</div>
        </div>

        <!-- Proveedores Activos -->
        <div class="card" style="padding: 1.25rem; border-left: 4px solid #10b981;">
            <div style="font-size: 0.8rem; font-weight: 700; color: #10b981; text-transform: uppercase;">Proveedores Activos</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;">
                {{ number_format($proveedoresActivos) }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Habilitados para compra</div>
        </div>

        <!-- Compras del Mes -->
        <div class="card" style="padding: 1.25rem; border-left: 4px solid var(--accent);">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--accent); text-transform: uppercase;">Compras del Mes ($)</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent); margin-top: 0.25rem;">
                ${{ number_format($comprasMesMonto, 2) }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Mercancía recibida este mes</div>
        </div>

        <!-- Proveedor Principal -->
        <div class="card" style="padding: 1.25rem; border-left: 4px solid #6366f1;">
            <div style="font-size: 0.8rem; font-weight: 700; color: #6366f1; text-transform: uppercase;">Proveedor Principal</div>
            <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-top: 0.35rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                {{ $topProveedorRecord?->proveedor?->nombre ?: 'Sin registros' }}
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">
                ${{ number_format($topProveedorRecord?->total_invertido ?: 0, 2) }} invertidos
            </div>
        </div>
    </div>

    <!-- TABLA Y BUSCADOR -->
    <div class="card" style="padding: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
            <h3 style="font-size: 1.1rem; font-weight: 700; margin: 0;">Directorio General de Proveedores</h3>
            
            <form method="GET" action="{{ route('proveedores.index') }}" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap; margin: 0;">
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por nombre, RUC, teléfono..." class="input-modern" style="width: 260px; padding: 0.45rem 0.85rem; font-size: 0.9rem;">
                
                <select name="filtro_estado" class="input-modern" onchange="this.form.submit()" style="width: 150px; padding: 0.45rem 0.85rem; font-size: 0.9rem; font-weight: 600;">
                    <option value="todos" {{ $filtroEstado === 'todos' ? 'selected' : '' }}>Todos (Estado)</option>
                    <option value="activo" {{ $filtroEstado === 'activo' ? 'selected' : '' }}>🟢 Activos</option>
                    <option value="inactivo" {{ $filtroEstado === 'inactivo' ? 'selected' : '' }}>🔴 Inactivos</option>
                </select>

                <button type="submit" class="btn-modern btn-primary" style="width: auto; padding: 0.45rem 0.95rem;">
                    <i class="fa-solid fa-magnifying-glass"></i> Buscar
                </button>

                @if($q !== '' || $filtroEstado !== 'todos')
                    <a href="{{ route('proveedores.index') }}" class="btn-modern btn-secondary" style="width: auto; padding: 0.45rem 0.85rem; text-decoration: none; display: inline-block;">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Proveedor / Razón Social</th>
                        <th>Contacto</th>
                        <th>RUC / Identificación</th>
                        <th>Teléfono</th>
                        <th style="text-align: center;">Recepciones</th>
                        <th style="text-align: right;">Total Invertido</th>
                        <th style="text-align: center;">Estado</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proveedores as $prov)
                        @php
                            $totInv = (float) ($prov->compras_sum_total ?? 0);
                        @endphp
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="font-weight: 700;">
                                <a href="{{ route('proveedores.show', $prov->id) }}" style="color: var(--primary); text-decoration: none; font-size: 1rem;">
                                    <i class="fa-solid fa-truck" style="color: var(--text-muted); margin-right: 4px;"></i> {{ $prov->nombre }}
                                </a>
                                @if($prov->direccion)
                                    <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 400;">
                                        {{ Str::limit($prov->direccion, 45) }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $prov->contacto_nombre ?: '-' }}</td>
                            <td style="font-family: monospace;">{{ $prov->identificacion ?: '-' }}</td>
                            <td>{{ $prov->telefono ?: '-' }}</td>
                            <td style="text-align: center;">
                                <span class="badge badge-info">{{ $prov->compras_count }} factura(s)</span>
                            </td>
                            <td style="text-align: right; font-weight: 800; color: var(--accent); font-size: 1.05rem;">
                                ${{ number_format($totInv, 2) }}
                            </td>
                            <td style="text-align: center;">
                                @if($prov->estado === 'activo')
                                    <span class="badge badge-success">🟢 Activo</span>
                                @else
                                    <span class="badge badge-danger">🔴 Inactivo</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 0.35rem; justify-content: center;">
                                    <a href="{{ route('proveedores.show', $prov->id) }}" class="btn-modern btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; text-decoration: none;" title="Ver Perfil e Historial">
                                        <i class="fa-solid fa-eye" style="color: var(--primary);"></i>
                                    </a>
                                    
                                    <button type="button" class="btn-modern btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;" onclick="openEditarProveedor({{ json_encode($prov) }})" title="Editar Proveedor">
                                        <i class="fa-solid fa-pen" style="color: #d97706;"></i>
                                    </button>

                                    <form action="{{ route('proveedores.destroy', $prov->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('¿Seguro que deseas eliminar a este proveedor?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-modern btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem; color: #dc2626;" title="Eliminar Proveedor">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                No se encontraron proveedores que coincidan con la búsqueda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.25rem;">
            {{ $proveedores->appends(['q' => $q, 'filtro_estado' => $filtroEstado])->links() }}
        </div>
    </div>

</div>

@include('proveedores._modals')

@push('scripts')
<script>
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
</script>
@endpush
@endsection
