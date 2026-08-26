// Estado Global del Carrito
let cart = [];
let ivaRate = 0.21; // 21% IVA por defecto

// Elementos del DOM a actualizar
let tbodyNormal = null;
let tbodyTactil = null;
let labelTotalNormal = null;
let labelTotalTactil = null;
let labelStatsNormal = null;
let currentSelectedIndex = -1; // Para la selección de líneas en Venta Normal

// Datos inyectados desde Blade estarán en la variable global windowArticulos y windowSettings
if (typeof windowArticulos === 'undefined') {
    window.windowArticulos = [];
}
if (typeof windowSettings === 'undefined') {
    window.windowSettings = {
        usar_impuestos: "1",
        iva_global_enabled: "1",
        iva_global_rate: "21"
    };
}

// Variables para el TPV Táctil (Familias)
let currentFamiliaId = null;

// Variables para Gestión de Clientes y Crédito en TPV
let currentPosCliente = null;
let currentTipoVenta = 'normal';

function openSeleccionarClienteModal() {
    const modal = document.getElementById('modalSeleccionarCliente');
    if (modal) {
        modal.style.display = 'flex';
        const input = document.getElementById('posClienteSearchInput');
        if (input) {
            input.value = '';
            input.focus();
            buscarClientePos('');
        }
    }
}

function closeSeleccionarClienteModal() {
    const modal = document.getElementById('modalSeleccionarCliente');
    if (modal) modal.style.display = 'none';
}

let searchClienteTimer = null;
function buscarClientePos(query) {
    clearTimeout(searchClienteTimer);
    searchClienteTimer = setTimeout(async () => {
        const resBox = document.getElementById('posClientesResultados');
        if (!resBox) return;

        try {
            const res = await fetch(`/clientes/api/buscar?q=${encodeURIComponent(query)}`);
            const data = await res.json();

            if (data.success && data.clientes && data.clientes.length > 0) {
                let html = '';
                data.clientes.forEach(c => {
                    const saldo = parseFloat(c.saldo_deudor || 0);
                    const limite = parseFloat(c.limite_credito || 0);
                    const saldoColor = saldo > 0 ? '#dc2626' : '#10b981';

                    html += `
                        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="color: var(--primary); font-size: 0.95rem; display: block;">${c.nombre}</strong>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    Identificación: ${c.identificacion || '-'} | Tel: ${c.telefono || '-'}
                                </div>
                            </div>
                            <div style="text-align: right; display: flex; align-items: center; gap: 0.75rem;">
                                <div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Saldo: <strong style="color: ${saldoColor}">$${saldo.toFixed(2)}</strong></div>
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">Límite: $${limite > 0 ? '$' + limite.toFixed(2) : 'Sin límite'}</div>
                                </div>
                                <button type="button" class="btn-modern btn-primary" style="padding: 0.3rem 0.65rem; font-size: 0.78rem;" onclick='seleccionarClientePos(${JSON.stringify(c)})'>
                                    Seleccionar
                                </button>
                            </div>
                        </div>
                    `;
                });
                resBox.innerHTML = html;
            } else {
                resBox.innerHTML = `
                    <div style="text-align: center; color: var(--text-muted); padding: 2rem;">
                        No se encontraron clientes activos con "${query}".
                    </div>
                `;
            }
        } catch (e) {
            console.error('Error buscando cliente:', e);
        }
    }, 200);
}

function seleccionarClientePos(cliente) {
    currentPosCliente = cliente;
    const headerName = document.getElementById('headerClienteNombre');
    const checkoutName = document.getElementById('checkoutClienteNombre');

    if (cliente) {
        if (headerName) headerName.innerText = 'Cliente: ' + cliente.nombre;
        if (checkoutName) checkoutName.innerText = cliente.nombre;
    } else {
        if (headerName) headerName.innerText = 'Cliente: Contado';
        if (checkoutName) checkoutName.innerText = 'Cliente Contado';
    }

    closeSeleccionarClienteModal();

    if (document.getElementById('checkoutModal') && document.getElementById('checkoutModal').style.display === 'flex') {
        if (currentTipoVenta === 'credito') {
            setTipoVenta('credito');
        }
    }
}

function setTipoVenta(tipo) {
    currentTipoVenta = tipo;

    const btnNormal = document.getElementById('btnTipoVentaNormal');
    const btnCredito = document.getElementById('btnTipoVentaCredito');
    const contadoSec = document.getElementById('checkoutContadoSection');
    const creditoInfo = document.getElementById('checkoutCreditoInfo');
    const btnConfirm = document.getElementById('btnConfirmCheckout');

    if (!btnNormal || !btnCredito) return;

    if (tipo === 'credito') {
        if (!currentPosCliente) {
            alert('Para realizar una venta a crédito, primero debes seleccionar un cliente.');
            openSeleccionarClienteModal();
            return;
        }

        btnNormal.style.background = 'white';
        btnNormal.style.borderColor = 'var(--border-color)';
        btnNormal.style.color = 'var(--text-main)';

        btnCredito.style.background = '#d97706';
        btnCredito.style.borderColor = '#d97706';
        btnCredito.style.color = 'white';

        if (contadoSec) contadoSec.style.display = 'none';
        if (creditoInfo) creditoInfo.style.display = 'block';

        const saldoActual = parseFloat(currentPosCliente.saldo_deudor || 0);
        const limite = parseFloat(currentPosCliente.limite_credito || 0);
        const disponible = limite > 0 ? Math.max(0, limite - saldoActual) : 'Sin límite';

        const saldoEl = document.getElementById('creditoSaldoActual');
        const limiteEl = document.getElementById('creditoLimiteDisponible');
        if (saldoEl) saldoEl.innerText = '$' + saldoActual.toFixed(2);
        if (limiteEl) limiteEl.innerText = typeof disponible === 'number' ? '$' + disponible.toFixed(2) : disponible;

        if (btnConfirm) {
            btnConfirm.disabled = false;
            btnConfirm.style.opacity = '1';
        }
    } else {
        btnNormal.style.background = '#059669';
        btnNormal.style.borderColor = '#059669';
        btnNormal.style.color = 'white';

        btnCredito.style.background = 'white';
        btnCredito.style.borderColor = 'var(--border-color)';
        btnCredito.style.color = 'var(--text-main)';

        if (contadoSec) contadoSec.style.display = 'block';
        if (creditoInfo) creditoInfo.style.display = 'none';

        calculateVuelto();
    }
}

function openCrearClienteRapidoModal() {
    closeSeleccionarClienteModal();
    const modal = document.getElementById('modalCrearClienteRapido');
    if (modal) {
        modal.style.display = 'flex';
        document.getElementById('rapidoClienteNombre').value = '';
        document.getElementById('rapidoClienteIdentificacion').value = '';
        document.getElementById('rapidoClienteTelefono').value = '';
        document.getElementById('rapidoClienteLimite').value = '0.00';
        setTimeout(() => document.getElementById('rapidoClienteNombre').focus(), 100);
    }
}

function closeCrearClienteRapidoModal() {
    const modal = document.getElementById('modalCrearClienteRapido');
    if (modal) modal.style.display = 'none';
}

async function ejecutarGuardarClienteRapido() {
    const nombreInput = document.getElementById('rapidoClienteNombre');
    const nombre = nombreInput ? nombreInput.value.trim() : '';
    if (!nombre) {
        alert('Por favor ingresa el nombre del cliente.');
        return;
    }

    try {
        const response = await fetch('/clientes/api/rapido', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                nombre: nombre,
                identificacion: document.getElementById('rapidoClienteIdentificacion').value.trim(),
                telefono: document.getElementById('rapidoClienteTelefono').value.trim(),
                limite_credito: parseFloat(document.getElementById('rapidoClienteLimite').value) || 0
            })
        });

        const data = await response.json();
        if (data.success && data.cliente) {
            seleccionarClientePos(data.cliente);
            closeCrearClienteRapidoModal();
        } else {
            alert(data.message || 'Error al crear cliente.');
        }
    } catch (e) {
        console.error('Error al guardar cliente rápido:', e);
        alert('Ocurrió un error de conexión al guardar el cliente.');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    tbodyNormal = document.getElementById('ticket-body-normal');
    tbodyTactil = document.getElementById('ticket-body-tactil');
    labelTotalNormal = document.getElementById('ticket-total-normal');
    labelTotalTactil = document.getElementById('ticket-total-tactil');
    labelStatsNormal = document.getElementById('ticket-stats-normal');
    
    // Inicializar buscador en Venta Normal si existe
    const searchInput = document.getElementById('search-articulo');
    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const isSmart = handleSmartBarcodeScan(e.target.value);
                if (isSmart) {
                    e.target.value = '';
                    renderSearchResults('');
                } else {
                    addSelectedToCart();
                }
            }
        });
        searchInput.addEventListener('keyup', (e) => {
            if (e.key !== 'Enter') {
                renderSearchResults(e.target.value);
            }
        });
        // Render inicial vacío
        renderSearchResults('');
    }
    
    // Inicializar lector en Venta Táctil si existe
    const searchTactil = document.getElementById('search-tactil');
    if (searchTactil) {
        searchTactil.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const rawVal = e.target.value;
                if (!rawVal || rawVal.trim() === '') return;
                
                const isSmart = handleSmartBarcodeScan(rawVal);
                if (isSmart) {
                    e.target.value = '';
                    filterTactilCatalog();
                } else {
                    // Si no es inteligente, buscar coincidencia exacta
                    const cleanVal = rawVal.trim().toLowerCase();
                    const exactMatch = windowArticulos.find(a => 
                        (a.codigo && String(a.codigo).toLowerCase() === cleanVal) || 
                        (a.codigo_cliente && String(a.codigo_cliente).toLowerCase() === cleanVal)
                    );
                    
                    if (exactMatch) {
                        e.target.value = '';
                        filterTactilCatalog();
                        // Abrimos el modal de báscula para que confirmen cantidad/peso
                        openScaleModal(exactMatch);
                    }
                }
            }
        });
    }
    
    renderCart();
});

