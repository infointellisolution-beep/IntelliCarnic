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
    const detailModal = document.getElementById('detalle-modal');
    const detailTitle = document.getElementById('detalle-modal-title');
    const detailCodigo = document.getElementById('detalle-codigo-chip');
    const detailEstado = document.getElementById('detalle-estado-chip');
    const detailDescripcion = document.getElementById('detalle-descripcion');
    const detailCategoria = document.getElementById('detalle-categoria');
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

    const setFieldValue = (name, value) => {
        const field = modalForm.querySelector(`[name="${name}"]`);

        if (!field) {
            return;
        }

        field.value = value ?? '';
    };

    const resetToCreateMode = () => {
        modalTitle.textContent = 'Nuevo artículo';
        modalForm.action = openCreateButtons[0]?.dataset.modalAction || modalForm.action;
        modalId.value = '';
        modalMethod.value = '';
        modalMethod.disabled = true;

        ['codigo', 'descripcion', 'categoria', 'precio_sin_iva', 'iva', 'pvp', 'stock', 'estado'].forEach((fieldName) => {
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

            field.value = '';
        });
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
            setFieldValue('descripcion', articulo.descripcion);
            setFieldValue('categoria', articulo.categoria);
            setFieldValue('precio_sin_iva', articulo.precio_sin_iva);
            setFieldValue('iva', articulo.iva ?? 21);
            setFieldValue('pvp', articulo.pvp);
            setFieldValue('stock', articulo.stock ?? 0);
            setFieldValue('estado', articulo.estado || 'activo');

            openModal();
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    if (modal.dataset.open === 'true') {
        openModal();
    }

    if (detailModal && detailTitle && detailCodigo && detailEstado && detailDescripcion && detailCategoria && detailPrecio && detailIva && detailPvp && detailStock) {
        const openDetailModal = () => {
            detailModal.classList.add('is-open');
            detailModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        };

        const closeDetailModal = () => {
            detailModal.classList.remove('is-open');
            detailModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
        };

        detailButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const articulo = JSON.parse(button.dataset.articuloDetalles || '{}');

                detailTitle.textContent = `Detalle de ${articulo.codigo || 'artículo'}`;
                detailCodigo.textContent = articulo.codigo || 'Sin código';
                detailEstado.textContent = (articulo.estado || 'activo').replace('_', ' ');
                detailDescripcion.textContent = articulo.descripcion || 'Sin descripción';
                detailCategoria.textContent = articulo.categoria || 'Sin categoría';
                detailPrecio.textContent = `$${Number(articulo.precio_sin_iva || 0).toFixed(2)}`;
                detailIva.textContent = `${Number(articulo.iva || 0).toFixed(0)}%`;
                detailPvp.textContent = `$${Number(articulo.pvp || 0).toFixed(2)}`;
                detailStock.textContent = `${articulo.stock ?? 0} unidades`;

                openDetailModal();
            });
        });

        detailCloseButtons.forEach((button) => {
            button.addEventListener('click', closeDetailModal);
        });

        detailModal.addEventListener('click', (event) => {
            if (event.target === detailModal) {
                closeDetailModal();
            }
        });
    }

        if (stockModal && stockSearchInput && stockResults && stockArticuloId && stockSelectedCard && stockSelectedName && stockSelectedMeta && stockMovimiento && stockCantidad && stockMotivo) {
            const openStockModal = () => {
                stockModal.classList.add('is-open');
                stockModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                stockSearchInput.focus();
            };

            const closeStockModal = () => {
                stockModal.classList.remove('is-open');
                stockModal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
            };

            const renderStockResults = (term = '') => {
                const normalizedTerm = term.trim().toLowerCase();
                const selectedId = stockArticuloId.value ? Number(stockArticuloId.value) : null;

                if (normalizedTerm.length < 2) {
                    stockResults.innerHTML = '<div class="stock-empty"><strong>Escribe al menos 2 caracteres</strong> para buscar un producto.</div>';
                    return;
                }

                const results = catalogoArticulos.filter((articulo) => {
                    return [articulo.codigo, articulo.descripcion, articulo.categoria]
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
                                <div class="stock-result-meta">${articulo.categoria || 'Sin categoría'} · Stock actual: ${articulo.stock}</div>
                            </div>
                            <span class="stock-result-badge ${articulo.stock <= 0 ? 'is-danger' : 'is-ok'}">${articulo.stock} un.</span>
                        </button>
                    `;
                }).join('');

                stockResults.querySelectorAll('.stock-result-item').forEach((button) => {
                    button.addEventListener('click', () => {
                        const articulo = JSON.parse(button.dataset.stockArticulo || '{}');

                        stockArticuloId.value = articulo.id || '';
                        stockSelectedName.textContent = `${articulo.codigo} · ${articulo.descripcion}`;
                        stockSelectedMeta.textContent = `${articulo.categoria || 'Sin categoría'} · Stock actual: ${articulo.stock} · Estado: ${articulo.estado}`;
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

            closeStockButtons.forEach((button) => {
                button.addEventListener('click', closeStockModal);
            });

            stockModal.addEventListener('click', (event) => {
                if (event.target === stockModal) {
                    closeStockModal();
                }
            });

            stockSearchInput.addEventListener('input', () => {
                renderStockResults(stockSearchInput.value);
            });

            if (stockArticuloId.value) {
                const selectedArticulo = catalogoArticulos.find((articulo) => Number(articulo.id) === Number(stockArticuloId.value));

                if (selectedArticulo) {
                    stockSelectedName.textContent = `${selectedArticulo.codigo} · ${selectedArticulo.descripcion}`;
                    stockSelectedMeta.textContent = `${selectedArticulo.categoria || 'Sin categoría'} · Stock actual: ${selectedArticulo.stock} · Estado: ${selectedArticulo.estado}`;
                    stockSelectedCard.classList.add('is-active');
                }
            }

            if (stockModal.dataset.open === 'true') {
                openStockModal();
            }
        }
});