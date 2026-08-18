<!-- MODAL NUEVO CLIENTE -->
<div id="modalNuevoCliente" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; width: 560px; max-width: 95%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color); overflow: hidden;">
        <form action="{{ route('clientes.store') }}" method="POST" style="display: flex; flex-direction: column; height: 100%; margin: 0;">
            @csrf
            <!-- Header -->
            <div style="padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div style="font-weight: 800; font-size: 1.15rem; color: var(--primary); display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fa-solid fa-user-plus"></i> Registrar Nuevo Cliente
                </div>
                <button type="button" onclick="closeModal('modalNuevoCliente')" style="background: none; border: none; font-size: 1.3rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <!-- Body -->
            <div style="padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Nombre Completo / Razón Social <span style="color: #dc2626;">*</span>
                    </label>
                    <input type="text" name="nombre" class="input-modern" required placeholder="Ej: Juan Pérez / Carnicería El Ganadero">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Identificación / RUC / DNI
                    </label>
                    <input type="text" name="identificacion" class="input-modern" placeholder="Ej: 0801199012345">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Teléfono / Celular
                    </label>
                    <input type="text" name="telefono" class="input-modern" placeholder="Ej: 9988-7766">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Correo Electrónico
                    </label>
                    <input type="email" name="email" class="input-modern" placeholder="cliente@correo.com">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Límite de Crédito ($)
                    </label>
                    <input type="number" step="0.01" min="0" name="limite_credito" class="input-modern" placeholder="0.00 (0 = Sin límite)" value="0.00">
                </div>

                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Dirección Completa
                    </label>
                    <input type="text" name="direccion" class="input-modern" placeholder="Calle, Barrio, Ciudad...">
                </div>

                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Notas / Observaciones
                    </label>
                    <textarea name="notas" class="input-modern" rows="2" placeholder="Detalles de facturación, referencias..."></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div style="padding: 0.85rem 1.5rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn-modern btn-secondary" onclick="closeModal('modalNuevoCliente')">Cancelar</button>
                <button type="submit" class="btn-modern btn-primary"><i class="fa-solid fa-check"></i> Guardar Cliente</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REGISTRAR ABONO -->
<div id="modalRegistrarAbono" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; width: 480px; max-width: 95%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color); overflow: hidden;">
        <form id="formRegistrarAbono" action="" method="POST" style="margin: 0; display: flex; flex-direction: column; height: 100%;">
            @csrf
            <!-- Header -->
            <div style="padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div style="font-weight: 800; font-size: 1.15rem; color: #10b981; display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fa-solid fa-hand-holding-dollar"></i> Registrar Abono a Deuda
                </div>
                <button type="button" onclick="closeModal('modalRegistrarAbono')" style="background: none; border: none; font-size: 1.3rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <!-- Body -->
            <div style="padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1;">
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="font-size: 0.75rem; color: #166534; font-weight: 700; text-transform: uppercase; display: block;">Cliente</span>
                        <strong id="abonoClienteNombre" style="font-size: 1rem; color: #14532d;">-</strong>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 0.75rem; color: #166534; font-weight: 700; text-transform: uppercase; display: block;">Saldo Pendiente</span>
                        <strong id="abonoSaldoPendiente" style="font-size: 1.2rem; color: #dc2626;">$0.00</strong>
                    </div>
                </div>

                <div style="margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin: 0;">
                            Monto a Abonar ($) <span style="color: #dc2626;">*</span>
                        </label>
                        <button type="button" class="btn-modern btn-secondary" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; color: #10b981; border-color: #a7f3d0;" onclick="liquidarDeudaTotal()">
                            Liquidar Todo ($<span id="txtSaldoTotalLiquidar">0.00</span>)
                        </button>
                    </div>
                    <input type="number" step="0.01" min="0.01" id="inputMontoAbono" name="monto" class="input-modern" required placeholder="0.00" style="font-size: 1.3rem; font-weight: 800; color: #10b981; text-align: center;">
                </div>

                <div style="margin-bottom: 1rem;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Método de Pago <span style="color: #dc2626;">*</span>
                    </label>
                    <select name="metodo_pago" class="input-modern" required style="font-weight: 600;">
                        <option value="efectivo">Efectivo (Entrada de Caja)</option>
                        <option value="tarjeta">Tarjeta de Crédito / Débito</option>
                        <option value="transferencia">Transferencia Bancaria</option>
                    </select>
                </div>

                <div style="margin-bottom: 0.5rem;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Notas / Concepto
                    </label>
                    <input type="text" name="notas" class="input-modern" placeholder="Ej: Pago parcial / Abono semana...">
                </div>
            </div>

            <!-- Footer -->
            <div style="padding: 0.85rem 1.5rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn-modern btn-secondary" onclick="closeModal('modalRegistrarAbono')">Cancelar</button>
                <button type="submit" class="btn-modern btn-primary" style="background: #10b981; border-color: #10b981;">
                    <i class="fa-solid fa-check"></i> Registrar Abono
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDITAR CLIENTE -->
<div id="modalEditarCliente" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; width: 560px; max-width: 95%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color); overflow: hidden;">
        <form id="formEditarCliente" action="" method="POST" style="display: flex; flex-direction: column; height: 100%; margin: 0;">
            @csrf
            @method('PUT')
            <!-- Header -->
            <div style="padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <div style="font-weight: 800; font-size: 1.15rem; color: var(--primary); display: flex; align-items: center; gap: 0.6rem;">
                    <i class="fa-solid fa-user-pen"></i> Editar Perfil de Cliente
                </div>
                <button type="button" onclick="closeModal('modalEditarCliente')" style="background: none; border: none; font-size: 1.3rem; color: var(--text-muted); cursor: pointer;">&times;</button>
            </div>

            <!-- Body -->
            <div style="padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Nombre Completo / Razón Social <span style="color: #dc2626;">*</span>
                    </label>
                    <input type="text" id="edit_nombre" name="nombre" class="input-modern" required>
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Identificación / RUC / DNI
                    </label>
                    <input type="text" id="edit_identificacion" name="identificacion" class="input-modern">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Teléfono / Celular
                    </label>
                    <input type="text" id="edit_telefono" name="telefono" class="input-modern">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Correo Electrónico
                    </label>
                    <input type="email" id="edit_email" name="email" class="input-modern">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Límite de Crédito ($)
                    </label>
                    <input type="number" step="0.01" min="0" id="edit_limite_credito" name="limite_credito" class="input-modern">
                </div>

                <div>
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Estado
                    </label>
                    <select id="edit_estado" name="estado" class="input-modern" style="font-weight: 600;">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </div>

                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Dirección Completa
                    </label>
                    <input type="text" id="edit_direccion" name="direccion" class="input-modern">
                </div>

                <div style="grid-column: span 2;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 0.35rem; display: block;">
                        Notas / Observaciones
                    </label>
                    <textarea id="edit_notas" name="notas" class="input-modern" rows="2"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div style="padding: 0.85rem 1.5rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn-modern btn-secondary" onclick="closeModal('modalEditarCliente')">Cancelar</button>
                <button type="submit" class="btn-modern btn-primary"><i class="fa-solid fa-floppy-disk"></i> Actualizar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL COMPROBANTE DE ABONO (TICKET 80mm) -->
