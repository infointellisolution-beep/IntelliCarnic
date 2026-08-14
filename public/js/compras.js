document.addEventListener('DOMContentLoaded', () => {
    const scanInput = document.getElementById('input-scan-barcode');
    const scanFeedback = document.getElementById('scan-feedback');
    const tbody = document.getElementById('tbody-compra-items');
    const trEmpty = document.getElementById('tr-empty-compra');
    const itemsCountBadge = document.getElementById('items-count-badge');
    const labelTotal = document.getElementById('label-total-compra');
    const btnGuardar = document.getElementById('btn-guardar-compra');
    const selectProveedor = document.getElementById('select-proveedor');
    const inputProveedorNombre = document.getElementById('input-proveedor-nombre');
    
    const selectManual = document.getElementById('select-articulo-manual');
    const btnAddManual = document.getElementById('btn-add-manual');

    // Modal proveedor
    const modalProv = document.getElementById('modal-nuevo-proveedor');
    const btnOpenProv = document.getElementById('btn-nuevo-proveedor');
    const btnCloseProv = document.getElementById('btn-close-modal-proveedor');
    const btnCancelProv = document.getElementById('btn-cancel-modal-proveedor');
    const formProv = document.getElementById('form-quick-proveedor');

    let compraItems = [];

    const articulos = Array.isArray(window.windowArticulos) ? window.windowArticulos : [];
    const systemUnit = (window.unidadPeso || 'lb').toLowerCase();

    // Sincronizar select de proveedor con input de nombre
    if (selectProveedor && inputProveedorNombre) {
        selectProveedor.addEventListener('change', (e) => {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            if (selectedOpt && selectedOpt.dataset.nombre) {
                inputProveedorNombre.value = selectedOpt.dataset.nombre;
            }
        });
    }

    // Modal proveedor rápido
    if (btnOpenProv && modalProv) {
        btnOpenProv.addEventListener('click', () => modalProv.style.display = 'flex');
        [btnCloseProv, btnCancelProv].forEach(b => b?.addEventListener('click', () => modalProv.style.display = 'none'));

        formProv?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const nombre = document.getElementById('prov-nombre').value;
            const identificacion = document.getElementById('prov-id').value;
            const telefono = document.getElementById('prov-tel').value;

            try {
                const res = await fetch('/proveedores', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ nombre, identificacion, telefono })
                });
                const data = await res.json();

                if (data.success) {
                    const opt = document.createElement('option');
                    opt.value = data.proveedor.id;
                    opt.dataset.nombre = data.proveedor.nombre;
                    opt.innerText = `${data.proveedor.nombre} (${data.proveedor.identificacion || 'Sin ID'})`;
                    opt.selected = true;
                    selectProveedor.appendChild(opt);
                    inputProveedorNombre.value = data.proveedor.nombre;

                    modalProv.style.display = 'none';
                    formProv.reset();
                }
            } catch (err) {
                alert('Error al guardar el proveedor.');
            }
        });
    }

    // Prevenir submit al presionar Enter en el scanner
    scanInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            processBarcode(e.target.value);
        }
    });

    let scanDebounce = null;
    scanInput?.addEventListener('input', (e) => {
        clearTimeout(scanDebounce);
        scanDebounce = setTimeout(() => {
            const val = e.target.value;
            if (val && val.trim().length >= 10) {
                processBarcode(val);
            }
        }, 300);
    });

    function processBarcode(rawVal) {
        if (!rawVal || rawVal.trim() === '') return;
        const cleanCode = rawVal.trim().replace(/[()\-\s]/g, '');

        let parsedSku = null;
        let parsedWeight = null;
        let parsedLote = null;
        let parsedSerie = null;

        // 1. Detección GS1-128
        let gtinMatch = cleanCode.match(/01(\d{14})/);
        let weightMatch = cleanCode.match(/(320[0-5]|310[0-5])(\d{6})/);
        let loteMatch = cleanCode.match(/10([a-zA-Z0-9]+?)(?=21|310|320|$)/);
        let serieMatch = cleanCode.match(/21([a-zA-Z0-9]+?)(?=10|310|320|$)/);

        if (gtinMatch && weightMatch && cleanCode.length >= 24) {
            parsedSku = gtinMatch[1];
            let ai = weightMatch[1];
            let weightStr = weightMatch[2];
            let decimals = parseInt(ai.charAt(3));
            parsedWeight = parseInt(weightStr, 10) / Math.pow(10, decimals);

            // Conversión KG -> LB o LB -> KG
            const isKgInBarcode = ai.startsWith('310');
            if (isKgInBarcode && (systemUnit === 'lb' || systemUnit === 'lbs')) {
                parsedWeight = Math.round((parsedWeight * 2.20462) * 100) / 100;
            } else if (!isKgInBarcode && systemUnit === 'kg') {
                parsedWeight = Math.round((parsedWeight / 2.20462) * 100) / 100;
            }

            if (loteMatch) parsedLote = loteMatch[1];
            if (serieMatch) parsedSerie = serieMatch[1];
        }
        // 2. Báscula 11 dígitos
        else if (/^\d{11}$/.test(cleanCode)) {
            parsedSku = cleanCode.substring(0, 6);
            parsedWeight = parseInt(cleanCode.substring(6, 11), 10) / 100;
        }
        // 3. Báscula 12 dígitos
        else if (/^\d{12}$/.test(cleanCode)) {
            parsedSku = cleanCode.substring(0, 6);
            parsedWeight = parseInt(cleanCode.substring(6, 12), 10) / 100;
        }
        // 4. EAN-13 Báscula
        else if (/^2\d{12}$/.test(cleanCode)) {
            parsedSku = cleanCode.substring(1, 6);
            parsedWeight = parseInt(cleanCode.substring(6, 11), 10) / 1000;
        }
        // 5. Búsqueda simple por SKU exacto
        else {
            parsedSku = cleanCode;
            parsedWeight = 1.0;
        }

        if (parsedSku) {
            const skuInt = parseInt(parsedSku, 10);
            
            // Buscar artículo en el catálogo local
            let found = articulos.find(a => {
                return String(a.codigo) === parsedSku || 
                       parseInt(a.codigo, 10) === skuInt ||
                       String(a.codigo_cliente) === parsedSku ||
                       parseInt(a.codigo_cliente, 10) === skuInt ||
                       (parsedSku.length >= 6 && String(a.codigo_cliente) === parsedSku.slice(-6, -1));
            });

            if (found) {
                addItemToTable({
                    articulo_id: found.id,
                    codigo: found.codigo,
                    descripcion: found.descripcion,
                    codigo_escaneado: cleanCode,
                    lote: parsedLote || '',
                    serie: parsedSerie || '',
                    cantidad_peso: parsedWeight || 1.0,
                    costo_unitario: parseFloat(found.precio_sin_iva) || 0.00
                });

                showFeedback(`✓ ¡Escaneado! ${found.descripcion} (${parsedWeight} ${systemUnit.toUpperCase()})`, '#10b981');
                scanInput.value = '';
            } else {
                showFeedback(`⚠ Producto no encontrado para el código: ${parsedSku}`, '#ef4444');
            }
        }
    }

    function showFeedback(msg, color) {
        if (scanFeedback) {
            scanFeedback.innerText = msg;
            scanFeedback.style.color = color;
            setTimeout(() => {
                scanFeedback.innerText = '';
            }, 3500);
        }
    }

    // Agregar manual
    btnAddManual?.addEventListener('click', () => {
        const selectedId = selectManual.value;
        if (!selectedId) return;

        const opt = selectManual.options[selectManual.selectedIndex];
        const found = articulos.find(a => String(a.id) === String(selectedId));

        if (found) {
            addItemToTable({
                articulo_id: found.id,
                codigo: found.codigo,
                descripcion: found.descripcion,
                codigo_escaneado: '',
                lote: '',
                serie: '',
                cantidad_peso: 1.0,
                costo_unitario: parseFloat(found.precio_sin_iva) || 0.00
            });
            selectManual.value = '';
        }
    });

    function addItemToTable(item) {
        compraItems.push(item);
        renderTable();
    }

    function renderTable() {
        if (!tbody) return;

        if (compraItems.length === 0) {
            if (trEmpty) trEmpty.style.display = '';
            tbody.innerHTML = '';
            tbody.appendChild(trEmpty);
            if (btnGuardar) btnGuardar.disabled = true;
            if (itemsCountBadge) itemsCountBadge.innerText = '0 ítems en lista';
            if (labelTotal) labelTotal.innerText = '$0.00';
            return;
        }

        if (trEmpty) trEmpty.style.display = 'none';
        tbody.innerHTML = '';

        let grandTotal = 0;

        compraItems.forEach((item, index) => {
            const subtotal = roundMoney(item.cantidad_peso * item.costo_unitario);
            grandTotal += subtotal;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="font-weight: 700; color: var(--text-muted);">${index + 1}</td>
                <td>
                    <div style="font-weight: 600;">${item.descripcion}</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">SKU: ${item.codigo}</div>
                    <input type="hidden" name="detalles[${index}][articulo_id]" value="${item.articulo_id}">
                    <input type="hidden" name="detalles[${index}][codigo_escaneado]" value="${item.codigo_escaneado}">
                </td>
                <td>
                    <input type="text" name="detalles[${index}][lote]" class="input-modern" style="padding: 0.25rem 0.5rem; font-size: 0.85rem; font-family: monospace; width: 110px; margin-bottom: 0.2rem;" placeholder="Lote" value="${item.lote}">
                    <input type="text" name="detalles[${index}][serie]" class="input-modern" style="padding: 0.25rem 0.5rem; font-size: 0.85rem; font-family: monospace; width: 110px;" placeholder="Serie" value="${item.serie}">
                </td>
                <td>
                    <input type="number" step="0.001" min="0.001" name="detalles[${index}][cantidad_peso]" class="input-modern js-input-peso" data-index="${index}" style="font-weight: 700;" value="${item.cantidad_peso}">
                </td>
                <td>
                    <input type="number" step="0.01" min="0" name="detalles[${index}][costo_unitario]" class="input-modern js-input-costo" data-index="${index}" style="font-weight: 700;" value="${item.costo_unitario}">
                </td>
                <td style="font-weight: 700; font-size: 1.05rem; color: var(--accent);">
                    $<span class="js-row-subtotal">${subtotal.toFixed(2)}</span>
                </td>
                <td style="text-align: center;">
                    <button type="button" class="btn-modern btn-secondary js-btn-delete-row" data-index="${index}" style="padding: 0.35rem 0.6rem; color: #ef4444; border-color: #fca5a5;">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });

        // Event listeners parainputs de peso y costo
        document.querySelectorAll('.js-input-peso').forEach(input => {
            input.addEventListener('change', (e) => {
                const idx = e.target.dataset.index;
                const val = parseFloat(e.target.value) || 0;
                compraItems[idx].cantidad_peso = val;
                renderTable();
            });
        });

        document.querySelectorAll('.js-input-costo').forEach(input => {
            input.addEventListener('change', (e) => {
                const idx = e.target.dataset.index;
                const val = parseFloat(e.target.value) || 0;
                compraItems[idx].costo_unitario = val;
                renderTable();
            });
        });

        document.querySelectorAll('.js-btn-delete-row').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const idx = btn.dataset.index;
                compraItems.splice(idx, 1);
                renderTable();
            });
        });

        if (btnGuardar) btnGuardar.disabled = false;
        if (itemsCountBadge) itemsCountBadge.innerText = `${compraItems.length} ítems en lista`;
        if (labelTotal) labelTotal.innerText = `$${grandTotal.toFixed(2)}`;
    }

    function roundMoney(num) {
        return Math.round(num * 100) / 100;
    }
});