// === BUSCADOR (VENTA NORMAL) ===
let selectedSearchIndex = -1;
let currentSearchResults = [];

function renderSearchResults(query) {
    const tbodySearch = document.getElementById('search-results-body');
    if (!tbodySearch) return;
    
    query = query.toLowerCase().trim();
    if (query === '') {
        tbodySearch.innerHTML = '';
        currentSearchResults = [];
        return;
    }
    
    // Si el texto ingresado parece un código inteligente (GS1 o local), extraer la base
    const cleanCode = query.replace(/[()\-\s]/g, '');
    let gtinMatch = cleanCode.match(/01(\d{14})/);
    let weightMatch = cleanCode.match(/(320[0-5]|310[0-5])(\d{6})/);
    
    if (gtinMatch && weightMatch && cleanCode.length >= 24) {
        query = gtinMatch[1]; // Extraer el GTIN base
    } else if ((cleanCode.length === 11 || cleanCode.length === 12) && /^\d{11,12}$/.test(cleanCode)) {
        query = cleanCode.substring(0, 6);
    } else if (cleanCode.length === 13 && /^2\d{12}$/.test(cleanCode)) {
        query = cleanCode.substring(1, 6);
    }
    
    currentSearchResults = windowArticulos.filter(art => {
        const queryLower = query;
        const codigo = art.codigo ? String(art.codigo).toLowerCase() : '';
        const codigo_cliente = art.codigo_cliente ? String(art.codigo_cliente).toLowerCase() : '';
        const descripcion = art.descripcion ? String(art.descripcion).toLowerCase() : '';
        
        return codigo.includes(queryLower) || 
               codigo_cliente.includes(queryLower) || 
               descripcion.includes(queryLower);
    });
    
    // Auto-seleccionar el primer resultado por defecto
    if (currentSearchResults.length > 0 && selectedSearchIndex === -1) {
        selectedSearchIndex = 0;
    }
    
    let html = '';
    currentSearchResults.forEach((art, index) => {
        const isSelected = index === selectedSearchIndex;
        const bgClass = isSelected ? 'background: #fef08a;' : '';
        
        html += `
            <tr style="border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background 0.2s; ${bgClass}" onclick="selectSearchResult(${index})">
                <td style="padding: 0.5rem 0.75rem;">${art.codigo || '-'}</td>
                <td style="padding: 0.5rem 0.75rem; font-weight: 500;">
                    ${art.descripcion}
                    ${(art.stock !== null && art.stock !== '') ? `<div style="font-size: 0.8rem; color: ${(getAvailableStock(art.id, art.stock) <= 0 ? '#ef4444' : '#10b981')}; font-weight: 600;">Stock: ${getAvailableStock(art.id, art.stock)} ${(windowSettings.unidad_peso || 'kg').toUpperCase()}</div>` : ''}
                </td>
                <td style="padding: 0.5rem 0.75rem; text-align: right; color: var(--primary); font-weight: 600;">$${parseFloat(art.pvp).toFixed(2)}</td>
                <td style="padding: 0.25rem 0.75rem;">
                    <input type="number" id="qty-search-${index}" class="input-modern" style="padding: 0.4rem; text-align: center; background: ${isSelected ? '#fef08a' : '#f8fafc'}; border: 1px solid var(--border-color);" value="1" onclick="event.stopPropagation()">
                </td>
            </tr>
        `;
    });
    
    tbodySearch.innerHTML = html;
}

function selectSearchResult(index) {
    selectedSearchIndex = index;
    // Volver a renderizar para resaltar
    const query = document.getElementById('search-articulo').value;
    renderSearchResults(query);
}

function addSelectedToCart() {
    if (selectedSearchIndex >= 0 && selectedSearchIndex < currentSearchResults.length) {
        const art = currentSearchResults[selectedSearchIndex];
        const qtyInput = document.getElementById(`qty-search-${selectedSearchIndex}`);
        let qty = 1;
        if (qtyInput) qty = parseFloat(qtyInput.value) || 1; // Cambiado a parseFloat para aceptar decimales si se ingresan manualmente
        
        const added = addToCart(art, qty);
        
        if (added) {
            // Limpiar búsqueda
            document.getElementById('search-articulo').value = '';
            selectedSearchIndex = -1; // Reset selection
            renderSearchResults('');
        }
    }
}

// === ANALIZADOR DE CÓDIGOS DE BARRAS INTELIGENTE ===
function handleSmartBarcodeScan(rawBarcode) {
    if (!rawBarcode || rawBarcode.trim() === '') return false;
    const barcode = rawBarcode.trim();
    let parsedSku = null;
    let parsedWeight = null;

    // Remover paréntesis, guiones y espacios (por si el código se escribe a mano con formato)
    const cleanCode = barcode.replace(/[()\-\s]/g, '');
    
    // 1. Detección de Códigos GS1-128 (Proveedor Mayorista)
    let gtinMatch = cleanCode.match(/01(\d{14})/);
    // 320x: Libras, 310x: Kilogramos. x = cantidad de decimales (0-5)
    let weightMatch = cleanCode.match(/(320[0-5]|310[0-5])(\d{6})/);

    if (gtinMatch && weightMatch && cleanCode.length >= 24) {
        parsedSku = gtinMatch[1]; // 14 dígitos del GTIN
        let ai = weightMatch[1];
        let weightStr = weightMatch[2];
        
        // Determinar decimales según el último dígito del IA (ej. 3201 = 1 decimal, 3202 = 2 decimales)
        let decimalPlaces = parseInt(ai.charAt(3));
        parsedWeight = parseInt(weightStr, 10) / Math.pow(10, decimalPlaces);

        // --- Conversión Automática de Unidades ---
        // 310x = Kilos (KG), 320x = Libras (LB)
        const systemUnit = (windowSettings.unidad_peso || 'lb').toLowerCase();
        const isKgInBarcode = ai.startsWith('310');

        if (isKgInBarcode && (systemUnit === 'lb' || systemUnit === 'lbs')) {
            // 1 kg = 2.20462 lbs
            parsedWeight = Math.round((parsedWeight * 2.20462) * 100) / 100;
        } else if (!isKgInBarcode && systemUnit === 'kg') {
            // 1 lb = 1 / 2.20462 kg
            parsedWeight = Math.round((parsedWeight / 2.20462) * 100) / 100;
        }
    } 
    // 2. Detección de Códigos de Báscula (11 dígitos local)
    else if (/^\d{11}$/.test(cleanCode)) {
        parsedSku = cleanCode.substring(0, 6);
        let weightStr = cleanCode.substring(6, 11);
        parsedWeight = parseInt(weightStr, 10) / 100;
    }
    // 2.1 Detección de Códigos de Báscula (12 dígitos estándar o local)
    else if (/^\d{12}$/.test(cleanCode)) {
        parsedSku = cleanCode.substring(0, 6);
        let weightStr = cleanCode.substring(6, 12);
        parsedWeight = parseInt(weightStr, 10) / 100; // Asumiendo 2 decimales para códigos de 12 dígitos (Ej. 000075 000041 = 0.41)
    }
    // 3. Detección de EAN-13 de Báscula (Empieza con 2, 13 dígitos)
    else if (/^2\d{12}$/.test(cleanCode)) {
        parsedSku = cleanCode.substring(1, 6); // 5 dígitos de código
        let weightStr = cleanCode.substring(6, 11); // 5 dígitos de peso
        parsedWeight = parseInt(weightStr, 10) / 1000; // Usualmente 3 decimales en EAN-13
    }

    // Si detectó un formato compuesto válido
    if (parsedSku !== null && parsedWeight !== null) {
        const skuInt = parseInt(parsedSku, 10);
        
        // Buscar el artículo exacto en la base de datos local
        let foundArt = windowArticulos.find(a => {
            return String(a.codigo) === parsedSku || 
                   parseInt(a.codigo, 10) === skuInt ||
                   String(a.codigo_cliente) === parsedSku ||
                   parseInt(a.codigo_cliente, 10) === skuInt;
        });

        // Si el artículo existe, abrir la báscula pre-llenada con el peso
        if (foundArt) {
            openScaleModal(foundArt, parsedWeight, cleanCode);
            return true;
        }
    }
    
    // Si no es un código inteligente o no se encontró el SKU base, retorna false para búsqueda normal
    return false; 
}

// === TPV TÁCTIL (FAMILIAS Y BOTONES DINÁMICOS) ===

function handleVaciarEliminarTactil() {
    if (currentSelectedIndex !== -1) {
        removeFromCart();
    } else {
        clearCart();
    }
}

function filterFamilia(familiaId, btnElement) {
    currentFamiliaId = familiaId;
    
    // 1. Resaltar la familia seleccionada (sombreado)
    const familiaBtns = document.querySelectorAll('.btn-tactil.familia');
    familiaBtns.forEach(btn => {
        // Restaurar estado base
        btn.style.boxShadow = 'none';
        btn.style.transform = 'scale(1)';
        btn.style.border = '2px solid var(--border-color)';
    });
    
    // Aplicar estilo al botón seleccionado
    if (btnElement) {
        btnElement.style.boxShadow = '0 4px 12px rgba(0, 112, 243, 0.4)';
        btnElement.style.transform = 'scale(0.98)';
        btnElement.style.border = '2px solid white';
    }
    
    // Aplicar el filtro completo (texto + familia)
    filterTactilCatalog();
}

