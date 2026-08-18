@extends('layouts.app')

@section('content')
<div class="header-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.8rem; font-weight: 700; color: var(--text-color); margin: 0; display: flex; align-items: center; gap: 0.75rem;">
            <i class="fa-solid fa-cash-register" style="color: var(--accent);"></i> Control y Arqueo de Caja
        </h1>
        <p style="color: var(--text-muted); margin-top: 0.25rem; font-size: 0.95rem;">
            Gestión de apertura de turno, fondo inicial, gastos/entradas de dinero y cierre de caja.
        </p>
    </div>

    <div>
        @if(!$cajaActiva)
            <button type="button" class="btn-modern btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem; display: inline-flex; align-items: center; gap: 0.5rem;" onclick="abrirModalApertura()">
                <i class="fa-solid fa-lock-open"></i> Aperturar Caja de Turno
            </button>
        @else
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button type="button" class="btn-modern btn-secondary" style="display: inline-flex; align-items: center; gap: 0.5rem;" onclick="abrirModalMovimiento('entrada')">
                    <i class="fa-solid fa-circle-plus" style="color: #10b981;"></i> Entrada Dinero
                </button>
                <button type="button" class="btn-modern btn-secondary" style="display: inline-flex; align-items: center; gap: 0.5rem;" onclick="abrirModalMovimiento('salida')">
                    <i class="fa-solid fa-circle-minus" style="color: #ef4444;"></i> Salida / Pago Proveedor
                </button>
                <button type="button" class="btn-modern btn-primary" style="background: var(--danger, #ef4444); border-color: var(--danger, #ef4444); display: inline-flex; align-items: center; gap: 0.5rem;" onclick="abrirModalCierre()">
                    <i class="fa-solid fa-vault"></i> Arquear y Cerrar Caja
                </button>
            </div>
        @endif
    </div>
</div>

@if(session('status'))
    <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #10b981; padding: 1rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 600; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
        <div><i class="fa-solid fa-circle-check"></i> {{ session('status') }}</div>
        @if(session('cierre_id'))
            <button type="button" class="btn-modern btn-secondary js-btn-ticket-z" data-url="{{ route('caja.ticketCierre', session('cierre_id')) }}" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: white; color: #10b981; border: 1px solid #10b981; cursor: pointer;">
                <i class="fa-solid fa-print"></i> Imprimir Ticket de Cierre Z
            </button>
        @endif
    </div>
@endif

@if($errors->has('caja'))
    <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #ef4444; padding: 1rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-weight: 600;">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first('caja') }}
    </div>
@endif

