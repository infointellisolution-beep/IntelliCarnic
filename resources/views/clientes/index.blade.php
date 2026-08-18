@extends('layouts.app')

@section('title', 'Directorio de Clientes y Crédito')

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">

    <!-- Encabezado del Módulo -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                <i class="fa-solid fa-users" style="color: var(--primary);"></i> Directorio de Clientes y Cuentas por Cobrar
            </h1>
            <p style="color: var(--text-muted); margin: 0.2rem 0 0 0; font-size: 0.9rem;">
                Gestión de clientes, historial de ventas, cartera de crédito y registro de abonos.
            </p>
        </div>
        <div>
            <button type="button" class="btn-modern btn-primary" onclick="openModal('modalNuevoCliente')" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem;">
                <i class="fa-solid fa-user-plus"></i> Nuevo Cliente
            </button>
        </div>
    </div>

    <!-- Mensajes de Alerta -->
    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 0.85rem 1.25rem; border-radius: 10px; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 0.85rem 1.25rem; border-radius: 10px; font-weight: 600;">
            <ul style="margin: 0; padding-left: 1.2rem;">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tarjetas KPI -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem;">
        <!-- Cartera por Cobrar (Deuda Total) -->
        <div class="card" style="padding: 1.25rem; background: linear-gradient(135deg, #ffffff 0%, #fff5f5 100%); border-left: 4px solid #dc2626;">
            <div style="font-size: 0.8rem; color: #991b1b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Cartera Total por Cobrar</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #dc2626; margin-top: 0.35rem;">
                ${{ number_format($totalCartera, 2) }}
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                Saldo acumulado pendiente de cobro
            </div>
        </div>

        <!-- Total Clientes -->
        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Clientes Registrados</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin-top: 0.35rem;">
                {{ number_format($totalClientes) }}
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                Base de datos de compradores
            </div>
        </div>

        <!-- Clientes con Deuda -->
        <div class="card" style="padding: 1.25rem;">
            <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Clientes con Saldo Deudor</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #d97706; margin-top: 0.35rem;">
                {{ number_format($clientesConDeuda) }}
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                Cuentas por cobrar activas
            </div>
        </div>

        <!-- Abonos Recibidos este Mes -->
        <div class="card" style="padding: 1.25rem; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%); border-left: 4px solid #10b981;">
            <div style="font-size: 0.8rem; color: #166534; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Abonos Recuperados este Mes</div>
            <div style="font-size: 1.8rem; font-weight: 800; color: #10b981; margin-top: 0.35rem;">
                ${{ number_format($abonosMes, 2) }}
            </div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">
                Recaudado en {{ now()->translatedFormat('F Y') }}
            </div>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda -->
    <div class="card" style="padding: 1rem 1.25rem;">
        <form method="GET" action="{{ route('clientes.index') }}" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; margin: 0;">
            <div style="position: relative; flex: 1; min-width: 250px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="text" name="q" value="{{ $q }}" class="input-modern" placeholder="Buscar cliente por nombre, DNI/RUC, teléfono o email..." style="padding-left: 2.6rem;">
            </div>

            <div style="min-width: 180px;">
                <select name="filtro_saldo" class="input-modern" onchange="this.form.submit()" style="font-weight: 600;">
                    <option value="todos" {{ $filtroSaldo === 'todos' ? 'selected' : '' }}>Todos los clientes</option>
                    <option value="con_deuda" {{ $filtroSaldo === 'con_deuda' ? 'selected' : '' }}>Con Saldo Deudor (> $0)</option>
                    <option value="al_dia" {{ $filtroSaldo === 'al_dia' ? 'selected' : '' }}>Al día ($0.00)</option>
                </select>
            </div>

            <button type="submit" class="btn-modern btn-primary" style="width: auto; padding: 0.55rem 1.2rem;">
                Filtrar
            </button>

            @if($q || $filtroSaldo !== 'todos')
                <a href="{{ route('clientes.index') }}" class="btn-modern btn-secondary" style="width: auto; padding: 0.55rem 1rem; color: var(--text-muted);">
                    Limpiar Filtros
                </a>
            @endif
        </form>
    </div>

    <!-- Tabla de Clientes -->
    <div class="card" style="padding: 1.25rem;">
        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Cliente / Razón Social</th>
                        <th>Identificación</th>
                        <th>Contacto</th>
                        <th style="text-align: right;">Límite Crédito</th>
                        <th style="text-align: right;">Saldo Deudor</th>
                        <th style="text-align: center;">Estado</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $c)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td>
                                <a href="{{ route('clientes.show', $c) }}" style="font-weight: 700; color: var(--primary); text-decoration: none; font-size: 0.95rem;">
                                    {{ $c->nombre }}
                                </a>
                                @if($c->direccion)
                                    <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                        <i class="fa-solid fa-location-dot"></i> {{ Str::limit($c->direccion, 35) }}
                                    </div>
                                @endif
                            </td>
                            <td style="font-family: monospace; font-weight: 600;">
                                {{ $c->identificacion ?: '-' }}
                            </td>
                            <td style="font-size: 0.85rem;">
                                @if($c->telefono)
                                    <div><i class="fa-solid fa-phone" style="font-size: 0.75rem; color: var(--text-muted);"></i> {{ $c->telefono }}</div>
                                @endif
                                @if($c->email)
                                    <div style="color: var(--text-muted); font-size: 0.78rem;">{{ $c->email }}</div>
                                @endif
                                @if(!$c->telefono && !$c->email)
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td style="text-align: right; font-weight: 600;">
                                ${{ number_format($c->limite_credito, 2) }}
                            </td>
                            <td style="text-align: right; font-weight: 800; font-size: 1.05rem; color: {{ $c->saldo_deudor > 0 ? '#dc2626' : '#10b981' }};">
                                ${{ number_format($c->saldo_deudor, 2) }}
                            </td>
                            <td style="text-align: center;">
                                @if($c->estado === 'activo')
                                    <span class="badge badge-success" style="font-size: 0.75rem;">Activo</span>
                                @else
                                    <span class="badge" style="background: rgba(100, 116, 139, 0.15); color: #64748b; font-size: 0.75rem;">Inactivo</span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 0.35rem; align-items: center;">
                                    <a href="{{ route('clientes.show', $c) }}" class="btn-modern btn-secondary" style="padding: 0.3rem 0.55rem; font-size: 0.8rem;" title="Ver Estado de Cuenta / Historial">
                                        <i class="fa-solid fa-eye" style="color: var(--primary);"></i> Ver
                                    </a>

                                    @if($c->saldo_deudor > 0)
                                        <button type="button" class="btn-modern btn-secondary" style="padding: 0.3rem 0.55rem; font-size: 0.8rem; color: #10b981; border-color: #a7f3d0;" onclick="openAbonoModal({{ json_encode($c) }})" title="Registrar Abono">
                                            <i class="fa-solid fa-hand-holding-dollar"></i> Abonar
                                        </button>
                                    @endif

                                    <button type="button" class="btn-modern btn-secondary" style="padding: 0.3rem 0.55rem; font-size: 0.8rem;" onclick="openEditarClienteModal({{ json_encode($c) }})" title="Editar Perfil">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form action="{{ route('clientes.destroy', $c) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar el cliente {{ $c->nombre }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-modern btn-secondary" style="padding: 0.3rem 0.55rem; font-size: 0.8rem; color: #ef4444; border-color: #fca5a5;" title="Eliminar Cliente">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                                <i class="fa-solid fa-users-slash" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 0.5rem; display: block;"></i>
                                No se encontraron clientes registrados con los criterios seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.25rem;">
            {{ $clientes->appends(['q' => $q, 'filtro_saldo' => $filtroSaldo])->links() }}
        </div>
    </div>