function filterTactilCatalog() {
    const searchInput = document.getElementById('search-tactil');
    let query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const gridBtns = document.querySelectorAll('.tactil-grid .btn-tactil');
    
    // Si el texto ingresado parece un código inteligente (GS1 o local), extraer la base
    const cleanCode = query.replace(/[()\-\s]/g, '');
    let gtinMatch = cleanCode.match(/01(\d{14})/);
    let weightMatch = cleanCode.match(/(320[0-5]|310[0-5])(\d{6})/);
    
    if (gtinMatch && weightMatch && cleanCode.length >= 24) {
        query = gtinMatch[1]; // Extraer el GTIN base
    } else if ((cleanCode.length === 11 || cleanCode.length === 12) && /^\d{11,12}$/.test(cleanCode)) {
        query = cleanCode.substring(0, 6);
    } else if (cleanCode.length === 13 && /^2\d{12}$/.test(cleanCode)) {
        query = cleanCode.substring(1, 6);
    }
    
    gridBtns.forEach(btn => {
        const textContent = btn.textContent.toLowerCase();
        const artCodigo = (btn.getAttribute('data-codigo') || '').toLowerCase();
        const artCodigoCliente = (btn.getAttribute('data-codigo-cliente') || '').toLowerCase();
        const artFamiliaId = btn.getAttribute('data-familia-id');
        
        const matchFamilia = (currentFamiliaId === null || artFamiliaId == currentFamiliaId);
        const matchText = query === '' || 
                          textContent.includes(query) || 
                          artCodigo.includes(query) || 
                          artCodigoCliente.includes(query);
        
        if (matchFamilia && matchText) {
            btn.style.display = 'flex';
        } else {
            btn.style.display = 'none';
        }
    });
}


// === OPERACIONES BÁSICAS ===

function getEffectivePrice(articulo) {
    const usarImpuestos = parseInt(windowSettings.usar_impuestos ?? 1) === 1;
    const globalIvaEnabled = parseInt(windowSettings.iva_global_enabled ?? 1) === 1;
    const globalIvaRate = parseFloat(windowSettings.iva_global_rate ?? 21);

    let iva = 0;
    if (usarImpuestos) {
        iva = globalIvaEnabled ? globalIvaRate : (parseFloat(articulo.iva) || 0);
    }
    
    const precioSinIva = parseFloat(articulo.precio_sin_iva) || 0;
    return {
        iva: iva,
        pvp: precioSinIva * (1 + (iva / 100))
    };
}

let scaleArticle = null;
let scaleBarcodeScanned = null;

function openScaleModal(articulo, initialWeight = '', rawBarcode = '') {
    if (Array.isArray(windowArticulos) && articulo && articulo.id) {
        const fresh = windowArticulos.find(a => a.id == articulo.id);
        if (fresh) articulo = fresh;
    }
    scaleArticle = articulo;
    scaleBarcodeScanned = rawBarcode;
    const effective = getEffectivePrice(articulo);
    
    document.getElementById('scaleArticleName').innerText = articulo.descripcion;
    document.getElementById('scaleArticlePrice').innerText = '$' + effective.pvp.toFixed(2) + ' / ' + (windowSettings.unidad_peso || 'kg').toUpperCase();
    
    const stockEl = document.getElementById('scaleArticleStock');
    if (stockEl) {
        if (articulo.stock !== null && articulo.stock !== undefined && articulo.stock !== '') {
            const available = getAvailableStock(articulo.id, articulo.stock);
            stockEl.innerText = `Stock: ${available} ${(windowSettings.unidad_peso || 'kg').toUpperCase()}`;
            stockEl.style.color = available <= 0 ? '#ef4444' : '#10b981';
            stockEl.style.display = 'block';
        } else {
            stockEl.style.display = 'none';
        }
    }
    
    document.getElementById('scaleInput').value = initialWeight;
    updateScaleTotal();
    
    const scaleInput = document.getElementById('scaleInput');
    if (scaleInput && !scaleInput.dataset.hasEnterListener) {
        scaleInput.dataset.hasEnterListener = 'true';
        scaleInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                confirmScaleAdd();
            }
        });
    }

    document.getElementById('scaleModal').style.display = 'flex';
    setTimeout(() => {
        if (scaleInput) {
            scaleInput.focus();
            scaleInput.select();
        }
    }, 100);
}

function updateScaleTotal() {
    if (!scaleArticle) return;
    
    const input = document.getElementById('scaleInput');
    const quantity = parseFloat(input.value) || 0;
    
    const effective = getEffectivePrice(scaleArticle);
    const total = quantity * effective.pvp;
    
    document.getElementById('scaleTotal').innerText = 'Total: $' + total.toFixed(2);
}

function closeScaleModal() {
    document.getElementById('scaleModal').style.display = 'none';
    scaleArticle = null;
    scaleBarcodeScanned = null;
}

function confirmScaleAdd() {
    const input = document.getElementById('scaleInput');
    const quantity = parseFloat(input.value);
    
    if (isNaN(quantity) || quantity <= 0) {
        alert('Por favor, ingresa una cantidad válida mayor a 0.');
        input.focus();
        return;
    }
    
    if (scaleArticle) {
        const added = addToCart(scaleArticle, quantity, scaleBarcodeScanned);
        if (added) {
            closeScaleModal();
        }
    }
}

function addToCart(articulo, quantity = 1, rawBarcode = '') {
    const existingIndex = cart.findIndex(item => item.id === articulo.id);
    const currentCartQty = existingIndex !== -1 ? cart[existingIndex].cantidad : 0;
    const requestedQty = currentCartQty + quantity;

    // Validación de stock
    if (articulo.stock !== null && articulo.stock !== undefined && articulo.stock !== '') {
        const stockDisponible = parseFloat(articulo.stock);
        if (requestedQty > stockDisponible) {
            const unidad = windowSettings && windowSettings.unidad_peso ? windowSettings.unidad_peso.toUpperCase() : 'KG';
            showErrorModal(
                'Stock Insuficiente',
                `<strong>Stock disponible:</strong> ${stockDisponible} ${unidad}<br><strong style="color: #ef4444;">Cantidad solicitada:</strong> ${requestedQty} ${unidad}`
            );
            return false;
        }
    }

    const effective = getEffectivePrice(articulo);
    
    if (existingIndex !== -1) {
        cart[existingIndex].cantidad += quantity;
        if (rawBarcode) cart[existingIndex].codigo_escaneado = rawBarcode;
    } else {
        cart.push({
            id: articulo.id,
            codigo: articulo.codigo,
            descripcion: articulo.descripcion,
            precio: parseFloat(effective.pvp),
            iva_rate: effective.iva,
            cantidad: quantity,
            descuento_tipo: 'porcentaje',
            descuento_valor: 0,
            descuento: 0,
            codigo_escaneado: rawBarcode || '',
            articulo: articulo
        });
    }
    
    renderCart();
    return true;
}

function removeFromCart() {
    if (currentSelectedIndex >= 0 && currentSelectedIndex < cart.length) {
        cart.splice(currentSelectedIndex, 1);
        currentSelectedIndex = -1;
        renderCart();
    }
}

function clearCart() {
    cart = [];
    currentSelectedIndex = -1;
    renderCart();
}

function selectRow(index) {
    if (currentSelectedIndex === index) {
        currentSelectedIndex = -1; // Toggle off if clicking the same row
    } else {
        currentSelectedIndex = index;
    }
    renderCart();
}

function updateCartQuantity(index, newValue) {
    if (index >= 0 && index < cart.length) {
        let newQty = parseFloat(newValue);
        if (isNaN(newQty) || newQty <= 0) {
            cart.splice(index, 1);
            if (currentSelectedIndex === index) currentSelectedIndex = -1;
            renderCart();
            return;
        }

        const articulo = cart[index].articulo;

        if (articulo.stock !== null && articulo.stock !== undefined && articulo.stock !== '') {
            const stockDisponible = parseFloat(articulo.stock);
            
            let inCartOtherRows = 0;
            cart.forEach((it, i) => {
                if (i !== index && it.id === articulo.id) {
                    inCartOtherRows += parseFloat(it.cantidad);
                }
            });
            
            const totalRequested = inCartOtherRows + newQty;

            if (totalRequested > stockDisponible) {
                const unidad = windowSettings && windowSettings.unidad_peso ? windowSettings.unidad_peso.toUpperCase() : 'KG';
                showErrorModal(
                    'Stock Insuficiente',
                    `<strong>Stock disponible:</strong> ${stockDisponible} ${unidad}<br><strong style="color: #ef4444;">Cantidad solicitada en total:</strong> ${totalRequested} ${unidad}`
                );
                renderCart(); // Reset input
                return;
            }
        }

        cart[index].cantidad = newQty;
        renderCart();
    }
}

function updateCartPrice(index, newValue) {
    if (index >= 0 && index < cart.length) {
        let newPrice = parseFloat(newValue);
        if (isNaN(newPrice) || newPrice < 0) {
            renderCart(); // Reset input
            return;
        }

        cart[index].precio = newPrice;
        renderCart();
    }
}

