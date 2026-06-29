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
        searchInput.addEventListener('keyup', (e) => {
            renderSearchResults(e.target.value);
        });
        // Render inicial vacío
        renderSearchResults('');
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
                <td style="padding: 0.5rem 0.75rem; font-weight: 500;">${art.descripcion}</td>
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
        if (qtyInput) qty = parseInt(qtyInput.value) || 1;
        
        addToCart(art, qty);
        
        // Limpiar búsqueda
        document.getElementById('search-articulo').value = '';
        selectedSearchIndex = -1; // Reset selection
        renderSearchResults('');
    }
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
    
    // 2. Filtrar artículos
    const gridBtns = document.querySelectorAll('.tactil-grid .btn-tactil');
    gridBtns.forEach(btn => {
        const artFamiliaId = btn.getAttribute('data-familia-id');
        
        // Si no hay familiaId seleccionado, o si coincide con el del botón, mostrar
        if (familiaId === null || artFamiliaId == familiaId) {
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

function addToCart(articulo, quantity = 1) {
    const existingIndex = cart.findIndex(item => item.id === articulo.id);
    
    const effective = getEffectivePrice(articulo);
    
    if (existingIndex !== -1) {
        cart[existingIndex].cantidad += quantity;
    } else {
        cart.push({
            id: articulo.id,
            codigo: articulo.codigo,
            descripcion: articulo.descripcion,
            precio: parseFloat(effective.pvp),
            iva_rate: effective.iva, // Store the effective IVA rate used
            cantidad: quantity,
            descuento: 0,
            articulo: articulo
        });
    }
    
    renderCart();
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
    currentSelectedIndex = index;
    renderCart();
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
                    <td style="padding: 0.75rem; text-align: center; font-weight: 600;">${item.cantidad}</td>
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
                    <td style="padding: 0.75rem; font-weight: 600; text-align:center;">${item.cantidad}</td>
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
}
