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

<!-- Modal de Precios Especiales -->
<div id="modalPreciosEspeciales" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(3px); z-index: 100; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; width: 480px; max-width: 95%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color); overflow: hidden;">
        <!-- Header -->
        <div style="padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="font-weight: 800; font-size: 1.1rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-tags" style="color: var(--primary);"></i> Precios Especiales / Tarifas
            </div>
            <button type="button" onclick="closePreciosEspecialesModal()" style="background: none; border: none; font-size: 1.3rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <!-- Body -->
        <div style="padding: 1.25rem 1.4rem;">
            <div style="margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                <div id="peModalArticuloNombre" style="font-weight: 700; font-size: 1.05rem; color: var(--text-main);">Artículo</div>
                <div id="peModalArticuloCodigo" style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">Código: -</div>
            </div>

            <div style="font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 0.6rem; text-transform: uppercase;">
                Selecciona la tarifa a aplicar:
            </div>

            <div id="pePreciosList" style="display: flex; flex-direction: column; gap: 0.6rem; max-height: 320px; overflow-y: auto;">
                <!-- Se llena dinámicamente -->
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 0.85rem 1.4rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: flex-end;">
            <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.5rem 1.25rem;" onclick="closePreciosEspecialesModal()">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal de Descuentos -->
<div id="modalDescuento" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(3px); z-index: 105; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: white; border-radius: 14px; width: 480px; max-width: 95%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-color); overflow: hidden;">
        <!-- Header -->
        <div style="padding: 1.1rem 1.4rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
            <div style="font-weight: 800; font-size: 1.1rem; color: #d97706; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-percent"></i> Aplicar Descuento
            </div>
            <button type="button" onclick="closeDescuentoModal()" style="background: none; border: none; font-size: 1.3rem; color: var(--text-muted); cursor: pointer;">&times;</button>
        </div>

        <!-- Body -->
        <div style="padding: 1.25rem 1.4rem;">
            <!-- Producto Info -->
            <div style="margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);">
                <div id="descModalArticuloNombre" style="font-weight: 700; font-size: 1.05rem; color: var(--text-main);">Artículo</div>
                <div id="descModalArticuloInfo" style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">Cantidad: 1 | Precio U: $0.00</div>
            </div>

            <!-- Selector de Tipo de Descuento -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1.25rem;">
                <button type="button" id="btnTipoDescPorcentaje" class="btn-modern" onclick="setTipoDescuento('porcentaje')" style="padding: 0.6rem; font-size: 0.9rem; font-weight: 700; background: #d97706; border: 2px solid #d97706; color: white;">
                    <i class="fa-solid fa-percent"></i> Por Porcentaje (%)
                </button>
                <button type="button" id="btnTipoDescFijo" class="btn-modern btn-secondary" onclick="setTipoDescuento('fijo')" style="padding: 0.6rem; font-size: 0.9rem; font-weight: 700; background: white; border: 2px solid var(--border-color); color: var(--text-main);">
                    <i class="fa-solid fa-dollar-sign"></i> Descuento Fijo ($)
                </button>
            </div>

            <!-- Campo de Entrada -->
            <div style="margin-bottom: 1rem;">
                <label id="lblValorDescuento" style="font-size: 0.85rem; font-weight: 700; color: #64748b; margin-bottom: 0.4rem; display: block;">
                    Porcentaje de Descuento (%)
                </label>
                <div style="position: relative;">
                    <input type="number" id="inputValorDescuento" class="input-modern" step="any" min="0" placeholder="0" style="font-size: 1.4rem; font-weight: 800; text-align: center; color: #d97706;" oninput="updateDescuentoPreview()" onkeydown="if(event.key==='Enter'){event.preventDefault();confirmarDescuento();}">
                    <span id="addonSimboloDescuento" style="position: absolute; right: 1.2rem; top: 50%; transform: translateY(-50%); font-weight: 800; font-size: 1.2rem; color: #d97706;">%</span>
                </div>
            </div>

            <!-- Botones Rápidos / Presets -->
            <div id="presetsDescuentoPorcentaje" style="display: flex; gap: 0.4rem; margin-bottom: 1.25rem; flex-wrap: wrap;">
                <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="setPresetDescuento(5)">5%</button>
                <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="setPresetDescuento(10)">10%</button>
                <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="setPresetDescuento(15)">15%</button>
                <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="setPresetDescuento(20)">20%</button>
                <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="setPresetDescuento(25)">25%</button>
                <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.8rem;" onclick="setPresetDescuento(50)">50%</button>
            </div>

            <!-- Previsualización de Cálculos -->
            <div style="background: #fffbeb; border: 1px dashed #fcd34d; border-radius: 10px; padding: 0.85rem 1rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: #92400e; margin-bottom: 0.25rem;">
                    <span>Subtotal Original:</span>
                    <strong id="prevSubtotalOriginal">$0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.95rem; color: #d97706; font-weight: 700; margin-bottom: 0.25rem;">
                    <span>Descuento Calculado:</span>
                    <strong id="prevDescuentoMonto">-$0.00</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 1.1rem; color: #059669; font-weight: 800; border-top: 1px solid #fde68a; padding-top: 0.35rem; margin-top: 0.35rem;">
                    <span>Total Final:</span>
                    <strong id="prevTotalFinal">$0.00</strong>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding: 0.85rem 1.4rem; border-top: 1px solid var(--border-color); background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.5rem 1rem; color: #ef4444; border-color: #fca5a5;" onclick="quitarDescuentoSeleccionado()">
                <i class="fa-solid fa-trash"></i> Quitar Descuento
            </button>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.5rem 1rem;" onclick="closeDescuentoModal()">Cancelar</button>
                <button type="button" class="btn-modern btn-primary" style="width: auto; padding: 0.5rem 1.25rem; background: #d97706; border-color: #d97706;" onclick="confirmarDescuento()">
                    <i class="fa-solid fa-check"></i> Aplicar Descuento
                </button>
            </div>
        </div>
    </div>
</div>