function updateCartTotalRow(index, newValue) {
    if (index >= 0 && index < cart.length) {
        let newTotal = parseFloat(newValue);
        if (isNaN(newTotal) || newTotal < 0) {
            renderCart(); // Reset input
            return;
        }

        let item = cart[index];
        if (item.cantidad > 0) {
            if (item.descuento_tipo === 'fijo') {
                item.precio = (newTotal + (item.descuento || 0)) / item.cantidad;
            } else {
                let pct = parseFloat(item.descuento_valor) || 0;
                let factor = Math.max(0.01, 1 - (pct / 100));
                item.precio = newTotal / (item.cantidad * factor);
            }
        } else {
            item.precio = 0;
        }
        renderCart();
    }
}

// === RENDERIZADO ===

function renderCart() {
    let totalSuma = 0;
    let totalCantidades = 0;
    
    // Calcular totales primero
    cart.forEach((item) => {
        const subtotalBruto = item.precio * item.cantidad;
        let descuentoMonto = 0;
        if (item.descuento_tipo === 'fijo') {
            descuentoMonto = Math.min(subtotalBruto, parseFloat(item.descuento_valor) || 0);
        } else {
            const pct = parseFloat(item.descuento_valor) || 0;
            descuentoMonto = subtotalBruto * (pct / 100);
        }
        item.descuento = descuentoMonto;
        const totalFila = Math.max(0, subtotalBruto - descuentoMonto);

        totalSuma += totalFila;
        totalCantidades += item.cantidad;
    });

    // Renderizar Venta Normal si existe
    if (tbodyNormal) {
        let html = '';
        cart.forEach((item, index) => {
            const subtotalBruto = item.precio * item.cantidad;
            const totalFila = Math.max(0, subtotalBruto - (item.descuento || 0));
            const isSelected = index === currentSelectedIndex;
            const bgClass = isSelected ? 'background: #eff6ff; border-bottom: 1px solid #bfdbfe;' : 'border-bottom: 1px solid var(--border-color);';
            
            let descText = '0.00';
            if (item.descuento > 0) {
                descText = item.descuento_tipo === 'fijo' ? `-$${item.descuento.toFixed(2)}` : `${item.descuento_valor}%`;
            }

            html += `
                <tr style="${bgClass} cursor: pointer; transition: background 0.2s;" onclick="selectRow(${index})">
                    <td style="padding: 0.75rem;">${item.codigo || '-'}</td>
                    <td style="padding: 0.75rem; font-weight: 600;">${item.descripcion}</td>
                    <td style="padding: 0.25rem 0.75rem; text-align: center;">
                        <input type="number" step="any" min="0" value="${item.cantidad}" class="input-modern" style="width: 80px; text-align: center; padding: 0.25rem; font-weight: 600; background: ${isSelected ? '#bfdbfe' : '#f8fafc'};" onclick="event.stopPropagation()" onchange="updateCartQuantity(${index}, this.value)">
                    </td>
                    <td style="padding: 0.75rem; text-align: right;">
                        <input type="number" step="any" min="0" value="${item.precio.toFixed(2)}" class="input-modern" style="width: 80px; text-align: right; padding: 0.25rem; font-weight: 600; background: ${isSelected ? '#bfdbfe' : '#f8fafc'};" onclick="event.stopPropagation()" onchange="updateCartPrice(${index}, this.value)">
                    </td>
                    <td style="padding: 0.75rem; text-align: right; font-weight: 700; color: ${item.descuento > 0 ? '#d97706' : 'inherit'};">${descText}</td>
                    <td style="padding: 0.75rem; text-align: right;">${item.iva_rate}%</td>
                    <td style="padding: 0.25rem 0.75rem; text-align: right;">
                        <input type="number" step="any" min="0" value="${totalFila.toFixed(2)}" class="input-modern" style="width: 90px; text-align: right; padding: 0.25rem; font-weight: 700; color: var(--primary); background: ${isSelected ? '#bfdbfe' : '#f8fafc'};" onclick="event.stopPropagation()" onchange="updateCartTotalRow(${index}, this.value)">
                    </td>
                </tr>
            `;
        });
        tbodyNormal.innerHTML = html;
        
        if (labelTotalNormal) labelTotalNormal.innerText = totalSuma.toFixed(2);
        if (labelStatsNormal) labelStatsNormal.innerText = `${cart.length} líneas. ${cart.length} artículos. ${totalCantidades} uds.`;
    }
    
    // Renderizar TPV Táctil si existe
    if (tbodyTactil) {
        let html = '';
        cart.forEach((item, index) => {
            const subtotalBruto = item.precio * item.cantidad;
            const totalFila = Math.max(0, subtotalBruto - (item.descuento || 0));
            const isSelected = index === currentSelectedIndex;
            const bgClass = isSelected ? 'background: #eff6ff; border-bottom: 1px solid #bfdbfe;' : 'border-bottom: 1px solid var(--border-color);';
            
            let descBadge = '';
            if (item.descuento > 0) {
                let descLabel = item.descuento_tipo === 'fijo' 
                    ? `-$${item.descuento.toFixed(2)}` 
                    : `-${item.descuento_valor}% (-$${item.descuento.toFixed(2)})`;
                descBadge = `<div style="font-size: 0.75rem; color: #d97706; font-weight: 700; display: flex; align-items: center; gap: 0.25rem; margin-top: 0.2rem;"><i class="fa-solid fa-tag"></i> Desc: ${descLabel}</div>`;
            }

            html += `
                <tr style="${bgClass} cursor: pointer; transition: background 0.2s;" onclick="selectRow(${index})">
                    <td style="padding: 0.25rem 0.75rem; text-align: center;">
                        <input type="number" step="any" min="0" value="${item.cantidad}" class="input-modern" style="width: 80px; text-align: center; padding: 0.25rem; font-weight: 600; background: ${isSelected ? '#bfdbfe' : '#f8fafc'};" onclick="event.stopPropagation()" onchange="updateCartQuantity(${index}, this.value)">
                    </td>
                    <td style="padding: 0.75rem;">
                        <div style="font-weight: 600;">${item.descripcion}</div>
                        ${descBadge}
                    </td>
                    <td style="padding: 0.25rem 0.75rem; text-align: right;">
                        <input type="number" step="any" min="0" value="${item.precio.toFixed(2)}" class="input-modern" style="width: 80px; text-align: right; padding: 0.25rem; font-weight: 600; background: ${isSelected ? '#bfdbfe' : '#f8fafc'};" onclick="event.stopPropagation()" onchange="updateCartPrice(${index}, this.value)">
                    </td>
                    <td style="padding: 0.25rem 0.75rem; text-align: right;">
                        <input type="number" step="any" min="0" value="${totalFila.toFixed(2)}" class="input-modern" style="width: 90px; text-align: right; padding: 0.25rem; font-weight: 700; background: ${isSelected ? '#bfdbfe' : '#f8fafc'};" onclick="event.stopPropagation()" onchange="updateCartTotalRow(${index}, this.value)">
                    </td>
                </tr>
            `;
        });
        tbodyTactil.innerHTML = html;
        
        if (labelTotalTactil) labelTotalTactil.innerText = totalSuma.toFixed(2);
        
        // Botón Vaciar/Eliminar dinámico
        const btnVaciar = document.getElementById('btn-vaciar-tactil');
        if (btnVaciar) {
            if (currentSelectedIndex !== -1) {
                btnVaciar.innerHTML = '<i class="fa-solid fa-trash-can"></i> ELIMINAR';
                btnVaciar.style.background = 'linear-gradient(135deg, #f97316 0%, #ea580c 100%)';
            } else {
                btnVaciar.innerHTML = '<i class="fa-solid fa-trash"></i> VACIAR';
                btnVaciar.style.background = 'linear-gradient(135deg, #64748b 0%, #475569 100%)';
            }
        }
    }
    
    updateVisualStockDisplays();
}

// === COBRO Y TICKET MODALS ===

let currentTotalToPay = 0;
let currentVentaResponse = null;

function procesarCobro() {
    if (cart.length === 0) {
        alert('El carrito está vacío.');
        return;
    }

    let subtotal = 0;
    let total = 0;
    let impuestos = 0;

    cart.forEach(item => {
        const itemBruto = item.precio * item.cantidad;
        const itemDesc = item.descuento || 0;
        const itemTotal = Math.max(0, itemBruto - itemDesc);
        const itemSubtotal = itemTotal / (1 + (item.iva_rate / 100));
        subtotal += itemSubtotal;
        total += itemTotal;
        impuestos += (itemTotal - itemSubtotal);
    });

    currentTotalToPay = total;

    // Abrir Modal de Cobro
    document.getElementById('checkoutTotalDisplay').innerText = '$' + total.toFixed(2);
    document.getElementById('checkoutMontoRecibido').value = total.toFixed(2);
    document.getElementById('checkoutMetodoPago').value = 'efectivo';
    document.getElementById('checkoutVueltoDisplay').innerText = '$0.00';
    document.getElementById('checkoutVueltoDisplay').style.color = '#059669';
    
    setTipoVenta('normal');
    const nameEl = document.getElementById('checkoutClienteNombre');
    if (nameEl) {
        nameEl.innerText = currentPosCliente ? currentPosCliente.nombre : 'Cliente Contado';
    }

    document.getElementById('checkoutModal').style.display = 'flex';
    setTimeout(() => {
        const input = document.getElementById('checkoutMontoRecibido');
        if (!input.disabled) {
            input.focus();
            input.select();
        }
    }, 100);
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').style.display = 'none';
}

