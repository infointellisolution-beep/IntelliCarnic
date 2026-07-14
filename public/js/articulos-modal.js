document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('articulo-modal');
    const modalTitle = document.getElementById('articulo-modal-title');
    const modalForm = document.getElementById('articulo-modal-form');
    const modalMethod = document.getElementById('articulo-modal-method');
    const modalId = document.getElementById('articulo-modal-id');
    const openCreateButtons = document.querySelectorAll('.js-articulo-create');
    const editButtons = document.querySelectorAll('.js-articulo-edit');
    const detailButtons = document.querySelectorAll('.js-articulo-details');
    const closeButtons = document.querySelectorAll('.js-articulo-modal-close');

    const familiaModal = document.getElementById('familia-modal');
    const familiaCreateButtons = document.querySelectorAll('.js-familia-create-open');
    const familiaCloseButtons = document.querySelectorAll('.js-familia-modal-close');

    const familiasListModal = document.getElementById('familias-list-modal');
    const familiasListButtons = document.querySelectorAll('.js-familias-list-open');
    const familiasListCloseButtons = document.querySelectorAll('.js-familias-list-close');

    const detailModal = document.getElementById('detalle-modal');
    const detailTitle = document.getElementById('detalle-modal-title');
    const detailCodigo = document.getElementById('detalle-codigo-chip');
    const detailEstado = document.getElementById('detalle-estado-chip');
    const detailCodigoCliente = document.getElementById('detalle-codigo-cliente');
    const detailDescripcion = document.getElementById('detalle-descripcion');
    const detailFamilia = document.getElementById('detalle-familia');
    const detailPrecio = document.getElementById('detalle-precio');
    const detailIva = document.getElementById('detalle-iva');
    const detailPvp = document.getElementById('detalle-pvp');
    const detailStock = document.getElementById('detalle-stock');
    const detailCloseButtons = document.querySelectorAll('.js-detalle-modal-close');

    const catalogoArticulos = Array.isArray(window.articulosCatalogo) ? window.articulosCatalogo : [];

    const stockModal = document.getElementById('stock-modal');
    const stockSearchInput = document.getElementById('stock-search-input');
    const stockResults = document.getElementById('stock-results');
    const stockArticuloId = document.getElementById('stock-articulo-id');
    const stockSelectedCard = document.getElementById('stock-selected-card');
    const stockSelectedName = document.getElementById('stock-selected-name');
    const stockSelectedMeta = document.getElementById('stock-selected-meta');
    const stockMovimiento = document.getElementById('stock-movimiento');
    const stockCantidad = document.getElementById('stock-cantidad');
    const stockMotivo = document.getElementById('stock-motivo');
    const openStockButtons = document.querySelectorAll('.js-stock-open');
    const closeStockButtons = document.querySelectorAll('.js-stock-modal-close');

    if (!modal || !modalForm || !modalTitle || !modalMethod || !modalId) {
        return;
    }

    // --- AUTO-LLENADO INTELIGENTE DE CÓDIGOS DE BARRAS ---
    const codigoInput = modalForm.querySelector('[name="codigo"]');
    const stockInput = modalForm.querySelector('[name="stock"]');
    
    if (codigoInput && stockInput) {
        // Escuchamos el evento input (funciona tanto si se teclea como si se usa un lector láser)
        codigoInput.addEventListener('input', (e) => {
            const rawBarcode = e.target.value;
            if (!rawBarcode || rawBarcode.trim() === '') return;
            const barcode = rawBarcode.trim();
            const cleanCode = barcode.replace(/[()]/g, '');
            
            let parsedSku = null;
            let parsedWeight = null;
            
            // 1. Detección GS1-128
            let gtinMatch = cleanCode.match(/01(\d{14})/);
            let weightMatch = cleanCode.match(/(3202|3102|3203|3103)(\d{6})/);
            
            if (gtinMatch && weightMatch && cleanCode.length >= 24) {
                parsedSku = gtinMatch[1];
                let ai = weightMatch[1];
                let weightStr = weightMatch[2];
                let decimalPlaces = parseInt(ai.charAt(3));
                parsedWeight = parseInt(weightStr, 10) / Math.pow(10, decimalPlaces);
                
                // --- Generación Automática de Código Interno (Báscula) ---
                const codigoClienteInput = modalForm.querySelector('[name="codigo_cliente"]');
                if (codigoClienteInput && parsedSku.length >= 6) {
                    codigoClienteInput.value = parsedSku.slice(-6); // Extraemos los últimos 6 dígitos
                    
                    const origBg = codigoClienteInput.style.backgroundColor;
                    codigoClienteInput.style.backgroundColor = '#dcfce7';
                    codigoClienteInput.style.borderColor = '#10b981';
                    codigoClienteInput.style.color = '#047857';
                    setTimeout(() => {
                        codigoClienteInput.style.backgroundColor = origBg;
                        codigoClienteInput.style.borderColor = '';
                        codigoClienteInput.style.color = '';
                    }, 1500);
                }
            }
            // 2. Detección Báscula 12 dígitos
            else if (/^\d{12}$/.test(cleanCode)) {
                parsedSku = cleanCode.substring(0, 6);
                let weightStr = cleanCode.substring(6, 12);
                parsedWeight = parseInt(weightStr, 10) / 100;
            }
            // 3. Detección EAN-13 Báscula
            else if (/^2\d{12}$/.test(cleanCode)) {
                parsedSku = cleanCode.substring(1, 6);
                let weightStr = cleanCode.substring(6, 11);
                parsedWeight = parseInt(weightStr, 10) / 1000;
            }
            
            if (parsedSku !== null && parsedWeight !== null) {
                // Limpiamos el código y movemos el peso al stock
                codigoInput.value = parsedSku;
                stockInput.value = parsedWeight;
                
                // Disparamos manualmente los eventos para que la previsualización se actualice
                codigoInput.dispatchEvent(new Event('input'));
                stockInput.dispatchEvent(new Event('input'));
                
                const codigoClienteInput = modalForm.querySelector('[name="codigo_cliente"]');
                if (codigoClienteInput) {
                    codigoClienteInput.dispatchEvent(new Event('input'));
                }
                
                // Feedback visual (parpadeo verde)
                const originalBg = codigoInput.style.backgroundColor;
                codigoInput.style.backgroundColor = '#dcfce7';
                codigoInput.style.borderColor = '#10b981';
                codigoInput.style.color = '#047857';
                
                stockInput.style.backgroundColor = '#dcfce7';
                stockInput.style.borderColor = '#10b981';
                stockInput.style.color = '#047857';
                
                setTimeout(() => {
                    codigoInput.style.backgroundColor = originalBg;
                    codigoInput.style.borderColor = '';
                    codigoInput.style.color = '';
                    
                    stockInput.style.backgroundColor = originalBg;
                    stockInput.style.borderColor = '';
                    stockInput.style.color = '';
                }, 1500);
            }
        });
    }
    // -----------------------------------------------------

    const openModal = () => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeModal = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    const openFamiliaModal = () => {
        if (!familiaModal) {
            return;
        }

        familiaModal.classList.add('is-open');
        familiaModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeFamiliaModal = () => {
        if (!familiaModal) {
            return;
        }

        familiaModal.classList.remove('is-open');
        familiaModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    const openFamiliasListModal = () => {
        if (!familiasListModal) {
            return;
        }

        familiasListModal.classList.add('is-open');
        familiasListModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeFamiliasListModal = () => {
        if (!familiasListModal) {
            return;
        }

        familiasListModal.classList.remove('is-open');
        familiasListModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    const openDetailModal = () => {
        if (!detailModal) {
            return;
        }

        detailModal.classList.add('is-open');
        detailModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    };

    const closeDetailModal = () => {
        if (!detailModal) {
            return;
        }

        detailModal.classList.remove('is-open');
        detailModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    const openStockModal = () => {
        if (!stockModal) {
            return;
        }

        stockModal.classList.add('is-open');
        stockModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        stockSearchInput?.focus();
    };

    const closeStockModal = () => {
        if (!stockModal) {
            return;
        }

        stockModal.classList.remove('is-open');
        stockModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    const setFieldValue = (name, value) => {
        const field = modalForm.querySelector(`[name="${name}"]`);

        if (!field) {
            return;
        }

        field.value = value ?? '';
    };

    const setCheckboxValue = (name, checked) => {
        const field = modalForm.querySelector(`[name="${name}"]`);

        if (!field) {
            return;
        }

        field.checked = Boolean(checked);
    };

    const resetToCreateMode = () => {
        modalTitle.textContent = 'Nuevo artículo';
        modalForm.action = openCreateButtons[0]?.dataset.modalAction || modalForm.action;
        modalId.value = '';
        modalMethod.value = '';
        modalMethod.disabled = true;

        ['codigo', 'codigo_cliente', 'familia_id', 'descripcion', 'precio_sin_iva', 'iva', 'pvp', 'stock', 'stock_minimo', 'estado'].forEach((fieldName) => {
            const field = modalForm.querySelector(`[name="${fieldName}"]`);

            if (!field) {
                return;
            }

            if (fieldName === 'iva') {
                field.value = 21;
                return;
            }

            if (fieldName === 'stock' || fieldName === 'stock_minimo') {
                field.value = 0;
                return;
            }

            if (fieldName === 'estado') {
                field.value = 'activo';
                return;
            }

            if (fieldName === 'codigo_cliente') {
                field.value = '';
                return;
            }

            field.value = '';
        });

        setCheckboxValue('aplica_iva', true);
    };

    openCreateButtons.forEach((button) => {
        button.addEventListener('click', () => {
            resetToCreateMode();
            openModal();
        });
    });

    editButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const articulo = JSON.parse(button.dataset.articulo || '{}');

            modalTitle.textContent = button.dataset.modalTitle || 'Editar artículo';
            modalForm.action = button.dataset.modalAction || modalForm.action;
            modalId.value = articulo.id || '';
            modalMethod.value = 'PUT';
            modalMethod.disabled = false;

            setFieldValue('codigo', articulo.codigo);
            setFieldValue('codigo_cliente', articulo.codigo_cliente);
            setFieldValue('familia_id', articulo.familia_id);
            setFieldValue('descripcion', articulo.descripcion);
            setFieldValue('precio_sin_iva', articulo.precio_sin_iva);
            setFieldValue('iva', articulo.iva ?? 21);
            setFieldValue('pvp', articulo.pvp);
            setFieldValue('stock', articulo.stock ?? 0);
            setFieldValue('stock_minimo', articulo.stock_minimo ?? 0);
            setFieldValue('estado', articulo.estado || 'activo');
            setCheckboxValue('aplica_iva', articulo.aplica_iva ?? true);

            openModal();
        });
    });

    familiaCreateButtons.forEach((button) => {
        button.addEventListener('click', openFamiliaModal);
    });

    familiasListButtons.forEach((button) => {
        button.addEventListener('click', openFamiliasListModal);
    });

    const printButtons = document.querySelectorAll('.js-articulo-print');

    // Función para imprimir etiqueta térmica
    printButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const articulo = JSON.parse(button.dataset.articulo || '{}');
            let cc = articulo.codigo_cliente;
            
            if (!cc || cc.length > 6 || !/^\d+$/.test(cc)) {
                alert('El artículo debe tener un código cliente de hasta 6 dígitos numéricos para imprimir la etiqueta de báscula.');
                return;
            }

            let padCc = cc.padStart(6, '0');
            let weight = parseFloat(articulo.stock) || 0;
            let weightInt = Math.round(weight * 100);
            let weightStr = weightInt.toString().padStart(6, '0');
            let generatedBarcode = padCc + weightStr;
            
            // Calculamos precio total
            let unitPrice = parseFloat(articulo.pvp) || 0;
            let totalPrice = unitPrice * weight;

            // Creamos un iframe oculto para imprimir
            const iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '-9999px';
            iframe.style.bottom = '-9999px';
            document.body.appendChild(iframe);

            const doc = iframe.contentWindow.document;
            doc.open();
            doc.write(`
                <html>
                <head>
                    <title>Imprimir Etiqueta</title>
                    <style>
                        @page { size: 58mm 40mm; margin: 0; }
                        body {
                            margin: 0;
                            padding: 2mm;
                            font-family: Arial, sans-serif;
                            width: 54mm;
                            height: 36mm;
                            box-sizing: border-box;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            justify-content: center;
                            text-align: center;
                        }
                        .title { font-size: 11px; font-weight: bold; margin-bottom: 2px; text-transform: uppercase; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
                        .details-grid {
                            display: grid;
                            grid-template-columns: 1fr 1fr 1fr;
                            width: 100%;
                            font-size: 9px;
                            margin-bottom: 4px;
                            border-top: 1px solid #000;
                            border-bottom: 1px solid #000;
                            padding: 2px 0;
                        }
                        .detail-col { display: flex; flex-direction: column; }
                        .label { font-size: 7px; text-transform: uppercase; }
                        .val { font-weight: bold; }
                        .barcode-container { margin-top: 2px; }
                        #barcode { width: 45mm; height: 12mm; margin: 0 auto; display: block; }
                    </style>
                </head>
                <body>
                    <div class="title">${articulo.descripcion}</div>
                    <div class="details-grid">
                        <div class="detail-col"><span class="label">Total</span><span class="val">$${totalPrice.toFixed(2)}</span></div>
                        <div class="detail-col"><span class="label">Peso</span><span class="val">${weight.toFixed(3)} lb</span></div>
                        <div class="detail-col"><span class="label">Precio Lb</span><span class="val">$${unitPrice.toFixed(2)}</span></div>
                    </div>
                    <div class="barcode-container">
                        <svg id="barcode"></svg>
                    </div>
                    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>
                    <script>
                        window.onload = function() {
                            if(typeof JsBarcode !== 'undefined') {
                                JsBarcode("#barcode", "${generatedBarcode}", {
                                    format: "EAN13", // Fallback to CODE128 si falla EAN13
                                    valid: function(valid){
                                        if(!valid) {
                                            JsBarcode("#barcode", "${generatedBarcode}", {
                                                format: "CODE128",
                                                displayValue: true,
                                                fontSize: 12,
                                                height: 40,
                                                margin: 0
                                            });
                                        }
                                    },
                                    displayValue: true,
                                    fontSize: 12,
                                    height: 40,
                                    margin: 0
                                });
                            }
                            setTimeout(() => {
                                window.print();
                            }, 500);
                        };
                    <\/script>
                </body>
                </html>
            `);
            doc.close();

            // Cleanup después de imprimir
            setTimeout(() => {
                document.body.removeChild(iframe);
            }, 5000);
        });
    });

    closeButtons.forEach((button) => button.addEventListener('click', closeModal));
    familiaCloseButtons.forEach((button) => button.addEventListener('click', closeFamiliaModal));
    familiasListCloseButtons.forEach((button) => button.addEventListener('click', closeFamiliasListModal));
    detailCloseButtons.forEach((button) => button.addEventListener('click', closeDetailModal));
    closeStockButtons.forEach((button) => button.addEventListener('click', closeStockModal));

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    if (familiaModal) {
        familiaModal.addEventListener('click', (event) => {
            if (event.target === familiaModal) {
                closeFamiliaModal();
            }
        });
    }

    if (familiasListModal) {
        familiasListModal.addEventListener('click', (event) => {
            if (event.target === familiasListModal) {
                closeFamiliasListModal();
            }
        });
    }

    if (detailModal) {
        detailModal.addEventListener('click', (event) => {
            if (event.target === detailModal) {
                closeDetailModal();
            }
        });
    }

    if (stockModal) {
        stockModal.addEventListener('click', (event) => {
            if (event.target === stockModal) {
                closeStockModal();
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        if (modal.classList.contains('is-open')) {
            closeModal();
        }

        if (familiaModal?.classList.contains('is-open')) {
            closeFamiliaModal();
        }

        if (familiasListModal?.classList.contains('is-open')) {
            closeFamiliasListModal();
        }

        if (detailModal?.classList.contains('is-open')) {
            closeDetailModal();
        }

        if (stockModal?.classList.contains('is-open')) {
            closeStockModal();
        }
    });

    if (modal.dataset.open === 'true') {
        openModal();
    }

    if (familiaModal?.dataset.open === 'true') {
        openFamiliaModal();
    }

    const detailImagenContainer = document.getElementById('detalle-imagen-container');
    const detailImagen = document.getElementById('detalle-imagen');

    if (detailModal && detailTitle && detailCodigo && detailEstado && detailDescripcion && detailFamilia && detailPrecio && detailIva && detailPvp && detailStock) {
        const basculaContainer = document.getElementById('detalle-codigo-bascula-container');
        const basculaValue = document.getElementById('detalle-codigo-bascula');
        const proveedorContainer = document.getElementById('detalle-codigo-proveedor-container');
        const proveedorValue = document.getElementById('detalle-codigo-proveedor');

        detailButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const articulo = JSON.parse(button.dataset.articuloDetalles || '{}');

                detailTitle.textContent = `Detalle de ${articulo.codigo || 'artículo'}`;
                detailCodigo.textContent = articulo.codigo || 'Sin código';
                detailEstado.textContent = (articulo.estado || 'activo').replace('_', ' ');
                detailCodigoCliente.textContent = articulo.codigo_cliente || 'Sin código';
                detailDescripcion.textContent = articulo.descripcion || 'Sin descripción';
                detailFamilia.textContent = articulo.familia_nombre || 'Sin familia';
                detailPrecio.textContent = `$${Number(articulo.precio_sin_iva || 0).toFixed(2)}`;
                detailIva.textContent = `${Number(articulo.iva || 0).toFixed(0)}%`;
                detailPvp.textContent = `$${Number(articulo.pvp || 0).toFixed(2)}`;
                detailStock.textContent = `${Number(articulo.stock ?? 0).toFixed(3)} ${window.unidadPeso || 'kg'}`;

                // --- Código Dinámico Báscula ---
                if (basculaContainer && basculaValue && articulo.codigo_cliente && articulo.codigo_cliente.length <= 6 && /^\d+$/.test(articulo.codigo_cliente)) {
                    let padCc = articulo.codigo_cliente.padStart(6, '0');
                    let weight = parseFloat(articulo.stock) || 0;
                    let weightInt = Math.round(weight * 100);
                    let weightStr = weightInt.toString().padStart(6, '0');
                    basculaValue.textContent = padCc + weightStr;
                    basculaContainer.style.display = 'block';
                } else if (basculaContainer) {
                    basculaContainer.style.display = 'none';
                }

                // --- Código Dinámico Proveedor (GS1-128) ---
                if (proveedorContainer && proveedorValue && articulo.codigo && articulo.codigo.length === 14 && /^\d+$/.test(articulo.codigo)) {
                    let weight = parseFloat(articulo.stock) || 0;
                    let weightInt = Math.round(weight * 100);
                    let weightStr = weightInt.toString().padStart(6, '0');
                    
                    proveedorValue.innerHTML = `<span style="opacity: 0.5;">(01)</span>${articulo.codigo}<span style="opacity: 0.5;">(3202)</span><strong>${weightStr}</strong>`;
                    proveedorContainer.style.display = 'block';
                } else if (proveedorContainer) {
                    proveedorContainer.style.display = 'none';
                }

                if (articulo.imagen_url && detailImagenContainer && detailImagen) {
                    detailImagen.src = articulo.imagen_url;
                    detailImagenContainer.style.display = 'block';
                } else if (detailImagenContainer && detailImagen) {
                    detailImagen.src = '';
                    detailImagenContainer.style.display = 'none';
                }

                openDetailModal();
            });
        });
    }

    if (stockModal && stockSearchInput && stockResults && stockArticuloId && stockSelectedCard && stockSelectedName && stockSelectedMeta && stockMovimiento && stockCantidad && stockMotivo) {
        const renderStockResults = (term = '') => {
            const normalizedTerm = term.trim().toLowerCase();
            const selectedId = stockArticuloId.value ? Number(stockArticuloId.value) : null;

            if (normalizedTerm.length < 2) {
                stockResults.innerHTML = '<div class="stock-empty"><strong>Escribe al menos 2 caracteres</strong> para buscar un producto.</div>';
                return;
            }

            const results = catalogoArticulos.filter((articulo) => {
                return [articulo.codigo, articulo.codigo_cliente, articulo.descripcion, articulo.familia_nombre]
                    .filter(Boolean)
                    .some((value) => String(value).toLowerCase().includes(normalizedTerm));
            });

            if (!results.length) {
                stockResults.innerHTML = '<div class="stock-empty">No se encontraron productos con ese filtro.</div>';
                return;
            }

            stockResults.innerHTML = results.map((articulo) => {
                const isSelected = selectedId === Number(articulo.id);

                return `
                    <button type="button" class="stock-result-item ${isSelected ? 'is-selected' : ''}" data-stock-articulo='${JSON.stringify(articulo).replace(/'/g, '&#39;')}'>
                        <div>
                            <div class="stock-result-title">${articulo.codigo} · ${articulo.descripcion}</div>
                            <div class="stock-result-meta">Código cliente: ${articulo.codigo_cliente || 'Sin código'}</div>
                            <div class="stock-result-meta">${articulo.familia_nombre || 'Sin familia'} · Peso actual: ${Number(articulo.stock || 0).toFixed(3)} ${window.unidadPeso || 'kg'}</div>
                        </div>
                        <span class="stock-result-badge ${articulo.stock <= 0 ? 'is-danger' : 'is-ok'}">${Number(articulo.stock || 0).toFixed(3)} ${window.unidadPeso || 'kg'}</span>
                    </button>
                `;
            }).join('');

            stockResults.querySelectorAll('.stock-result-item').forEach((button) => {
                button.addEventListener('click', () => {
                    const articulo = JSON.parse(button.dataset.stockArticulo || '{}');

                    stockArticuloId.value = articulo.id || '';
                    stockSelectedName.textContent = `${articulo.codigo} · ${articulo.descripcion}`;
                    stockSelectedMeta.textContent = `${articulo.familia_nombre || 'Sin familia'} · ${articulo.codigo_cliente || 'Sin código'} · Peso actual: ${Number(articulo.stock || 0).toFixed(3)} ${window.unidadPeso || 'kg'} · Estado: ${articulo.estado}`;
                    stockSelectedCard.classList.add('is-active');

                    stockResults.querySelectorAll('.stock-result-item').forEach((item) => item.classList.remove('is-selected'));
                    button.classList.add('is-selected');
                    updateStockBarcodePreview(articulo);
                });
            });
        };

        const updateStockBarcodePreview = (articulo) => {
            const barcodeDiv = document.getElementById('stock-selected-barcode');
            if (!barcodeDiv || !articulo) return;

            let cc = articulo.codigo_cliente;
            if (cc && cc.length > 0 && cc.length <= 6 && /^\d+$/.test(cc)) {
                let padCc = cc.padStart(6, '0');
                let mov = stockMovimiento.value;
                let val = parseFloat(stockCantidad.value) || 0;
                let currentStock = parseFloat(articulo.stock) || 0;
                let newStock = currentStock;

                if (val > 0) {
                    if (mov === 'sumar') newStock += val;
                    if (mov === 'restar') newStock -= val;
                }
                if (newStock < 0) newStock = 0;

                let weightInt = Math.round(newStock * 100);
                let weightStr = weightInt.toString().padStart(6, '0');

                barcodeDiv.innerHTML = `<i class="fa-solid fa-barcode"></i> Código Báscula Resultante: <strong>${padCc}${weightStr}</strong>`;
                barcodeDiv.style.display = 'block';
            } else {
                barcodeDiv.style.display = 'none';
            }
        };

        // Escuchar cambios en los inputs para actualizar en tiempo real
        stockCantidad.addEventListener('input', () => {
            const id = stockArticuloId.value;
            if (id) {
                const art = catalogoArticulos.find((a) => Number(a.id) === Number(id));
                if (art) updateStockBarcodePreview(art);
            }
        });
        stockMovimiento.addEventListener('change', () => {
            const id = stockArticuloId.value;
            if (id) {
                const art = catalogoArticulos.find((a) => Number(a.id) === Number(id));
                if (art) updateStockBarcodePreview(art);
            }
        });

        openStockButtons.forEach((button) => {
            button.addEventListener('click', () => {
                renderStockResults(stockSearchInput.value);
                openStockModal();
            });
        });

        stockSearchInput.addEventListener('input', () => {
            renderStockResults(stockSearchInput.value);
        });

        if (stockArticuloId.value) {
            const selectedArticulo = catalogoArticulos.find((articulo) => Number(articulo.id) === Number(stockArticuloId.value));

            if (selectedArticulo) {
                stockSelectedName.textContent = `${selectedArticulo.codigo} · ${selectedArticulo.descripcion}`;
                stockSelectedMeta.textContent = `${selectedArticulo.familia_nombre || 'Sin familia'} · ${selectedArticulo.codigo_cliente || 'Sin código'} · Peso actual: ${Number(selectedArticulo.stock || 0).toFixed(3)} ${window.unidadPeso || 'kg'} · Estado: ${selectedArticulo.estado}`;
                stockSelectedCard.classList.add('is-active');
            }
        }

        if (stockModal.dataset.open === 'true') {
            openStockModal();
        }
    }
});