</div>

@include('clientes._modals')

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

    let currentClienteAbono = null;

    function openAbonoModal(cliente) {
        currentClienteAbono = cliente;
        document.getElementById('formRegistrarAbono').action = `/clientes/${cliente.id}/abono`;
        document.getElementById('abonoClienteNombre').textContent = cliente.nombre;
        document.getElementById('abonoSaldoPendiente').textContent = '$' + parseFloat(cliente.saldo_deudor).toFixed(2);
        document.getElementById('txtSaldoTotalLiquidar').textContent = parseFloat(cliente.saldo_deudor).toFixed(2);
        
        const inputMonto = document.getElementById('inputMontoAbono');
        inputMonto.value = '';
        inputMonto.max = parseFloat(cliente.saldo_deudor);

        openModal('modalRegistrarAbono');
        setTimeout(() => inputMonto.focus(), 100);
    }

    function liquidarDeudaTotal() {
        if (!currentClienteAbono) return;
        const inputMonto = document.getElementById('inputMontoAbono');
        inputMonto.value = parseFloat(currentClienteAbono.saldo_deudor).toFixed(2);
    }

    function openEditarClienteModal(cliente) {
        document.getElementById('formEditarCliente').action = `/clientes/${cliente.id}`;
        document.getElementById('edit_nombre').value = cliente.nombre || '';
        document.getElementById('edit_identificacion').value = cliente.identificacion || '';
        document.getElementById('edit_telefono').value = cliente.telefono || '';
        document.getElementById('edit_email').value = cliente.email || '';
        document.getElementById('edit_limite_credito').value = cliente.limite_credito || 0;
        document.getElementById('edit_direccion').value = cliente.direccion || '';
        document.getElementById('edit_estado').value = cliente.estado || 'activo';
        document.getElementById('edit_notas').value = cliente.notas || '';

        openModal('modalEditarCliente');
    }
</script>
@endpush
@endsection