function handlePaymentMethodChange() {
    const metodo = document.getElementById('checkoutMetodoPago').value;
    const inputMonto = document.getElementById('checkoutMontoRecibido');
    
    if (metodo !== 'efectivo') {
        inputMonto.value = currentTotalToPay.toFixed(2);
        inputMonto.disabled = true;
        calculateVuelto();
    } else {
        inputMonto.disabled = false;
        inputMonto.focus();
        inputMonto.select();
        calculateVuelto();
    }
}

function calculateVuelto() {
    const input = document.getElementById('checkoutMontoRecibido');
    const vueltoDisplay = document.getElementById('checkoutVueltoDisplay');
    const btnConfirm = document.getElementById('btnConfirmCheckout');
    
    let monto = parseFloat(input.value);
    if (isNaN(monto)) monto = 0;

    const vuelto = monto - currentTotalToPay;

    if (vuelto < 0) {
        vueltoDisplay.innerText = 'Falta: $' + Math.abs(vuelto).toFixed(2);
        vueltoDisplay.style.color = '#dc2626'; // Rojo
        btnConfirm.disabled = true;
        btnConfirm.style.opacity = '0.5';
    } else {
        vueltoDisplay.innerText = '$' + vuelto.toFixed(2);
        vueltoDisplay.style.color = '#059669'; // Verde
        btnConfirm.disabled = false;
        btnConfirm.style.opacity = '1';
    }
}

let isProcessingSale = false;

async function confirmCheckout() {
    if (isProcessingSale) return;
    const btnConfirm = document.getElementById('btnConfirmCheckout');
    if (!btnConfirm || btnConfirm.disabled) return;

    isProcessingSale = true;
    btnConfirm.disabled = true;
    btnConfirm.style.pointerEvents = 'none';
    btnConfirm.style.opacity = '0.7';
    btnConfirm.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Registrando Venta...';

    let subtotal = 0;
    let total = 0;
    let totalDescuento = 0;
    let impuestos = 0;

    const items = cart.map(item => {
        const itemBruto = item.precio * item.cantidad;
        const itemDesc = item.descuento || 0;
        const itemTotal = Math.max(0, itemBruto - itemDesc);
        const itemSubtotal = itemTotal / (1 + (item.iva_rate / 100));
        
        subtotal += itemSubtotal;
        total += itemTotal;
        totalDescuento += itemDesc;
        impuestos += (itemTotal - itemSubtotal);

        return {
            articulo_id: item.id,
            codigo_escaneado: item.codigo_escaneado || '',
            cantidad: item.cantidad,
            precio: item.precio,
            descuento: itemDesc,
            subtotal: itemTotal
        };
    });

    const metodoPago = document.getElementById('checkoutMetodoPago').value;
    let montoRecibido = parseFloat(document.getElementById('checkoutMontoRecibido').value) || total;
    let vuelto = montoRecibido - total;
    if (vuelto < 0) vuelto = 0;

    try {
        const response = await fetch('/vender/cobrar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                total: total.toFixed(2),
                subtotal: subtotal.toFixed(2),
                descuento: totalDescuento.toFixed(2),
                impuestos: impuestos.toFixed(2),
                metodo_pago: currentTipoVenta === 'credito' ? 'credito' : metodoPago,
                monto_recibido: currentTipoVenta === 'credito' ? '0.00' : montoRecibido.toFixed(2),
                vuelto: currentTipoVenta === 'credito' ? '0.00' : vuelto.toFixed(2),
                cliente_id: currentPosCliente ? currentPosCliente.id : null,
                tipo_venta: currentTipoVenta,
                items: items
            })
        });

        const data = await response.json();
        
        if (data.success) {
            if (Array.isArray(data.articulos_actualizados)) {
                data.articulos_actualizados.forEach(art => {
                    if (Array.isArray(windowArticulos)) {
                        const found = windowArticulos.find(a => a.id == art.id);
                        if (found) {
                            found.stock = art.stock;
                        }
                    }

                    const el = document.getElementById(`tactil-stock-${art.id}`);
                    if (el) {
                        const unit = (windowSettings.unidad_peso || 'LB').toUpperCase();
                        el.innerText = `Stock: ${art.stock} ${unit}`;
                    }
                });
            }

            closeCheckoutModal();
            showTicketPreview(data.venta, items, montoRecibido, vuelto);

            // Reiniciar cliente tras cobrar
            seleccionarClientePos(null);
        } else {
            alert(data.message || 'Error al registrar la venta.');
        }
    } catch (error) {
        console.error(error);
        alert('Ocurrió un error en la conexión.');
    } finally {
        isProcessingSale = false;
        if (btnConfirm) {
            btnConfirm.disabled = false;
            btnConfirm.style.pointerEvents = '';
            btnConfirm.style.opacity = '';
            btnConfirm.innerHTML = 'Registrar Venta';
        }
    }
}

function showTicketPreview(venta, itemsPayload, montoRecibido, vuelto) {
    const ticketArea = document.getElementById('printableTicketArea');
    
    // Configuración empresa
    const empresaNombre = windowSettings.empresa_nombre || 'IntelliCarnic';
    const empresaRuc = windowSettings.empresa_ruc || '000000000';
    const empresaDireccion = windowSettings.empresa_direccion || 'Dirección de la empresa';
    const unidadPeso = windowSettings.unidad_peso || 'kg';

    const fecha = new Date().toLocaleString();
    const ticketId = venta.id.toString().padStart(6, '0');

    let clienteBanner = '';
    if (venta.tipo_venta === 'credito') {
        const clienteNom = (venta.cliente ? venta.cliente.nombre : (currentPosCliente ? currentPosCliente.nombre : 'CLIENTE CRÉDITO'));
        clienteBanner = `
            <div style="background: #fffbeb; border: 1.5px dashed #fcd34d; color: #d97706; font-weight: 800; padding: 5px; margin-top: 6px; font-size: 11px; text-align: center;">
                *** VENTA A CRÉDITO ***
            </div>
            <div style="margin-top: 4px; font-size: 11px; text-align: center; font-weight: bold;">
                CLIENTE: ${clienteNom}
            </div>
        `;
    } else if (currentPosCliente) {
        clienteBanner = `
            <div style="margin-top: 4px; font-size: 11px; text-align: center; font-weight: bold;">
                CLIENTE: ${currentPosCliente.nombre}
            </div>
        `;
    }

    let html = `
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="margin: 0; font-size: 18px;">${empresaNombre}</h2>
            <div>RUC/NIT: ${empresaRuc}</div>
            <div>${empresaDireccion}</div>
            <div style="margin-top: 5px;">Ticket #${ticketId}</div>
            <div>Fecha: ${fecha}</div>
            ${clienteBanner}
        </div>
        <hr style="border-top: 1px dashed black; margin: 10px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid black;">
                    <th style="text-align: left; padding: 2px 0;">CANT</th>
                    <th style="text-align: left; padding: 2px 0;">DESCRIPCIÓN</th>
                    <th style="text-align: right; padding: 2px 0;">IMPORTE</th>
                </tr>
            </thead>
            <tbody>
    `;

    let totalDescuentoVenta = 0;
    cart.forEach(item => {
        const itemBruto = item.precio * item.cantidad;
        const itemDesc = item.descuento || 0;
        totalDescuentoVenta += itemDesc;
        const importe = Math.max(0, itemBruto - itemDesc);

        let descText = '';
        if (itemDesc > 0) {
            let label = item.descuento_tipo === 'fijo' 
                ? `-$${itemDesc.toFixed(2)}` 
                : `-${item.descuento_valor}% (-$${itemDesc.toFixed(2)})`;
            descText = `<div style="font-size: 11px; color: #475569; font-style: italic;">↳ Desc: ${label}</div>`;
        }

        html += `
            <tr>
                <td style="text-align: left; padding: 2px 0; vertical-align: top;">${item.cantidad}</td>
                <td style="text-align: left; padding: 2px 0;">
                    <div>${item.descripcion}</div>
                    ${descText}
                </td>
                <td style="text-align: right; padding: 2px 0; vertical-align: top;">$${importe.toFixed(2)}</td>
            </tr>
        `;
    });

    const descFinal = parseFloat(venta.descuento) || totalDescuentoVenta;
    const subtotalBruto = parseFloat(venta.subtotal) + descFinal;

    html += `
            </tbody>
        </table>
        <hr style="border-top: 1px dashed black; margin: 10px 0;">
        <div style="display: flex; justify-content: space-between;">
            <span>SUBTOTAL:</span>
            <span>$${subtotalBruto.toFixed(2)}</span>
        </div>
        ${descFinal > 0 ? `
        <div style="display: flex; justify-content: space-between; font-weight: bold; color: #000;">
            <span>DESCUENTO:</span>
            <span>-$${descFinal.toFixed(2)}</span>
        </div>` : ''}
        <div style="display: flex; justify-content: space-between;">
            <span>IMPUESTOS:</span>
            <span>$${parseFloat(venta.impuestos).toFixed(2)}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; margin-top: 5px;">
            <span>TOTAL:</span>
            <span>$${parseFloat(venta.total).toFixed(2)}</span>
        </div>
        <hr style="border-top: 1px dashed black; margin: 10px 0;">
        <div style="display: flex; justify-content: space-between;">
            <span>MÉTODO DE PAGO:</span>
            <span style="text-transform: uppercase;">${venta.metodo_pago}</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>ENTREGADO:</span>
            <span>$${montoRecibido.toFixed(2)}</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>CAMBIO/VUELTO:</span>
            <span>$${vuelto.toFixed(2)}</span>
        </div>
        <div style="text-align: center; margin-top: 18px; margin-bottom: 5px;">
            <svg id="ticketBarcodePos" style="max-width: 100%;"></svg>
        </div>
        <div style="text-align: center; margin-top: 8px; font-size: 11px;">
            ¡Gracias por su compra!
        </div>
    `;

    ticketArea.innerHTML = html;
    document.getElementById('ticketModal').style.display = 'flex';

    setTimeout(() => {
        try {
            if (typeof JsBarcode === 'function') {
                JsBarcode("#ticketBarcodePos", ticketId, {
                    format: "CODE128",
                    width: 1.6,
                    height: 40,
                    displayValue: true,
                    fontSize: 12,
                    margin: 4
                });
            }
        } catch (e) {
            console.error("Error Barcode Ticket:", e);
        }
    }, 50);
}

