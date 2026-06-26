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

        ['codigo', 'codigo_cliente', 'familia_id', 'descripcion', 'precio_sin_iva', 'iva', 'pvp', 'stock', 'estado'].forEach((fieldName) => {
            const field = modalForm.querySelector(`[name="${fieldName}"]`);

            if (!field) {
                return;
            }

            if (fieldName === 'iva') {
                field.value = 21;
                return;
            }

            if (fieldName === 'stock') {
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

    if (detailModal && detailTitle && detailCodigo && detailEstado && detailDescripcion && detailFamilia && detailPrecio && detailIva && detailPvp && detailStock) {
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
                detailStock.textContent = `${Number(articulo.stock ?? 0).toFixed(3)} kg`;

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
                            <div class="stock-result-meta">${articulo.familia_nombre || 'Sin familia'} · Peso actual: ${Number(articulo.stock || 0).toFixed(3)} kg</div>
                        </div>
                        <span class="stock-result-badge ${articulo.stock <= 0 ? 'is-danger' : 'is-ok'}">${Number(articulo.stock || 0).toFixed(3)} kg</span>
                    </button>
                `;
            }).join('');

            stockResults.querySelectorAll('.stock-result-item').forEach((button) => {
                button.addEventListener('click', () => {
                    const articulo = JSON.parse(button.dataset.stockArticulo || '{}');

                    stockArticuloId.value = articulo.id || '';
                    stockSelectedName.textContent = `${articulo.codigo} · ${articulo.descripcion}`;
                    stockSelectedMeta.textContent = `${articulo.familia_nombre || 'Sin familia'} · ${articulo.codigo_cliente || 'Sin código'} · Peso actual: ${Number(articulo.stock || 0).toFixed(3)} kg · Estado: ${articulo.estado}`;
                    stockSelectedCard.classList.add('is-active');

                    stockResults.querySelectorAll('.stock-result-item').forEach((item) => item.classList.remove('is-selected'));
                    button.classList.add('is-selected');
                });
            });
        };

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
                stockSelectedMeta.textContent = `${selectedArticulo.familia_nombre || 'Sin familia'} · ${selectedArticulo.codigo_cliente || 'Sin código'} · Peso actual: ${Number(selectedArticulo.stock || 0).toFixed(3)} kg · Estado: ${selectedArticulo.estado}`;
                stockSelectedCard.classList.add('is-active');
            }
        }

        if (stockModal.dataset.open === 'true') {
            openStockModal();
        }
    }
});