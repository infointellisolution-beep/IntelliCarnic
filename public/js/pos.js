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
    } else if (cleanCode.length === 12 && /^\d{12}$/.test(cleanCode)) {
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
    } 
    // 2. Detección de Códigos de Báscula (12 dígitos estándar o local)
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
            openScaleModal(foundArt, parsedWeight);
            return true; // Retorna true para indicar que el código fue procesado como inteligente
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
    } else if (cleanCode.length === 12 && /^\d{12}$/.test(cleanCode)) {
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

function openScaleModal(articulo, initialWeight = '') {
    scaleArticle = articulo;
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
    
    document.getElementById('scaleModal').style.display = 'flex';
    setTimeout(() => document.getElementById('scaleInput').focus(), 100);
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
        const added = addToCart(scaleArticle, quantity);
        if (added) {
            closeScaleModal();
        }
    }
}

function addToCart(articulo, quantity = 1) {
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
    } else {
        cart.push({
            id: articulo.id,
            codigo: articulo.codigo,
            descripcion: articulo.descripcion,
            precio: parseFloat(effective.pvp),
            iva_rate: effective.iva,
            cantidad: quantity,
            descuento: 0,
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

// === RENDERIZADO ===

function renderCart() {
    let totalSuma = 0;
    let totalCantidades = 0;
    
    // Calcular totales primero
    cart.forEach((item) => {
        totalSuma += item.precio * item.cantidad * (1 - item.descuento / 100);
        totalCantidades += item.cantidad;
    });

    // Renderizar Venta Normal si existe
    if (tbodyNormal) {
        let html = '';
        cart.forEach((item, index) => {
            const totalFila = item.precio * item.cantidad * (1 - item.descuento / 100);
            const isSelected = index === currentSelectedIndex;
            const bgClass = isSelected ? 'background: #eff6ff; border-bottom: 1px solid #bfdbfe;' : 'border-bottom: 1px solid var(--border-color);';
            
            html += `
                <tr style="${bgClass} cursor: pointer; transition: background 0.2s;" onclick="selectRow(${index})">
                    <td style="padding: 0.75rem;">${item.codigo || '-'}</td>
                    <td style="padding: 0.75rem; font-weight: 600;">${item.descripcion}</td>
                    <td style="padding: 0.25rem 0.75rem; text-align: center;">
                        <input type="number" step="any" min="0" value="${item.cantidad}" class="input-modern" style="width: 80px; text-align: center; padding: 0.25rem; font-weight: 600; background: ${isSelected ? '#bfdbfe' : '#f8fafc'};" onclick="event.stopPropagation()" onchange="updateCartQuantity(${index}, this.value)">
                    </td>
                    <td style="padding: 0.75rem; text-align: right;">${item.precio.toFixed(2)}</td>
                    <td style="padding: 0.75rem; text-align: right;">${item.descuento.toFixed(2)}</td>
                    <td style="padding: 0.75rem; text-align: right;">${item.iva_rate}%</td>
                    <td style="padding: 0.75rem; text-align: right; font-weight: 700; color: var(--primary);">${totalFila.toFixed(2)}</td>
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
            const totalFila = item.precio * item.cantidad * (1 - item.descuento / 100);
            const isSelected = index === currentSelectedIndex;
            const bgClass = isSelected ? 'background: #eff6ff; border-bottom: 1px solid #bfdbfe;' : 'border-bottom: 1px solid var(--border-color);';
            
            html += `
                <tr style="${bgClass} cursor: pointer; transition: background 0.2s;" onclick="selectRow(${index})">
                    <td style="padding: 0.25rem 0.75rem; text-align: center;">
                        <input type="number" step="any" min="0" value="${item.cantidad}" class="input-modern" style="width: 80px; text-align: center; padding: 0.25rem; font-weight: 600; background: ${isSelected ? '#bfdbfe' : '#f8fafc'};" onclick="event.stopPropagation()" onchange="updateCartQuantity(${index}, this.value)">
                    </td>
                    <td style="padding: 0.75rem;">${item.descripcion}</td>
                    <td style="padding: 0.75rem; text-align: center;">${item.descuento.toFixed(2)}</td>
                    <td style="padding: 0.75rem; text-align: right; font-weight: 700;">${totalFila.toFixed(2)}</td>
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
        const itemTotal = item.precio * item.cantidad * (1 - item.descuento / 100);
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
    handlePaymentMethodChange(); // Asegurar estado correcto

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

async function confirmCheckout() {
    const btnConfirm = document.getElementById('btnConfirmCheckout');
    if (btnConfirm.disabled) return;

    btnConfirm.disabled = true;
    btnConfirm.innerText = 'Procesando...';

    let subtotal = 0;
    let total = 0;
    let impuestos = 0;

    const items = cart.map(item => {
        const itemTotal = item.precio * item.cantidad * (1 - item.descuento / 100);
        const itemSubtotal = itemTotal / (1 + (item.iva_rate / 100));
        
        subtotal += itemSubtotal;
        total += itemTotal;
        impuestos += (itemTotal - itemSubtotal);

        return {
            articulo_id: item.id,
            cantidad: item.cantidad,
            precio: item.precio,
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
                impuestos: impuestos.toFixed(2),
                metodo_pago: metodoPago,
                monto_recibido: montoRecibido.toFixed(2),
                vuelto: vuelto.toFixed(2),
                items: items
            })
        });

        const data = await response.json();
        
        if (data.success) {
            closeCheckoutModal();
            showTicketPreview(data.venta, items, montoRecibido, vuelto);
        } else {
            alert('Error al registrar la venta.');
        }
    } catch (error) {
        console.error(error);
        alert('Ocurrió un error en la conexión.');
    } finally {
        btnConfirm.disabled = false;
        btnConfirm.innerText = 'Registrar Venta';
    }
}

function showTicketPreview(venta, itemsPayload, montoRecibido, vuelto) {
    const ticketArea = document.getElementById('printableTicketArea');
    
    // Configuración empresa (puedes inyectar esto desde settings si lo tienes)
    const empresaNombre = windowSettings.empresa_nombre || 'IntelliCarnic';
    const empresaRuc = windowSettings.empresa_ruc || '000000000';
    const empresaDireccion = windowSettings.empresa_direccion || 'Dirección de la empresa';
    const unidadPeso = windowSettings.unidad_peso || 'kg';

    const fecha = new Date().toLocaleString();
    const ticketId = venta.id.toString().padStart(6, '0');

    let html = `
        <div style="text-align: center; margin-bottom: 15px;">
            <h2 style="margin: 0; font-size: 18px;">${empresaNombre}</h2>
            <div>RUC/NIT: ${empresaRuc}</div>
            <div>${empresaDireccion}</div>
            <div style="margin-top: 5px;">Ticket #${ticketId}</div>
            <div>Fecha: ${fecha}</div>
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

    cart.forEach(item => {
        const importe = item.precio * item.cantidad * (1 - item.descuento / 100);
        html += `
            <tr>
                <td style="text-align: left; padding: 2px 0; vertical-align: top;">${item.cantidad}</td>
                <td style="text-align: left; padding: 2px 0;">${item.descripcion}</td>
                <td style="text-align: right; padding: 2px 0; vertical-align: top;">$${importe.toFixed(2)}</td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
        <hr style="border-top: 1px dashed black; margin: 10px 0;">
        <div style="display: flex; justify-content: space-between;">
            <span>SUBTOTAL:</span>
            <span>$${parseFloat(venta.subtotal).toFixed(2)}</span>
        </div>
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
        <div style="text-align: center; margin-top: 20px;">
            ¡Gracias por su compra!
        </div>
    `;

    ticketArea.innerHTML = html;
    document.getElementById('ticketModal').style.display = 'flex';
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