<div id="modalComprobanteAbono" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); z-index: 1100; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 12px; width: 380px; max-width: 95%; display: flex; flex-direction: column; max-height: 90vh; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <!-- Header -->
        <div class="no-print" style="padding: 0.85rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <h3 style="margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--text-main);">
                <i class="fa-solid fa-receipt" style="color: #10b981;"></i> Comprobante de Abono
            </h3>
            <button type="button" onclick="closeModal('modalComprobanteAbono')" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <!-- Area Imprimible -->
        <div id="printableAbonoArea" style="padding: 1.5rem; overflow-y: auto; font-family: monospace; font-size: 12px; color: black; background: white;">
            <!-- Contenido dinamico -->
        </div>

        <!-- Footer Acciones -->
        <div class="no-print" style="padding: 0.85rem 1.25rem; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <button type="button" class="btn-modern btn-secondary" onclick="closeModal('modalComprobanteAbono')">Cerrar</button>
            <button type="button" class="btn-modern btn-primary" style="background: #10b981; border-color: #10b981;" onclick="imprimirTicketAbono()">
                <i class="fa-solid fa-print"></i> Imprimir Ticket
            </button>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden !important; }
    #modalComprobanteAbono, #modalComprobanteAbono * { visibility: visible !important; }
    #modalComprobanteAbono { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; background: white !important; display: block !important; }
    #printableAbonoArea { padding: 0 !important; width: 80mm !important; margin: 0 auto !important; }
    .no-print { display: none !important; }
}
</style>

<script>
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

            let statusBanner = '';
            if (saldoNuev <= 0) {
                statusBanner = `
                    <div style="background: #f0fdf4; border: 1.5px dashed #16a34a; color: #15803d; font-weight: 800; padding: 6px; margin: 10px 0; font-size: 12px; text-align: center;">
                        *** DEUDA TOTALMENTE LIQUIDADA ***<br>
                        ¡SU CUENTA HA QUEDADO EN $0.00!
                    </div>
                `;
            } else {
                statusBanner = `
                    <div style="background: #fffbeb; border: 1.5px dashed #fcd34d; color: #d97706; font-weight: 800; padding: 6px; margin: 10px 0; font-size: 11px; text-align: center;">
                        ABONO PARCIAL A CUENTA A CRÉDITO
                    </div>
                `;
            }

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
            const modal = document.getElementById('modalComprobanteAbono');
            if (modal) modal.style.display = 'flex';

            setTimeout(() => {
                try {
                    if (typeof JsBarcode === 'function') {
                        JsBarcode("#abonoBarcodeSvg", abonoNum, {
                            format: "CODE128",
                            width: 1.6,
                            height: 40,
                            displayValue: true,
                            fontSize: 12,
                            margin: 4
                        });
                    }
                } catch (e) {
                    console.error("Error Barcode Abono:", e);
                }
            }, 50);

        } else {
            alert(data.message || 'Error al obtener el comprobante.');
        }
    } catch (e) {
        console.error('Error al ver ticket de abono:', e);
    }
}

function imprimirTicketAbono() {
    window.print();
}

@if(session('abono_id'))
    document.addEventListener('DOMContentLoaded', () => {
        verTicketAbono({{ session('abono_id') }});
    });
@endif
</script>