function closeTicketModal() {
    document.getElementById('ticketModal').style.display = 'none';
    clearCart();
}

function printTicket() {
    window.print();
}

// === UTILIDADES ===
function showErrorModal(title, message) {
    const errorModal = document.getElementById('errorModal');
    if (errorModal) {
        document.getElementById('errorModalTitle').innerText = title;
        document.getElementById('errorModalMessage').innerHTML = message;
        errorModal.style.display = 'flex';
    } else {
        alert(title + "\n\n" + message.replace(/<br>/g, "\n").replace(/<\/?strong[^>]*>/g, ""));
    }
}

function getAvailableStock(articuloId, initialStock) {
    if (initialStock === null || initialStock === undefined || initialStock === '') return null;
    let inCart = 0;
    cart.forEach(item => {
        if (item.id === articuloId) {
            inCart += parseFloat(item.cantidad);
        }
    });
    const diff = parseFloat(initialStock) - inCart;
    return parseFloat(diff.toFixed(3));
}

function updateVisualStockDisplays() {
    const unidad = windowSettings && windowSettings.unidad_peso ? windowSettings.unidad_peso.toUpperCase() : 'KG';
    
    // Update Tactil Cards
    if (window.windowArticulos) {
        window.windowArticulos.forEach(art => {
            if (art.stock !== null && art.stock !== '') {
                const available = getAvailableStock(art.id, art.stock);
                const elTactil = document.getElementById(`tactil-stock-${art.id}`);
                if (elTactil) {
                    elTactil.innerText = `Stock: ${available} ${unidad}`;
                    elTactil.style.color = available <= 0 ? '#ef4444' : '#10b981';
                }
            }
        });
    }

    // Update Scale Modal if open
    if (scaleArticle) {
        const stockEl = document.getElementById('scaleArticleStock');
        if (stockEl && scaleArticle.stock !== null && scaleArticle.stock !== '') {
            const available = getAvailableStock(scaleArticle.id, scaleArticle.stock);
            stockEl.innerText = `Stock: ${available} ${unidad}`;
            stockEl.style.color = available <= 0 ? '#ef4444' : '#10b981';
        }
    }
    
    // Update Normal Search Results if active
    const searchInput = document.getElementById('search-articulo');
    if (searchInput && tbodyNormal && document.getElementById('search-results-body')) {
        // Just re-render search results to update stock
        renderSearchResults(searchInput.value);
    }
}

// === PRECIOS ESPECIALES / TARIFAS ===

function openPreciosEspecialesModal() {
    if (!cart || cart.length === 0) {
        showErrorModal('Sin productos en el ticket', 'Agrega primero un producto al ticket para poder consultar o aplicar sus precios especiales.');
        return;
    }

    // Si hay un solo producto en el ticket y no está seleccionado, seleccionarlo automáticamente
    if (currentSelectedIndex === -1 && cart.length === 1) {
        currentSelectedIndex = 0;
        renderCart();
    }

    if (currentSelectedIndex === -1 || !cart[currentSelectedIndex]) {
        showErrorModal('Selecciona un producto', 'Haz clic en una fila del ticket para seleccionar el producto al que deseas aplicar el precio especial.');
        return;
    }

    const item = cart[currentSelectedIndex];
    let fullArticulo = (Array.isArray(windowArticulos) ? windowArticulos.find(a => a.id == item.id) : null) || item.articulo || item;

    const modal = document.getElementById('modalPreciosEspeciales');
    const nombreEl = document.getElementById('peModalArticuloNombre');
    const codigoEl = document.getElementById('peModalArticuloCodigo');
    const listEl = document.getElementById('pePreciosList');

    if (!modal || !listEl) return;

    if (nombreEl) nombreEl.textContent = item.descripcion;
    if (codigoEl) codigoEl.textContent = `Código: ${item.codigo || '-'} | Cantidad: ${item.cantidad} ${(windowSettings.unidad_peso || 'kg').toUpperCase()}`;

    let effective = getEffectivePrice(fullArticulo);
    let precioBase = parseFloat(effective.pvp);
    let currentPrice = parseFloat(item.precio);

    let extraPrices = fullArticulo.precios_adicionales;
    if (typeof extraPrices === 'string') {
        try { extraPrices = JSON.parse(extraPrices); } catch(e) { extraPrices = []; }
    }
    if (!Array.isArray(extraPrices)) extraPrices = [];

    let html = '';

    // 1. Opción Precio Base / Regular
    const isBaseActive = Math.abs(currentPrice - precioBase) < 0.01;
    html += `
        <div class="pe-price-option" onclick="applySpecialPrice(${precioBase})" style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1rem; border-radius: 10px; border: 2px solid ${isBaseActive ? 'var(--primary)' : 'var(--border-color)'}; background: ${isBaseActive ? 'rgba(37,99,235,0.06)' : 'white'}; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='${isBaseActive ? 'var(--primary)' : 'var(--border-color)'}'">
            <div>
                <div style="font-weight: 700; color: ${isBaseActive ? 'var(--primary)' : 'var(--text-main)'}; font-size: 0.95rem;">
                    <i class="fa-solid fa-tag"></i> Precio Base / Regular
                </div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">Precio estándar de catálogo</div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 1.25rem; font-weight: 800; color: ${isBaseActive ? 'var(--primary)' : 'var(--text-main)'};">$${precioBase.toFixed(2)}</div>
                ${isBaseActive ? '<span class="badge badge-success" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">Activo</span>' : ''}
            </div>
        </div>
    `;

    // 2. Precios adicionales configurados
    if (extraPrices.length > 0) {
        extraPrices.forEach((ep) => {
            if (ep && (ep.nombre || ep.precio !== undefined)) {
                let priceVal = parseFloat(ep.precio) || 0;
                const isThisActive = Math.abs(currentPrice - priceVal) < 0.01;
                html += `
                    <div class="pe-price-option" onclick="applySpecialPrice(${priceVal})" style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1rem; border-radius: 10px; border: 2px solid ${isThisActive ? 'var(--primary)' : 'var(--border-color)'}; background: ${isThisActive ? 'rgba(37,99,235,0.06)' : 'white'}; cursor: pointer; transition: all 0.15s ease;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='${isThisActive ? 'var(--primary)' : 'var(--border-color)'}'">
                        <div>
                            <div style="font-weight: 700; color: ${isThisActive ? 'var(--primary)' : 'var(--text-main)'}; font-size: 0.95rem;">
                                <i class="fa-solid fa-star" style="color: #f59e0b;"></i> ${ep.nombre || 'Tarifa Especial'}
                            </div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Precio adicional configurado</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.25rem; font-weight: 800; color: ${isThisActive ? 'var(--primary)' : 'var(--text-main)'};">$${priceVal.toFixed(2)}</div>
                            ${isThisActive ? '<span class="badge badge-success" style="font-size: 0.7rem; padding: 0.15rem 0.45rem;">Activo</span>' : ''}
                        </div>
                    </div>
                `;
            }
        });
    } else {
        html += `
            <div style="padding: 1rem; border-radius: 8px; background: #f8fafc; border: 1px dashed var(--border-color); color: var(--text-muted); font-size: 0.85rem; text-align: center;">
                <i class="fa-solid fa-circle-info" style="color: #3b82f6; margin-right: 0.35rem;"></i>
                Este artículo no tiene precios adicionales configurados en el catálogo.
            </div>
        `;
    }

    listEl.innerHTML = html;
    modal.style.display = 'flex';
}

function closePreciosEspecialesModal() {
    const modal = document.getElementById('modalPreciosEspeciales');
    if (modal) modal.style.display = 'none';
}

function applySpecialPrice(price) {
    if (currentSelectedIndex !== -1 && cart[currentSelectedIndex]) {
        cart[currentSelectedIndex].precio = parseFloat(price);
        renderCart();
    }
    closePreciosEspecialesModal();
}

// === DESCUENTOS (PORCENTAJE / FIJO) ===

let currentTipoDescuento = 'porcentaje'; // 'porcentaje' o 'fijo'

function openDescuentoModal() {
    if (!cart || cart.length === 0) {
        showErrorModal('Sin productos en el ticket', 'Agrega primero un producto al ticket para poder aplicarle un descuento.');
        return;
    }

    if (currentSelectedIndex === -1 && cart.length === 1) {
        currentSelectedIndex = 0;
        renderCart();
    }

    if (currentSelectedIndex === -1 || !cart[currentSelectedIndex]) {
        showErrorModal('Selecciona un producto', 'Haz clic en una fila del ticket para seleccionar el producto al que deseas aplicar el descuento.');
        return;
    }

    const item = cart[currentSelectedIndex];
    const modal = document.getElementById('modalDescuento');
    if (!modal) return;

    const subtotalBruto = item.precio * item.cantidad;
    document.getElementById('descModalArticuloNombre').textContent = item.descripcion;
    document.getElementById('descModalArticuloInfo').textContent = `Cantidad: ${item.cantidad} | Precio U: $${item.precio.toFixed(2)} | Subtotal: $${subtotalBruto.toFixed(2)}`;

    // Cargar tipo y valor previo si existía
    currentTipoDescuento = item.descuento_tipo || 'porcentaje';
    setTipoDescuento(currentTipoDescuento);

    let val = item.descuento_valor !== undefined && item.descuento_valor > 0 ? item.descuento_valor : (item.descuento > 0 ? item.descuento : '');
    document.getElementById('inputValorDescuento').value = val || '';

    updateDescuentoPreview();
    modal.style.display = 'flex';
    setTimeout(() => {
        const inp = document.getElementById('inputValorDescuento');
        if (inp) { inp.focus(); inp.select(); }
    }, 100);
}

