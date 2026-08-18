@extends('layouts.app')

@section('title', 'Estado de Cuenta - ' . $cliente->nombre)

@section('content')
<div style="display: flex; flex-direction: column; gap: 1.5rem;">

    <!-- Regresar y Acciones -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <a href="{{ route('clientes.index') }}" style="display: inline-flex; align-items: center; gap: 0.4rem; color: var(--text-muted); text-decoration: none; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.4rem;">
                <i class="fa-solid fa-arrow-left"></i> Volver al Directorio de Clientes
            </a>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.6rem;">
                <i class="fa-solid fa-address-card" style="color: var(--primary);"></i> Perfil y Estado de Cuenta
            </h1>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            @if($cliente->saldo_deudor > 0)
                <button type="button" class="btn-modern btn-primary" onclick="openAbonoModal({{ json_encode($cliente) }})" style="background: #10b981; border-color: #10b981; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem;">
                    <i class="fa-solid fa-hand-holding-dollar"></i> Registrar Abono
                </button>
            @endif
            <button type="button" class="btn-modern btn-secondary" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem;">
                <i class="fa-solid fa-print"></i> Imprimir Estado
            </button>
        </div>
    </div>

    <!-- Alertas -->
    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 0.85rem 1.25rem; border-radius: 10px; font-weight: 600; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i> {{ session('success') }}
        </div>
    @endif

    <!-- Tarjeta Principal del Cliente -->
    <div class="card" style="padding: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; align-items: center;">
            <!-- Datos de Contacto -->
            <div>
                <div style="font-size: 1.3rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.35rem;">
                    {{ $cliente->nombre }}
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted); display: flex; flex-direction: column; gap: 0.25rem;">
                    @if($cliente->identificacion)
                        <div><strong style="color: var(--text-main);">ID/RUC:</strong> {{ $cliente->identificacion }}</div>
                    @endif
                    @if($cliente->telefono)
                        <div><i class="fa-solid fa-phone" style="font-size: 0.75rem;"></i> {{ $cliente->telefono }}</div>
                    @endif
                    @if($cliente->email)
                        <div><i class="fa-solid fa-envelope" style="font-size: 0.75rem;"></i> {{ $cliente->email }}</div>
                    @endif
                    @if($cliente->direccion)
                        <div><i class="fa-solid fa-location-dot" style="font-size: 0.75rem;"></i> {{ $cliente->direccion }}</div>
                    @endif
                </div>
            </div>

            <!-- Saldo Deudor -->
            <div style="background: #fef2f2; border: 1.5px solid #fecaca; border-radius: 12px; padding: 1.25rem; text-align: center;">
                <span style="font-size: 0.8rem; font-weight: 700; color: #991b1b; text-transform: uppercase; display: block; letter-spacing: 0.5px;">Saldo Deudor Pendiente</span>
                <div style="font-size: 2.2rem; font-weight: 800; color: #dc2626; margin: 0.25rem 0;">
                    ${{ number_format($cliente->saldo_deudor, 2) }}
                </div>
                <span style="font-size: 0.78rem; color: #b91c1c;">
                    {{ $cliente->saldo_deudor > 0 ? 'Cuenta activa con saldo por cobrar' : 'Cliente al día ($0.00)' }}
                </span>
            </div>

            <!-- Límite de Crédito -->
            <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: 12px; padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.4rem;">
                    <span style="color: var(--text-muted); font-weight: 600;">Límite de Crédito:</span>
                    <strong style="color: var(--text-main);">${{ number_format($cliente->limite_credito, 2) }}</strong>
                </div>
                @php
                    $limite = (float) $cliente->limite_credito;
                    $saldo = (float) $cliente->saldo_deudor;
                    $pct = $limite > 0 ? min(100, round(($saldo / $limite) * 100)) : 0;
                @endphp
                <div style="background: #e2e8f0; border-radius: 10px; height: 10px; overflow: hidden; margin-bottom: 0.5rem;">
                    <div style="width: {{ $pct }}%; background: {{ $pct > 80 ? '#dc2626' : ($pct > 50 ? '#d97706' : '#10b981') }}; height: 100%; border-radius: 10px;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--text-muted);">
                    <span>Uso: {{ $pct }}%</span>
                    <span>Disponible: ${{ number_format(max(0, $limite - $saldo), 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas de Información -->
    <div class="card" style="padding: 1.25rem;">
        <!-- Botones Tab -->
        <div style="display: flex; gap: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
            <button type="button" class="btn-tab active" id="tabBtnVentasCredito" onclick="switchTab('ventasCredito')">
                <i class="fa-solid fa-credit-card"></i> Ventas a Crédito ({{ $cliente->ventas->where('tipo_venta', 'credito')->count() }})
            </button>
            <button type="button" class="btn-tab" id="tabBtnAbonos" onclick="switchTab('abonos')">
                <i class="fa-solid fa-receipt"></i> Historial de Abonos ({{ $cliente->abonos->count() }})
            </button>
            <button type="button" class="btn-tab" id="tabBtnTodasVentas" onclick="switchTab('todasVentas')">
                <i class="fa-solid fa-list"></i> Todas las Ventas ({{ $cliente->ventas->count() }})
            </button>
        </div>

        <!-- TAB 1: Ventas a Crédito -->
        <div id="tabContentVentasCredito" style="display: block;">
            <div style="overflow-x: auto;">
                <table class="table-modern" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>N° Ticket</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th style="text-align: right;">Subtotal</th>
                            <th style="text-align: right;">Descuento</th>
                            <th style="text-align: right;">Total Crédito</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cliente->ventas->where('tipo_venta', 'credito') as $v)
                            @php
                                $infoSt = $estadoCreditoVentas[$v->id] ?? [
                                    'label' => 'Crédito Pendiente',
                                    'color' => '#dc2626',
                                    'bg' => 'rgba(239, 68, 68, 0.12)',
                                    'estado' => 'pendiente'
                                ];
                            @endphp
                            <tr style="border-bottom: 1px solid var(--border-color); {{ $infoSt['estado'] === 'pagado' ? 'background: rgba(16, 185, 129, 0.03);' : '' }}">
                                <td style="font-weight: 700; font-family: monospace; color: var(--primary);">
                                    #{{ str_pad($v->id, 6, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>{{ $v->created_at->format('d/m/Y h:i A') }}</td>
                                <td>
                                    <span class="badge" style="background: {{ $infoSt['bg'] }}; color: {{ $infoSt['color'] }}; font-weight: 700;">
                                        @if($infoSt['estado'] === 'pagado')
                                            <i class="fa-solid fa-circle-check"></i>
                                        @elseif($infoSt['estado'] === 'parcial')
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        @else
                                            <i class="fa-solid fa-clock"></i>
                                        @endif
                                        {{ $infoSt['label'] }}
                                    </span>
                                </td>
                                <td style="text-align: right;">${{ number_format($v->subtotal, 2) }}</td>
                                <td style="text-align: right; color: #d97706;">-${{ number_format($v->descuento, 2) }}</td>
                                <td style="text-align: right; font-weight: 800; color: {{ $infoSt['color'] }}; font-size: 1.05rem;">
                                    ${{ number_format($v->total, 2) }}
                                    @if($infoSt['estado'] === 'pagado')
                                        <div style="font-size: 0.75rem; color: #10b981; font-weight: 700;">(Saldado)</div>
                                    @elseif($infoSt['estado'] === 'parcial')
                                        <div style="font-size: 0.75rem; color: #d97706; font-weight: 600;">(Pend: ${{ number_format($infoSt['pendiente'], 2) }})</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    Este cliente no tiene ventas a crédito registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: Historial de Abonos -->
        <div id="tabContentAbonos" style="display: none;">
            <div style="overflow-x: auto;">
                <table class="table-modern" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>N° Abono</th>
                            <th>Fecha</th>
                            <th>Método Pago</th>
                            <th style="text-align: right;">Saldo Anterior</th>
                            <th style="text-align: right;">Monto Abonado</th>
                            <th style="text-align: right;">Nuevo Saldo</th>
                            <th>Notas / Registro</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cliente->abonos as $ab)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="font-weight: 700; font-family: monospace; color: #10b981;">
                                    #AB-{{ str_pad($ab->id, 6, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>{{ $ab->created_at->format('d/m/Y h:i A') }}</td>
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
                                    {{ $ab->notas ?: '-' }} (Cajero: {{ $ab->user->name ?? 'Sistema' }})
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn-modern btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.78rem;" onclick="verTicketAbono({{ $ab->id }})">
                                        <i class="fa-solid fa-receipt" style="color: #10b981;"></i> Ver Ticket
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    No se han registrado abonos para este cliente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: Todas las Ventas -->
        <div id="tabContentTodasVentas" style="display: none;">
            <div style="overflow-x: auto;">
                <table class="table-modern" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>N° Ticket</th>
                            <th>Fecha</th>
                            <th>Tipo Venta</th>
                            <th>Método Pago</th>
                            <th style="text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cliente->ventas as $v)
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="font-weight: 700; font-family: monospace; color: var(--primary);">
                                    #{{ str_pad($v->id, 6, '0', STR_PAD_LEFT) }}
                                </td>
                                <td>{{ $v->created_at->format('d/m/Y h:i A') }}</td>
                                <td>
                                    @if($v->tipo_venta === 'credito')
                                        <span class="badge" style="background: rgba(217, 119, 6, 0.15); color: #d97706;">Crédito</span>
                                    @else
                                        <span class="badge badge-success">Contado</span>
                                    @endif
                                </td>
                                <td style="text-transform: uppercase; font-size: 0.85rem;">{{ $v->metodo_pago }}</td>
                                <td style="text-align: right; font-weight: 800; color: var(--primary);">
                                    ${{ number_format($v->total, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                    Sin historial de ventas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@include('clientes._modals')

<style>
.btn-tab {
    padding: 0.55rem 1.1rem;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--text-muted);
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}
@media print {
    .btn-modern, .btn-tab, nav.sidebar, header { display: none !important; }
}
</style>

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

    function switchTab(tab) {
        document.getElementById('tabContentVentasCredito').style.display = tab === 'ventasCredito' ? 'block' : 'none';
        document.getElementById('tabContentAbonos').style.display = tab === 'abonos' ? 'block' : 'none';
        document.getElementById('tabContentTodasVentas').style.display = tab === 'todasVentas' ? 'block' : 'none';

        document.getElementById('tabBtnVentasCredito').classList.toggle('active', tab === 'ventasCredito');
        document.getElementById('tabBtnAbonos').classList.toggle('active', tab === 'abonos');
        document.getElementById('tabBtnTodasVentas').classList.toggle('active', tab === 'todasVentas');
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
</script>
@endpush
@endsection
