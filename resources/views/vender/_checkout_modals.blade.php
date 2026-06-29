<!-- Modal de Cobro -->
<div id="checkoutModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; width: 450px; max-width: 90%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
        <h2 style="margin-top: 0; margin-bottom: 1rem; color: var(--text-main); text-align: center; font-size: 1.5rem;">Completar Pago</h2>
        
        <div style="text-align: center; margin-bottom: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: 8px;">
            <div style="font-size: 0.9rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Total a Cobrar</div>
            <div id="checkoutTotalDisplay" style="font-size: 2.5rem; font-weight: 800; color: var(--primary);"></div>
        </div>

        <div class="input-group" style="margin-bottom: 1rem;">
            <label>Método de Pago</label>
            <select id="checkoutMetodoPago" class="input-modern" onchange="handlePaymentMethodChange()">
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta (Crédito/Débito)</option>
                <option value="transferencia">Transferencia</option>
            </select>
        </div>

        <div class="input-group" style="margin-bottom: 1rem;" id="montoRecibidoContainer">
            <label>Monto Recibido</label>
            <input type="number" id="checkoutMontoRecibido" class="input-modern" style="font-size: 1.5rem; text-align: center;" step="0.01" min="0" oninput="calculateVuelto()">
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0; border-top: 1px dashed var(--border-color); margin-bottom: 1.5rem;">
            <span style="font-size: 1.1rem; font-weight: 600; color: var(--text-main);">Vuelto / Cambio:</span>
            <span id="checkoutVueltoDisplay" style="font-size: 1.5rem; font-weight: 700; color: #059669;">$0.00</span>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button class="btn-modern btn-secondary" style="flex: 1; padding: 1rem;" onclick="closeCheckoutModal()">Cancelar</button>
            <button id="btnConfirmCheckout" class="btn-modern btn-primary" style="flex: 2; padding: 1rem; background: #059669;" onclick="confirmCheckout()">Registrar Venta</button>
        </div>
    </div>
</div>

<!-- Modal de Ticket (Previsualización) -->
<div id="ticketModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 110; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; width: 350px; max-width: 90%; display: flex; flex-direction: column; max-height: 90vh;">
        
        <!-- Header del Modal -->
        <div style="padding: 1rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border-radius: 8px 8px 0 0;">
            <h3 style="margin: 0;">Ticket Generado</h3>
            <button onclick="closeTicketModal()" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <!-- Área del Ticket Imprimible -->
        <div id="printableTicketArea" style="padding: 1.5rem; overflow-y: auto; font-family: monospace; font-size: 12px; color: black; background: white;">
            <!-- Contenido dinámico del ticket -->
        </div>

        <!-- Footer del Modal -->
        <div style="padding: 1rem; border-top: 1px solid #e2e8f0; display: flex; gap: 0.5rem; background: #f8fafc; border-radius: 0 0 8px 8px;">
            <button class="btn-modern btn-secondary" style="flex: 1;" onclick="closeTicketModal()">Cerrar (Nueva Venta)</button>
            <button class="btn-modern btn-primary" style="flex: 1;" onclick="printTicket()"><i class="fa-solid fa-print"></i> Imprimir</button>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printableTicketArea, #printableTicketArea * {
        visibility: visible;
    }
    #printableTicketArea {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        overflow: visible;
    }
}
</style>

<!-- Modal de Error Personalizado -->
<div id="errorModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 200; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; width: 400px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="color: #ef4444; font-size: 3rem; margin-bottom: 1rem;">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <h3 id="errorModalTitle" style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-top: 0; margin-bottom: 1rem;">Error</h3>
        <p id="errorModalMessage" style="color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.5; font-size: 1.1rem;"></p>
        <button onclick="document.getElementById('errorModal').style.display='none'" class="btn-modern btn-primary" style="width: 100%; justify-content: center; background: #ef4444; border-color: #ef4444;">Entendido</button>
    </div>
</div>