function closeDescuentoModal() {
    const modal = document.getElementById('modalDescuento');
    if (modal) modal.style.display = 'none';
}

function setTipoDescuento(tipo) {
    currentTipoDescuento = tipo;
    const btnPct = document.getElementById('btnTipoDescPorcentaje');
    const btnFijo = document.getElementById('btnTipoDescFijo');
    const lbl = document.getElementById('lblValorDescuento');
    const addon = document.getElementById('addonSimboloDescuento');
    const presets = document.getElementById('presetsDescuentoPorcentaje');

    if (tipo === 'porcentaje') {
        if (btnPct) { btnPct.style.background = '#d97706'; btnPct.style.borderColor = '#d97706'; btnPct.style.color = 'white'; }
        if (btnFijo) { btnFijo.style.background = 'white'; btnFijo.style.borderColor = 'var(--border-color)'; btnFijo.style.color = 'var(--text-main)'; }
        if (lbl) lbl.textContent = 'Porcentaje de Descuento (%)';
        if (addon) addon.textContent = '%';
        if (presets) presets.style.display = 'flex';
    } else {
        if (btnFijo) { btnFijo.style.background = '#d97706'; btnFijo.style.borderColor = '#d97706'; btnFijo.style.color = 'white'; }
        if (btnPct) { btnPct.style.background = 'white'; btnPct.style.borderColor = 'var(--border-color)'; btnPct.style.color = 'var(--text-main)'; }
        if (lbl) lbl.textContent = 'Monto Fijo de Descuento ($)';
        if (addon) addon.textContent = '$';
        if (presets) presets.style.display = 'none';
    }
    updateDescuentoPreview();
}

function setPresetDescuento(pct) {
    document.getElementById('inputValorDescuento').value = pct;
    updateDescuentoPreview();
}

function updateDescuentoPreview() {
    if (currentSelectedIndex === -1 || !cart[currentSelectedIndex]) return;
    const item = cart[currentSelectedIndex];
    const subtotalBruto = item.precio * item.cantidad;
    const inputVal = parseFloat(document.getElementById('inputValorDescuento').value) || 0;

    let descuentoMonto = 0;
    if (currentTipoDescuento === 'porcentaje') {
        descuentoMonto = subtotalBruto * (Math.min(100, Math.max(0, inputVal)) / 100);
    } else {
        descuentoMonto = Math.min(subtotalBruto, Math.max(0, inputVal));
    }

    const totalFinal = Math.max(0, subtotalBruto - descuentoMonto);

    const prevSub = document.getElementById('prevSubtotalOriginal');
    const prevDesc = document.getElementById('prevDescuentoMonto');
    const prevTot = document.getElementById('prevTotalFinal');

    if (prevSub) prevSub.textContent = '$' + subtotalBruto.toFixed(2);
    if (prevDesc) prevDesc.textContent = '-$' + descuentoMonto.toFixed(2);
    if (prevTot) prevTot.textContent = '$' + totalFinal.toFixed(2);
}

function confirmarDescuento() {
    if (currentSelectedIndex === -1 || !cart[currentSelectedIndex]) {
        closeDescuentoModal();
        return;
    }

    const item = cart[currentSelectedIndex];
    const subtotalBruto = item.precio * item.cantidad;
    const inputVal = parseFloat(document.getElementById('inputValorDescuento').value) || 0;

    if (inputVal <= 0) {
        quitarDescuentoSeleccionado();
        return;
    }

    item.descuento_tipo = currentTipoDescuento;
    item.descuento_valor = inputVal;
    
    if (currentTipoDescuento === 'porcentaje') {
        item.descuento = subtotalBruto * (Math.min(100, Math.max(0, inputVal)) / 100);
    } else {
        item.descuento = Math.min(subtotalBruto, Math.max(0, inputVal));
    }

    closeDescuentoModal();
    renderCart();
}

function quitarDescuentoSeleccionado() {
    if (currentSelectedIndex !== -1 && cart[currentSelectedIndex]) {
        cart[currentSelectedIndex].descuento_tipo = 'porcentaje';
        cart[currentSelectedIndex].descuento_valor = 0;
        cart[currentSelectedIndex].descuento = 0;
        renderCart();
    }
    closeDescuentoModal();
}

// === GESTIÓN DE DEVOLUCIONES ===

let currentDevolucionTicket = null;
let devolucionItemsState = [];

function openDevolucionModal() {
    const modal = document.getElementById('modalDevolucion');
    if (!modal) return;

    currentDevolucionTicket = null;
    devolucionItemsState = [];

    const input = document.getElementById('devolucionTicketInput');
    if (input) input.value = '';

    const content = document.getElementById('devolucionTicketContent');
    if (content) content.style.display = 'none';

    const loader = document.getElementById('devolucionLoader');
    if (loader) loader.style.display = 'none';

    const btnConfirm = document.getElementById('btnConfirmarDevolucion');
    if (btnConfirm) {
        btnConfirm.disabled = true;
        btnConfirm.style.opacity = '0.5';
    }

    modal.style.display = 'flex';
    setTimeout(() => {
        if (input) input.focus();
    }, 100);
}

function closeDevolucionModal() {
    const modal = document.getElementById('modalDevolucion');
    if (modal) modal.style.display = 'none';
}

async function buscarTicketDevolucion() {
    const input = document.getElementById('devolucionTicketInput');
    const rawVal = input ? input.value.trim() : '';

    if (!rawVal) {
        showErrorModal('Ingresa un Ticket', 'Por favor ingresa o escanea el número de ticket a consultar.');
        return;
    }

    const loader = document.getElementById('devolucionLoader');
    const content = document.getElementById('devolucionTicketContent');

    if (loader) loader.style.display = 'block';
    if (content) content.style.display = 'none';

    try {
        const response = await fetch(`/vender/ticket/${encodeURIComponent(rawVal)}`);
        const data = await response.json();

        if (loader) loader.style.display = 'none';

        if (data.success && data.ticket) {
            currentDevolucionTicket = data.ticket;
            renderDevolucionTicket(data.ticket);
            if (content) content.style.display = 'block';
        } else {
            showErrorModal('Ticket No Encontrado', data.message || 'No se encontró ninguna venta con ese folio.');
        }
    } catch (error) {
        console.error(error);
        if (loader) loader.style.display = 'none';
        showErrorModal('Error de Conexión', 'Ocurrió un error al consultar el ticket.');
    }
}

