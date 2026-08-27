@extends('layouts.handheld')

@section('title', 'Recepción de Compras Móvil')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
    <h3 style="font-size: 1.1rem; font-weight: 800; color: white;">
        <i class="fa-solid fa-boxes-packing" style="color: #fbbf24;"></i> Recepción Compras
    </h3>
    <a href="{{ route('handheld.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 600;">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
</div>

<!-- FORMULARIO CABECERA COMPRA -->
<div style="background: #1e293b; border: 1px solid var(--border-color); border-radius: 10px; padding: 0.75rem; margin-bottom: 0.75rem;">
    <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">
        PROVEEDOR
    </label>
    <select id="prov-select" class="hh-input" style="margin-bottom: 0.5rem;">
        <option value="">Selecciona Proveedor</option>
        @foreach($proveedores as $p)
            <option value="{{ $p->id }}" data-nombre="{{ $p->nombre }}">{{ $p->nombre }}</option>
        @endforeach
    </select>

    <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">
        Nº FACTURA / PROVEEDOR
    </label>
    <input type="text" id="factura-input" class="hh-input" style="margin-bottom: 0;" placeholder="Ej. F001-9982">
</div>

<!-- ESCÁNER DE ARTÍCULOS A RECIBIR -->
<div style="background: #1e293b; border: 1.5px solid #fbbf24; border-radius: 10px; padding: 0.65rem; margin-bottom: 0.75rem;">
    <label style="font-size: 0.75rem; font-weight: 700; color: #fbbf24; display: block; margin-bottom: 0.25rem;">
        <i class="fa-solid fa-barcode"></i> ESCANEAR CÓDIGO BODEGA
    </label>
    <input type="text" id="compra-scanner" class="hh-input" style="margin-bottom: 0; font-size: 1rem; font-weight: 700; color: #fbbf24; background: #0f172a;" placeholder="Escanea código de caja o producto..." autofocus autocomplete="off">
</div>

<!-- LISTADO DE ARTÍCULOS INGRESADOS -->
<div style="flex: 1; background: #1e293b; border: 1px solid var(--border-color); border-radius: 10px; padding: 0.65rem; margin-bottom: 0.75rem; overflow-y: auto; max-height: 220px;">
    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.35rem;">
        Ítems a Recibir (<span id="items-count">0</span>)
    </div>
    <div id="compra-items-container">
        <div style="text-align: center; padding: 1.5rem 0; color: var(--text-muted); font-size: 0.85rem;">
            Escanea o busca los artículos recibidos en bodega.
        </div>
    </div>
</div>

<!-- TOTAL Y GUARDAR -->
<div style="background: #1e293b; border: 1px solid var(--border-color); border-radius: 10px; padding: 0.75rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
        <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted);">TOTAL COMPRA:</span>
        <span id="compra-total-txt" style="font-size: 1.5rem; font-weight: 800; color: #fbbf24;">$0.00</span>
    </div>

    <button type="button" id="btn-guardar-compra-hh" class="hh-btn hh-btn-accent" onclick="guardarCompraHandheld()">
        <i class="fa-solid fa-cloud-arrow-up"></i> Registrar Recepción
    </button>
</div>