<!-- ESTADO GENERAL Y TARJETAS DE SALDOS DE CAJA -->
@if($cajaActiva)
    <div style="background: var(--surface-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 1.25rem;">
            <div>
                <span class="badge badge-success" style="font-size: 0.85rem; padding: 0.35rem 0.75rem; text-transform: uppercase;">
                    <i class="fa-solid fa-circle-dot"></i> TURNO DE CAJA ABIERTO
                </span>
                <span style="margin-left: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                    Aperturado por <strong>{{ $cajaActiva->user->name ?? 'Usuario' }}</strong> el {{ $cajaActiva->fecha_apertura->format('d/m/Y h:i A') }}
                </span>
            </div>
            <div>
                <button type="button" class="btn-modern btn-secondary js-btn-ticket-z" data-url="{{ route('caja.ticketCierre', $cajaActiva) }}" style="padding: 0.35rem 0.75rem; font-size: 0.85rem; color: var(--accent); font-weight: 600; cursor: pointer;">
                    <i class="fa-solid fa-print"></i> Vista previa Ticket Turno
                </button>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem;">
            <!-- Fondo Inicial -->
            <div style="background: var(--surface-bg); border: 1px solid var(--border-color); padding: 1rem; border-radius: var(--radius-md);">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Fondo Inicial</div>
                <div style="font-size: 1.4rem; font-weight: 700; color: var(--text-color); margin-top: 0.25rem;">
                    ${{ number_format($cajaActiva->monto_inicial, 2) }}
                </div>
            </div>

            <!-- Ventas Efectivo -->
            <div style="background: var(--surface-bg); border: 1px solid var(--border-color); padding: 1rem; border-radius: var(--radius-md);">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Ventas Efectivo</div>
                <div style="font-size: 1.4rem; font-weight: 700; color: #10b981; margin-top: 0.25rem;">
                    +${{ number_format($cajaActiva->total_ventas_efectivo, 2) }}
                </div>
            </div>

            <!-- Entradas Manuales -->
            <div style="background: var(--surface-bg); border: 1px solid var(--border-color); padding: 1rem; border-radius: var(--radius-md);">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Entradas Manuales</div>
                <div style="font-size: 1.4rem; font-weight: 700; color: #3b82f6; margin-top: 0.25rem;">
                    +${{ number_format($cajaActiva->total_entradas, 2) }}
                </div>
            </div>

            <!-- Salidas / Gastos -->
            <div style="background: var(--surface-bg); border: 1px solid var(--border-color); padding: 1rem; border-radius: var(--radius-md);">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Salidas / Gastos</div>
                <div style="font-size: 1.4rem; font-weight: 700; color: #ef4444; margin-top: 0.25rem;">
                    -${{ number_format($cajaActiva->total_salidas, 2) }}
                </div>
            </div>

            <!-- Ventas Tarjeta / Transf -->
            <div style="background: var(--surface-bg); border: 1px solid var(--border-color); padding: 1rem; border-radius: var(--radius-md);">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Tarjeta / Transferencia</div>
                <div style="font-size: 1.4rem; font-weight: 700; color: #8b5cf6; margin-top: 0.25rem;">
                    ${{ number_format($cajaActiva->total_ventas_tarjeta + $cajaActiva->total_ventas_transferencia, 2) }}
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">(No afecta efectivo físico)</div>
            </div>

            <!-- Descuentos Aplicados -->
            <div style="background: var(--surface-bg); border: 1px solid var(--border-color); padding: 1rem; border-radius: var(--radius-md);">
                <div style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Descuentos Aplicados</div>
                <div style="font-size: 1.4rem; font-weight: 700; color: #d97706; margin-top: 0.25rem;">
                    -${{ number_format($cajaActiva->total_descuentos ?? $cajaActiva->ventas()->sum('descuento'), 2) }}
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                    {{ $cajaActiva->ventas()->where('descuento', '>', 0)->count() }} venta(s) con descuento
                </div>
            </div>

            <!-- SALDO ESPERADO EN CAJA (DESTACADO) -->
            <div style="background: linear-gradient(135deg, var(--accent) 0%, #2563eb 100%); color: white; padding: 1rem; border-radius: var(--radius-md); box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);">
                <div style="font-size: 0.8rem; opacity: 0.9; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Efectivo Esperado en Caja</div>
                <div style="font-size: 1.7rem; font-weight: 800; margin-top: 0.25rem;">
                    ${{ number_format($cajaActiva->saldo_esperado, 2) }}
                </div>
            </div>
        </div>
    </div>

    <!-- MOVIMIENTOS DEL TURNO ACTUAL -->
    <div class="card" style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-list-check" style="color: var(--accent);"></i> Movimientos del Turno Actual
        </h3>

        <div style="overflow-x: auto;">
            <table class="table-modern" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 0.75rem 1rem; text-align: left;">Hora</th>
                        <th style="padding: 0.75rem 1rem; text-align: left;">Tipo</th>
                        <th style="padding: 0.75rem 1rem; text-align: left;">Concepto / Detalle</th>
                        <th style="padding: 0.75rem 1rem; text-align: left;">Usuario</th>
                        <th style="padding: 0.75rem 1rem; text-align: right;">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: rgba(16, 185, 129, 0.05);">
                        <td style="padding: 0.75rem 1rem; color: var(--text-muted);">{{ $cajaActiva->fecha_apertura->format('h:i A') }}</td>
                        <td style="padding: 0.75rem 1rem;"><span class="badge badge-success">Apertura</span></td>
                        <td style="padding: 0.75rem 1rem; font-weight: 600;">Fondo de Caja Inicial</td>
                        <td style="padding: 0.75rem 1rem;">{{ $cajaActiva->user->name ?? 'Sistema' }}</td>
                        <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: #10b981;">${{ number_format($cajaActiva->monto_inicial, 2) }}</td>
                    </tr>

                    @forelse($cajaActiva->movimientos as $mov)
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 0.75rem 1rem; color: var(--text-muted);">{{ $mov->created_at->format('h:i A') }}</td>
                            <td style="padding: 0.75rem 1rem;">
                                @if($mov->tipo === 'entrada')
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">Entrada</span>
                                @else
                                    <span class="badge" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">Salida</span>
                                @endif
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 600;">{{ $mov->concepto }}</div>
                                @if($mov->observaciones)<div style="font-size: 0.8rem; color: var(--text-muted);">{{ $mov->observaciones }}</div>@endif
                            </td>
                            <td style="padding: 0.75rem 1rem;">{{ $mov->user->name ?? 'Cajero' }}</td>
                            <td style="padding: 0.75rem 1rem; text-align: right; font-weight: 700; color: {{ $mov->tipo === 'entrada' ? '#3b82f6' : '#ef4444' }};">
                                {{ $mov->tipo === 'entrada' ? '+' : '-' }}${{ number_format($mov->monto, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding: 1.5rem; text-align: center; color: var(--text-muted);">
                                No se han registrado movimientos de entradas/salidas manuales en este turno.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="card" style="text-align: center; padding: 3rem 1.5rem; margin-bottom: 2rem; border: 2px dashed var(--border-color);">
        <i class="fa-solid fa-lock" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
        <h2 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 0.5rem;">Caja de Turno Actualmente Cerrada</h2>
        <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem auto;">
            Para comenzar a realizar cobros y ventas en el punto de venta (TPV), primero debes aperturar la caja e ingresar el monto de fondo inicial.
        </p>
        <button type="button" class="btn-modern btn-primary" style="padding: 0.75rem 2rem; font-size: 1.05rem; display: inline-flex; align-items: center; gap: 0.5rem;" onclick="abrirModalApertura()">
            <i class="fa-solid fa-lock-open"></i> Aperturar Caja Ahora
        </button>
    </div>
@endif

<!-- ENLACE AL REPORTE DE CAJA -->
<div class="card" style="padding: 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <div style="background: rgba(37,99,235,0.1); color: var(--primary); width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0;">
            <i class="fa-solid fa-vault"></i>
        </div>
        <div>
            <div style="font-size: 1rem; font-weight: 700; color: var(--text-main);">Historial de Cierres y Arqueos</div>
            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">El reporte completo de turnos y cierres de caja se encuentra en el Módulo de Reportes.</div>
        </div>
    </div>
    <a href="{{ route('reportes.index', ['tab' => 'caja']) }}" class="btn-modern btn-primary" style="white-space: nowrap; display: inline-flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-chart-pie"></i> Ver Reporte de Caja
    </a>
</div>

<!-- MODAL APERTURA DE CAJA -->
<div id="modalApertura" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; color: #1e293b; width: 100%; max-width: 480px; border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); border: 1px solid #e2e8f0;">
        <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; color: #0f172a;">
            <i class="fa-solid fa-lock-open" style="color: var(--accent);"></i> Aperturar Caja de Turno
        </h3>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.25rem;">
            Ingresa el monto físico de dinero en efectivo con el que inicias tu caja para cambio.
        </p>

        <form action="{{ route('caja.aperturar') }}" method="POST">
            @csrf
            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Fondo Inicial en Efectivo ($)</label>
                <input type="number" step="0.01" min="0" class="input-modern" name="monto_inicial" placeholder="0.00" style="font-size: 1.4rem; font-weight: 700; text-align: right; background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1;" required autofocus>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Observaciones / Notas (Opcional)</label>
                <textarea class="input-modern" name="observaciones" rows="2" placeholder="Ej. Cambio en billetes de 20 y 50 pesos..." style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1;"></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn-modern btn-secondary" onclick="cerrarModales()">Cancelar</button>
                <button type="submit" class="btn-modern btn-primary">Aperturar Turno</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL MOVIMIENTO MANUAL (ENTRADA / SALIDA) -->
<div id="modalMovimiento" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; color: #1e293b; width: 100%; max-width: 480px; border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); border: 1px solid #e2e8f0;">
        <h3 id="movimientoTitle" style="font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; color: #0f172a;">
            <!-- Inyectado por JS -->
        </h3>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.25rem;">
            Registra una entrada o egreso de dinero en efectivo de la caja actual.
        </p>

        <form action="{{ route('caja.movimiento.store') }}" method="POST">
            @csrf
            <input type="hidden" id="movimientoTipo" name="tipo" value="entrada">

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Monto ($)</label>
                <input type="number" step="0.01" min="0.01" class="input-modern" name="monto" style="font-size: 1.4rem; font-weight: 700; text-align: right; background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1;" placeholder="0.00" required>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Concepto / Motivo</label>
                <input type="text" class="input-modern" id="movimientoConcepto" name="concepto" placeholder="Ej. Pago a proveedor de carne, compra de hielo..." style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1;" required>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Observaciones Adicionales</label>
                <textarea class="input-modern" name="observaciones" rows="2" placeholder="Notas opcionales o número de comprobante..." style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1;"></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn-modern btn-secondary" onclick="cerrarModales()">Cancelar</button>
                <button type="submit" id="movimientoSubmitBtn" class="btn-modern btn-primary">Guardar Movimiento</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ARQUEO Y CIERRE DE CAJA -->
@if($cajaActiva)
<div id="modalCierre" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; color: #1e293b; width: 100%; max-width: 520px; border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35); border: 1px solid #e2e8f0;">
        <h3 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; color: #ef4444;">
            <i class="fa-solid fa-vault"></i> Arquear y Cerrar Caja de Turno
        </h3>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.25rem;">
            Cuenta el dinero en efectivo presente en el cajón de la caja e ingrésalo a continuación.
        </p>

        <form action="{{ route('caja.cerrar') }}" method="POST">
            @csrf

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; margin-bottom: 0.35rem;">
                    <span style="color: #64748b;">Efectivo Esperado en Sistema:</span>
                    <strong style="font-size: 1.1rem; color: var(--accent);">${{ number_format($cajaActiva->saldo_esperado, 2) }}</strong>
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Dinero Físico Contado ($)</label>
                <input type="number" step="0.01" min="0" class="input-modern" id="saldoRealInput" name="saldo_real" style="font-size: 1.5rem; font-weight: 800; text-align: right; background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1;" value="{{ number_format($cajaActiva->saldo_esperado, 2, '.', '') }}" oninput="calcularDiferencia({{ $cajaActiva->saldo_esperado }})" required autofocus>
            </div>

            <div id="boxDiferencia" style="padding: 0.75rem 1rem; border-radius: var(--radius-md); font-weight: 700; margin-bottom: 1.25rem; text-align: center; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid #10b981;">
                Diferencia: $0.00 (Caja Cuadrada Perfecta)
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 0.35rem;">Notas de Cierre / Explicación de Diferencias</label>
                <textarea class="input-modern" name="observaciones" rows="2" placeholder="Ej. Faltante de 10 pesos por error en cambio..." style="background: #ffffff; color: #0f172a; border: 1px solid #cbd5e1;"></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
                <button type="button" class="btn-modern btn-secondary" onclick="cerrarModales()">Cancelar</button>
                <button type="submit" class="btn-modern btn-primary" style="background: #ef4444; border-color: #ef4444;">Confirmar Cierre de Caja</button>
            </div>
        </form>
    </div>
</div>
</div>
@endif

<script>
    function abrirModalApertura() {
        document.getElementById('modalApertura').style.display = 'flex';
    }

    function abrirModalMovimiento(tipo) {
        const modal = document.getElementById('modalMovimiento');
        const title = document.getElementById('movimientoTitle');
        const inputTipo = document.getElementById('movimientoTipo');
        const inputConcepto = document.getElementById('movimientoConcepto');
        const btn = document.getElementById('movimientoSubmitBtn');

        inputTipo.value = tipo;

        if (tipo === 'entrada') {
            title.innerHTML = '<i class="fa-solid fa-circle-plus" style="color: #10b981;"></i> Registrar Entrada de Dinero';
            inputConcepto.placeholder = 'Ej. Aporte de cambio extra en monedas...';
            btn.style.background = '#10b981';
            btn.style.borderColor = '#10b981';
        } else {
            title.innerHTML = '<i class="fa-solid fa-circle-minus" style="color: #ef4444;"></i> Registrar Salida / Pago Proveedor';
            inputConcepto.placeholder = 'Ej. Pago a proveedor de carne, compra de hielo, retiros...';
            btn.style.background = '#ef4444';
            btn.style.borderColor = '#ef4444';
        }

        modal.style.display = 'flex';
    }

    function abrirModalCierre() {
        const modal = document.getElementById('modalCierre');
        if (modal) modal.style.display = 'flex';
    }

    function cerrarModales() {
        document.getElementById('modalApertura').style.display = 'none';
        document.getElementById('modalMovimiento').style.display = 'none';
        const modalCierre = document.getElementById('modalCierre');
        if (modalCierre) modalCierre.style.display = 'none';
    }

    function calcularDiferencia(esperado) {
        const inputReal = parseFloat(document.getElementById('saldoRealInput').value) || 0;
        const diff = inputReal - esperado;
        const box = document.getElementById('boxDiferencia');

        if (Math.abs(diff) < 0.01) {
            box.style.background = 'rgba(16, 185, 129, 0.15)';
            box.style.borderColor = '#10b981';
            box.style.color = '#10b981';
            box.innerHTML = 'Diferencia: $0.00 (Caja Cuadrada Perfecta)';
        } else if (diff > 0) {
            box.style.background = 'rgba(59, 130, 246, 0.15)';
            box.style.borderColor = '#3b82f6';
            box.style.color = '#3b82f6';
            box.innerHTML = 'Diferencia: +$' + diff.toFixed(2) + ' (Sobrante de dinero)';
        } else {
            box.style.background = 'rgba(239, 68, 68, 0.15)';
            box.style.borderColor = '#ef4444';
            box.style.color = '#ef4444';
            box.innerHTML = 'Diferencia: -$' + Math.abs(diff).toFixed(2) + ' (Faltante de dinero)';
        }
    }
</script>

@push('modals')
<!-- MODAL TICKET Z PREVIEW -->
<div id="modal-ticket-z-caja" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; max-width: 520px; width: 95%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color); overflow: hidden;">
        <div style="padding: 1rem 1.25rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="font-weight: 800; font-size: 1.1rem; color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-receipt" style="color: #10b981;"></i> Vista Previa Ticket Z
            </div>
            <button type="button" id="btn-cerrar-ticket-z-caja" style="background: none; border: none; font-size: 1.4rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>
        <div style="flex: 1; min-height: 450px; background: #f1f5f9; padding: 0.5rem;">
            <iframe id="iframe-ticket-z-caja" src="" style="width: 100%; height: 450px; border: none; border-radius: 8px; background: white;"></iframe>
        </div>
        <div style="padding: 0.85rem 1.25rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" id="btn-imprimir-ticket-z-caja" class="btn-modern btn-primary"><i class="fa-solid fa-print"></i> Imprimir Ticket</button>
            <button type="button" id="btn-cerrar-ticket-z-caja-2" class="btn-modern btn-secondary">Cerrar</button>
        </div>
    </div>
</div>
<script>
(function() {
    var modal = document.getElementById('modal-ticket-z-caja');
    var iframe = document.getElementById('iframe-ticket-z-caja');

    function cerrarTicket() {
        if (modal) modal.style.display = 'none';
        if (iframe) iframe.src = '';
    }

    var btn1 = document.getElementById('btn-cerrar-ticket-z-caja');
    if (btn1) btn1.addEventListener('click', cerrarTicket);

    var btn2 = document.getElementById('btn-cerrar-ticket-z-caja-2');
    if (btn2) btn2.addEventListener('click', cerrarTicket);

    var btnPrint = document.getElementById('btn-imprimir-ticket-z-caja');
    if (btnPrint) btnPrint.addEventListener('click', function() {
        if (iframe && iframe.contentWindow) iframe.contentWindow.print();
    });

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.js-btn-ticket-z');
        if (btn) {
            e.preventDefault();
            var url = btn.getAttribute('data-url');
            if (url && modal && iframe) {
                iframe.src = url;
                modal.style.display = 'flex';
            }
        }
    });
})();
</script>
@endpush
@endsection