function renderDevolucionTicket(ticket) {
    document.getElementById('devInfoFolio').textContent = `#${ticket.folio}`;
    document.getElementById('devInfoFecha').textContent = ticket.fecha;
    document.getElementById('devInfoCajero').textContent = ticket.cajero;
    document.getElementById('devInfoPago').textContent = ticket.metodo_pago;
    document.getElementById('devInfoTotal').textContent = `$${parseFloat(ticket.total).toFixed(2)}`;

    const tbody = document.getElementById('devolucionItemsBody');
    if (!tbody) return;

    devolucionItemsState = ticket.items.map(item => ({
        venta_detalle_id: item.id,
        articulo_id: item.articulo_id,
        descripcion: item.descripcion,
        cantidad_vendida: item.cantidad_vendida,
        cantidad_devuelta: item.cantidad_devuelta,
        cantidad_disponible: item.cantidad_disponible,
        precio_efectivo: item.precio_efectivo,
        selected: item.cantidad_disponible > 0,
        cantidad_a_devolver: item.cantidad_disponible > 0 ? item.cantidad_disponible : 0,
    }));

    let html = '';
    const unit = (windowSettings && windowSettings.unidad_peso ? windowSettings.unidad_peso : 'kg').toUpperCase();

    devolucionItemsState.forEach((it, idx) => {
        const isDisponble = it.cantidad_disponible > 0;
        const subtotalReembolso = it.cantidad_a_devolver * it.precio_efectivo;

        html += `
            <tr style="border-bottom: 1px solid var(--border-color); background: ${isDisponble ? 'white' : '#f8fafc'}; opacity: ${isDisponble ? '1' : '0.6'};">
                <td style="text-align: center; padding: 0.5rem;">
                    <input type="checkbox" id="devCheck_${idx}" ${it.selected ? 'checked' : ''} ${!isDisponble ? 'disabled' : ''} onchange="toggleDevolucionItem(${idx}, this.checked)" style="transform: scale(1.1); cursor: pointer;">
                </td>
                <td style="padding: 0.5rem 0.75rem;">
                    <strong style="color: var(--text-main);">${it.descripcion}</strong>
                    ${!isDisponble ? '<div style="font-size: 0.75rem; color: #dc2626; font-weight: 700;">Totalmente devuelto</div>' : ''}
                </td>
                <td style="text-align: center; padding: 0.5rem; color: var(--text-muted); font-weight: 600;">
                    ${it.cantidad_vendida}
                </td>
                <td style="text-align: center; padding: 0.5rem; color: #dc2626; font-weight: 600;">
                    ${it.cantidad_devuelta}
                </td>
                <td style="text-align: center; padding: 0.5rem;">
                    <input type="number" step="any" min="0.001" max="${it.cantidad_disponible}" value="${it.cantidad_a_devolver}" ${!isDisponble ? 'disabled' : ''} class="input-modern" style="width: 85px; text-align: center; padding: 0.25rem; font-weight: 700; ${isDisponble ? 'background: #eff6ff;' : ''}" onchange="updateDevolucionCantidad(${idx}, this.value)" oninput="updateDevolucionCantidad(${idx}, this.value)">
                </td>
                <td style="text-align: right; padding: 0.5rem 0.75rem; font-weight: 600;">
                    $${it.precio_efectivo.toFixed(2)}
                </td>
                <td style="text-align: right; padding: 0.5rem 0.75rem; font-weight: 800; color: #dc2626;" id="devSubtotalRow_${idx}">
                    $${subtotalReembolso.toFixed(2)}
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    recalcularTotalDevolucion();
}

function toggleDevolucionItem(idx, isChecked) {
    if (devolucionItemsState[idx]) {
        devolucionItemsState[idx].selected = isChecked;
        recalcularTotalDevolucion();
    }
}

function toggleSelectAllDevolucion(isChecked) {
    devolucionItemsState.forEach((it, idx) => {
        if (it.cantidad_disponible > 0) {
            it.selected = isChecked;
            const el = document.getElementById(`devCheck_${idx}`);
            if (el) el.checked = isChecked;
        }
    });
    recalcularTotalDevolucion();
}

function updateDevolucionCantidad(idx, val) {
    if (!devolucionItemsState[idx]) return;
    const it = devolucionItemsState[idx];
    let num = parseFloat(val);

    if (isNaN(num) || num <= 0) {
        num = 0;
    } else if (num > it.cantidad_disponible) {
        num = it.cantidad_disponible;
    }

    it.cantidad_a_devolver = num;
    if (num > 0 && !it.selected) {
        it.selected = true;
        const el = document.getElementById(`devCheck_${idx}`);
        if (el) el.checked = true;
    }

    const rowSub = document.getElementById(`devSubtotalRow_${idx}`);
    if (rowSub) {
        const sub = num * it.precio_efectivo;
        rowSub.textContent = '$' + sub.toFixed(2);
    }

    recalcularTotalDevolucion();
}

function recalcularTotalDevolucion() {
    let total = 0;
    let selectedCount = 0;

    devolucionItemsState.forEach(it => {
        if (it.selected && it.cantidad_a_devolver > 0) {
            total += (it.cantidad_a_devolver * it.precio_efectivo);
            selectedCount++;
        }
    });

    const display = document.getElementById('devTotalReembolsoDisplay');
    const label = document.getElementById('devItemsCountLabel');
    const btnConfirm = document.getElementById('btnConfirmarDevolucion');

    if (display) display.textContent = '$' + total.toFixed(2);
    if (label) label.textContent = `${selectedCount} artículo(s) seleccionado(s)`;

    if (btnConfirm) {
        if (selectedCount > 0 && total > 0) {
            btnConfirm.disabled = false;
            btnConfirm.style.opacity = '1';
        } else {
            btnConfirm.disabled = true;
            btnConfirm.style.opacity = '0.5';
        }
    }
}

async function ejecutarProcesarDevolucion() {
    if (!currentDevolucionTicket) return;

    const itemsToReturn = [];
    devolucionItemsState.forEach(it => {
        if (it.selected && it.cantidad_a_devolver > 0) {
            itemsToReturn.push({
                venta_detalle_id: it.venta_detalle_id,
                cantidad_devolver: it.cantidad_a_devolver
            });
        }
    });

    if (itemsToReturn.length === 0) {
        showErrorModal('Sin artículos', 'Selecciona al menos un artículo con cantidad mayor a 0 para devolver.');
        return;
    }

    const metodoReembolso = document.getElementById('devMetodoReembolso').value;
    const motivo = document.getElementById('devMotivo').value.trim();
    const reingresarStock = document.getElementById('devReingresarStock').checked;

    const btnConfirm = document.getElementById('btnConfirmarDevolucion');
    if (btnConfirm) {
        btnConfirm.disabled = true;
        btnConfirm.innerText = 'Procesando Devolución...';
    }

    try {
        const response = await fetch('/vender/devolucion', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify({
                venta_id: currentDevolucionTicket.id,
                metodo_reembolso: metodoReembolso,
                motivo: motivo,
                reingresar_stock: reingresarStock,
                items: itemsToReturn
            })
        });

        const data = await response.json();

        if (data.success) {
            // 1. Actualizar stock visual en TPV si vino en la respuesta
            if (Array.isArray(data.articulos_actualizados)) {
                data.articulos_actualizados.forEach(art => {
                    if (Array.isArray(windowArticulos)) {
                        const found = windowArticulos.find(a => a.id == art.id);
                        if (found) found.stock = art.stock;
                    }
                    const el = document.getElementById(`tactil-stock-${art.id}`);
                    if (el) {
                        const unit = (windowSettings && windowSettings.unidad_peso ? windowSettings.unidad_peso : 'kg').toUpperCase();
                        el.innerText = `Stock: ${art.stock} ${unit}`;
                    }
                });
            }

            closeDevolucionModal();
            showDevolucionTicketPreview(data.devolucion);
        } else {
            showErrorModal('Error al Devolver', data.message || 'No se pudo procesar la devolución.');
        }
    } catch (error) {
        console.error(error);
        showErrorModal('Error de Conexión', 'Ocurrió un error al procesar la devolución.');
    } finally {
        if (btnConfirm) {
            btnConfirm.disabled = false;
            btnConfirm.innerHTML = '<i class="fa-solid fa-check"></i> Confirmar y Procesar Devolución';
        }
    }
}

function showDevolucionTicketPreview(devolucion) {
    const ticketArea = document.getElementById('printableTicketArea');
    if (!ticketArea) return;

    const empresaNombre = windowSettings.empresa_nombre || 'IntelliCarnic';
    const empresaRuc = windowSettings.empresa_ruc || '000000000';
    const empresaDireccion = windowSettings.empresa_direccion || 'Dirección';

    const fecha = new Date().toLocaleString();
    const folioDev = (devolucion.id || '1').toString().padStart(6, '0');
    const ticketOrig = (devolucion.venta_id || '').toString().padStart(6, '0');

    let html = `
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="margin: 0; font-size: 18px;">${empresaNombre}</h2>
            <div>RUC/NIT: ${empresaRuc}</div>
            <div>${empresaDireccion}</div>
            <div style="margin-top: 5px; font-weight: bold; color: #dc2626;">COMPROBANTE DE DEVOLUCIÓN</div>
            <div style="margin-top: 2px;">Devolución #${folioDev} | Ticket Orig: #${ticketOrig}</div>
            <div>Fecha: ${fecha}</div>
        </div>
        <hr style="border-top: 1px dashed black; margin: 10px 0;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid black;">
                    <th style="text-align: left; padding: 2px 0;">CANT</th>
                    <th style="text-align: left; padding: 2px 0;">ARTÍCULO DEVUELTO</th>
                    <th style="text-align: right; padding: 2px 0;">REEMBOLSO</th>
                </tr>
            </thead>
            <tbody>
    `;

    if (Array.isArray(devolucion.detalles)) {
        devolucion.detalles.forEach(d => {
            const nom = d.articulo ? d.articulo.descripcion : 'Artículo';
            html += `
                <tr>
                    <td style="text-align: left; padding: 2px 0; vertical-align: top;">${d.cantidad}</td>
                    <td style="text-align: left; padding: 2px 0;">${nom}</td>
                    <td style="text-align: right; padding: 2px 0; vertical-align: top;">-$${parseFloat(d.subtotal).toFixed(2)}</td>
                </tr>
            `;
        });
    }

    html += `
            </tbody>
        </table>
        <hr style="border-top: 1px dashed black; margin: 10px 0;">
        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 15px; color: #dc2626;">
            <span>TOTAL REEMBOLSADO:</span>
            <span>-$${parseFloat(devolucion.total_reembolsado).toFixed(2)}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 4px;">
            <span>MÉTODO REEMBOLSO:</span>
            <span style="text-transform: uppercase;">${devolucion.metodo_reembolso}</span>
        </div>
        ${devolucion.motivo ? `
        <div style="margin-top: 6px; font-size: 11px; color: #475569;">
            <strong>Motivo:</strong> ${devolucion.motivo}
        </div>` : ''}
        <div style="text-align: center; margin-top: 18px; margin-bottom: 5px;">
            <svg id="ticketBarcodeDevolucion" style="max-width: 100%;"></svg>
        </div>
        <div style="text-align: center; margin-top: 8px; font-size: 11px;">
            Devolución realizada conforme por el cliente
        </div>
    `;

    ticketArea.innerHTML = html;
    document.getElementById('ticketModal').style.display = 'flex';

    setTimeout(() => {
        try {
            if (typeof JsBarcode === 'function') {
                JsBarcode("#ticketBarcodeDevolucion", folioDev, {
                    format: "CODE128",
                    width: 1.6,
                    height: 40,
                    displayValue: true,
                    fontSize: 12,
                    margin: 4
                });
            }
        } catch (e) {
            console.error("Error Barcode Devolución:", e);
        }
    }, 50);
}