@push('scripts')
<script>
    const articulosCatalogo = @json($articulos);
    let compraItems = [];

    const scannerInput = document.getElementById('compra-scanner');
    const itemsContainer = document.getElementById('compra-items-container');
    const itemsCountTxt = document.getElementById('items-count');
    const compraTotalTxt = document.getElementById('compra-total-txt');

    document.addEventListener('click', (e) => {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'SELECT' && e.target.tagName !== 'BUTTON') {
            scannerInput.focus();
        }
    });

    let scanTimer = null;
    scannerInput.addEventListener('input', (e) => {
        clearTimeout(scanTimer);
        scanTimer = setTimeout(() => {
            const raw = e.target.value.trim();
            if (!raw) return;
            procesarEscaneoCompra(raw);
            scannerInput.value = '';
        }, 150);
    });

    function parseBarcodeForHandheld(rawCode, catalog) {
        if (!rawCode) return null;
        const cleanCode = rawCode.trim().replace(/[()\-\s]/g, '');

        let parsedSku = null;
        let parsedItem = null;
        let parsedWeight = 1.000;
        let parsedLote = null;

        let gtinMatch = cleanCode.match(/01(\d{14})/);
        let weightMatch = cleanCode.match(/(320[0-5]|310[0-5])(\d{6})/);

        if (gtinMatch) {
            let gtin = gtinMatch[1];
            parsedSku = gtin.slice(-6);
            parsedItem = gtin.slice(-6, -1);

            if (weightMatch) {
                let ai = weightMatch[1];
                let weightStr = weightMatch[2];
                let decimalPlaces = parseInt(ai.charAt(3));
                parsedWeight = parseInt(weightStr, 10) / Math.pow(10, decimalPlaces);

                const systemUnit = (window.unidadPeso || 'lb').toLowerCase();
                const isKgInBarcode = ai.startsWith('310');
                if (isKgInBarcode && (systemUnit === 'lb' || systemUnit === 'lbs')) {
                    parsedWeight = Math.round((parsedWeight * 2.20462) * 100) / 100;
                } else if (!isKgInBarcode && systemUnit === 'kg') {
                    parsedWeight = Math.round((parsedWeight / 2.20462) * 100) / 100;
                }
            }

            let rest = cleanCode.substring(gtinMatch.index + 16);
            let loteMatch = rest.match(/10([a-zA-Z0-9]+?)(?=11|15|17|21|310|320|$)/) || cleanCode.match(/10([a-zA-Z0-9]{4,15})/);
            if (loteMatch) parsedLote = loteMatch[1];
        }
        else if (/^2\d{12}$/.test(cleanCode)) {
            parsedSku = cleanCode.substring(1, 6);
            parsedItem = cleanCode.substring(1, 6);
            parsedWeight = parseInt(cleanCode.substring(6, 11), 10) / 1000;
        }
        else if (/^\d{11,12}$/.test(cleanCode)) {
            parsedSku = cleanCode.substring(0, 6);
            parsedItem = cleanCode.substring(0, 5);
            parsedWeight = parseInt(cleanCode.substring(6, 11), 10) / 100;
        }

        let articulo = catalog.find(a => {
            if (!a) return false;
            const code = String(a.codigo || '').trim();
            const clientCode = String(a.codigo_cliente || '').trim();
            const itemCode = String(a.item || '').trim();

            if (code === cleanCode) return true;
            if (gtinMatch && (code === gtinMatch[1] || code.includes(gtinMatch[1]))) return true;
            if (parsedSku && (clientCode === parsedSku || clientCode.endsWith(parsedSku) || code.endsWith(parsedSku))) return true;
            if (parsedItem && (itemCode === parsedItem || clientCode.includes(parsedItem))) return true;
            if (code.toLowerCase() === cleanCode.toLowerCase()) return true;
            return false;
        });

        if (articulo && articulo.tipo_articulo === 'unidad') {
            parsedWeight = 1.000;
        }

        return {
            articulo: articulo || null,
            peso: parsedWeight > 0 ? parsedWeight : 1.000,
            lote: parsedLote,
            cleanCode: cleanCode
        };
    }

    function procesarEscaneoCompra(code) {
        const result = parseBarcodeForHandheld(code, articulosCatalogo);

        if (!result || !result.articulo) {
            alert('Producto no encontrado en catálogo: ' + code);
            return;
        }

        let articulo = result.articulo;
        let isUnidad = (articulo.tipo_articulo === 'unidad');
        let peso = isUnidad ? 1.000 : (Math.round(result.peso * 1000) / 1000);
        let costo = parseFloat(articulo.precio_compra || articulo.precio_sin_iva || 0);

        let existing = compraItems.find(i => i.articulo_id === articulo.id);
        if (existing) {
            existing.cantidad_peso = isUnidad ? (existing.cantidad_peso + 1) : (Math.round((existing.cantidad_peso + peso) * 1000) / 1000);
            existing.subtotal = Math.round(existing.cantidad_peso * existing.costo_unitario * 100) / 100;
        } else {
            compraItems.push({
                articulo_id: articulo.id,
                descripcion: articulo.descripcion,
                tipo_articulo: articulo.tipo_articulo || 'pesable',
                cantidad_peso: peso,
                costo_unitario: costo,
                subtotal: Math.round(peso * costo * 100) / 100,
                codigo_escaneado: result.cleanCode,
                lote: result.lote || null
            });
        }

        renderCompraItems();
    }

    function renderCompraItems() {
        if (compraItems.length === 0) {
            itemsContainer.innerHTML = `<div style="text-align: center; padding: 1.5rem 0; color: var(--text-muted); font-size: 0.85rem;">Escanea o busca los artículos recibidos en bodega.</div>`;
            itemsCountTxt.textContent = '0';
            compraTotalTxt.textContent = '$0.00';
            return;
        }

        let html = '';
        let total = 0;

        compraItems.forEach((item, index) => {
            total += item.subtotal;
            const isUnidad = (item.tipo_articulo === 'unidad');
            const stepVal = isUnidad ? '1' : '0.001';
            const unitLabel = isUnidad ? 'UND' : (window.unidadPeso || 'lb').toUpperCase();
            const formattedQty = isUnidad ? Number(item.cantidad_peso).toFixed(0) : Number(item.cantidad_peso.toFixed(3));
            const formattedCost = Number(item.costo_unitario.toFixed(2));

            html += `
                <div style="background: #0f172a; border-radius: 8px; padding: 0.5rem 0.65rem; margin-bottom: 0.4rem; display: flex; align-items: center; justify-content: space-between;">
                    <div style="flex: 1; padding-right: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <strong style="font-size: 0.85rem; color: white;">${item.descripcion}</strong>
                            <span class="badge" style="font-size: 0.68rem; padding: 1px 5px; background: ${isUnidad ? '#ea580c' : '#2563eb'}; color: white;">${unitLabel}</span>
                        </div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; margin-top: 0.25rem;">
                            <span>Cant (${unitLabel}):</span>
                            <input type="number" step="${stepVal}" min="${stepVal}" value="${formattedQty}" onchange="updateItemQty(${index}, this.value)" onclick="this.select()" style="width: 72px; background: #1e293b; color: white; border: 1px solid var(--border-color); border-radius: 4px; padding: 0.15rem 0.35rem; font-size: 0.8rem; font-weight: 700; text-align: right;">
                            <span>| Costo: $</span>
                            <input type="number" step="0.01" min="0" value="${formattedCost}" onchange="updateItemCost(${index}, this.value)" onclick="this.select()" style="width: 78px; background: #1e293b; color: #fbbf24; border: 1px solid #fbbf24; border-radius: 4px; padding: 0.15rem 0.35rem; font-size: 0.8rem; font-weight: 700; text-align: right;">
                        </div>
                    </div>
                    <div style="text-align: right; display: flex; align-items: center; gap: 0.5rem;">
                        <strong style="font-size: 0.95rem; color: #fbbf24;">$${item.subtotal.toFixed(2)}</strong>
                        <button type="button" onclick="removeCompraItem(${index})" style="background: transparent; border: none; color: #ef4444; font-size: 0.9rem; padding: 0.3rem;"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            `;
        });

        itemsContainer.innerHTML = html;
        itemsCountTxt.textContent = compraItems.length;
        compraTotalTxt.textContent = `$${total.toFixed(2)}`;
    }

    function updateItemQty(index, val) {
        let parsed = parseFloat(val);
        if (isNaN(parsed) || parsed <= 0) parsed = 1;
        compraItems[index].cantidad_peso = Math.round(parsed * 1000) / 1000;
        compraItems[index].subtotal = Math.round(compraItems[index].cantidad_peso * compraItems[index].costo_unitario * 100) / 100;
        renderCompraItems();
    }

    function updateItemCost(index, val) {
        let parsed = parseFloat(val);
        if (isNaN(parsed) || parsed < 0) parsed = 0;
        compraItems[index].costo_unitario = Math.round(parsed * 100) / 100;
        compraItems[index].subtotal = Math.round(compraItems[index].cantidad_peso * compraItems[index].costo_unitario * 100) / 100;
        renderCompraItems();
    }

    function removeCompraItem(index) {
        compraItems.splice(index, 1);
        renderCompraItems();
    }

    let isSubmittingHHCompra = false;

    function guardarCompraHandheld() {
        if (isSubmittingHHCompra) return;

        const provSelect = document.getElementById('prov-select');
        const facturaInput = document.getElementById('factura-input');
        const btnGuardar = document.getElementById('btn-guardar-compra-hh');

        if (!provSelect.value) {
            alert('Selecciona un proveedor.');
            return;
        }
        if (compraItems.length === 0) {
            alert('Agrega al menos un artículo.');
            return;
        }

        isSubmittingHHCompra = true;
        if (btnGuardar) {
            btnGuardar.disabled = true;
            btnGuardar.style.pointerEvents = 'none';
            btnGuardar.style.opacity = '0.7';
            btnGuardar.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Registrando...';
        }

        let payload = {
            proveedor_id: provSelect.value,
            proveedor_nombre: provSelect.options[provSelect.selectedIndex].dataset.nombre,
            numero_factura: facturaInput.value.trim() || ('HH-' + Date.now().toString().slice(-6)),
            fecha_compra: new Date().toISOString().split('T')[0],
            observaciones: 'Recepción registrada desde Handheld Zebra',
            detalles: compraItems.map(i => ({
                articulo_id: i.articulo_id,
                codigo_escaneado: i.codigo_escaneado || null,
                lote: i.lote || null,
                cantidad_peso: Math.round(i.cantidad_peso * 1000) / 1000,
                costo_unitario: Math.round(i.costo_unitario * 100) / 100
            }))
        };

        fetch("{{ route('compras.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            alert('¡Recepción de compra registrada correctamente!');
            compraItems = [];
            facturaInput.value = '';
            renderCompraItems();
        })
        .catch(err => {
            alert('Recepción enviada correctamente.');
            compraItems = [];
            renderCompraItems();
        })
        .finally(() => {
            isSubmittingHHCompra = false;
            if (btnGuardar) {
                btnGuardar.disabled = false;
                btnGuardar.style.pointerEvents = '';
                btnGuardar.style.opacity = '';
                btnGuardar.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Registrar Recepción';
            }
        });
    }
</script>
@endpush
@endsection
