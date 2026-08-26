@extends('layouts.handheld')

@section('title', 'TPV Móvil Express')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
    <h3 style="font-size: 1.1rem; font-weight: 800; color: white;">
        <i class="fa-solid fa-cart-shopping" style="color: #60a5fa;"></i> TPV Móvil
    </h3>
    <a href="{{ route('handheld.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 600;">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
</div>

<!-- ESCÁNER EN VIVO -->
<div style="background: #1e293b; border: 1.5px solid #38bdf8; border-radius: 10px; padding: 0.65rem; margin-bottom: 0.75rem;">
    <label style="font-size: 0.75rem; font-weight: 700; color: #38bdf8; display: block; margin-bottom: 0.25rem;">
        <i class="fa-solid fa-barcode"></i> ESCÁNER / CÓDIGO BÁSCULA
    </label>
    <input type="text" id="scanner-input" class="hh-input" style="margin-bottom: 0; font-size: 1.1rem; font-weight: 700; color: #38bdf8; background: #0f172a;" placeholder="Escanea o escribe código..." autofocus autocomplete="off">
</div>

<!-- CLIENTE SELECCIONADO -->
<div style="background: #1e293b; border: 1px solid var(--border-color); border-radius: 10px; padding: 0.65rem; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: space-between;">
    <div style="font-size: 0.85rem;">
        <span style="color: var(--text-muted); display: block; font-size: 0.75rem;">Cliente:</span>
        <strong id="cliente-nombre-txt" style="color: white;">CONSUMIDOR FINAL</strong>
    </div>
    <select id="cliente-select" class="hh-input" style="width: auto; margin-bottom: 0; padding: 0.35rem 0.5rem; font-size: 0.8rem;">
        <option value="">Consumidor Final</option>
        @foreach($clientes as $c)
            <option value="{{ $c->id }}" data-nombre="{{ $c->nombre }}">{{ $c->nombre }} ({{ $c->identificacion ?: 'S/I' }})</option>
        @endforeach
    </select>
</div>

<!-- CARRITO MÓVIL DE COMPRAS -->
<div style="flex: 1; background: #1e293b; border: 1px solid var(--border-color); border-radius: 10px; padding: 0.65rem; margin-bottom: 0.75rem; overflow-y: auto; max-height: 260px;">
    <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.35rem;">
        Artículos en la Venta (<span id="cart-count">0</span>)
    </div>
    <div id="cart-items-container">
        <div id="cart-empty-msg" style="text-align: center; padding: 1.5rem 0; color: var(--text-muted); font-size: 0.85rem;">
            Escanea un producto con el lector de la Handheld.
        </div>
    </div>
</div>

<!-- TOTAL Y BOTONES DE COBRO -->
<div style="background: #1e293b; border: 1px solid var(--border-color); border-radius: 10px; padding: 0.75rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem;">
        <span style="font-size: 0.9rem; font-weight: 700; color: var(--text-muted);">TOTAL A COBRAR:</span>
        <span id="cart-total-txt" style="font-size: 1.6rem; font-weight: 800; color: #34d399;">$0.00</span>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
        <button type="button" id="btn-hh-efectivo" class="hh-btn hh-btn-success" onclick="procesarCobroHandheld('efectivo')">
            <i class="fa-solid fa-money-bill-wave"></i> Efectivo
        </button>
        <button type="button" id="btn-hh-tarjeta" class="hh-btn hh-btn-accent" onclick="procesarCobroHandheld('tarjeta')">
            <i class="fa-solid fa-credit-card"></i> Tarjeta
        </button>
    </div>
    <button type="button" id="btn-hh-credito" class="hh-btn hh-btn-secondary" style="margin-top: 0.5rem;" onclick="procesarCobroHandheld('credito')">
        <i class="fa-solid fa-hand-holding-dollar"></i> Venta a Crédito
    </button>
</div>

@push('scripts')
<script>
    const articulosCatalogo = @json($articulos);
    let cart = [];

    const scannerInput = document.getElementById('scanner-input');
    const cartContainer = document.getElementById('cart-items-container');
    const cartEmptyMsg = document.getElementById('cart-empty-msg');
    const cartCountTxt = document.getElementById('cart-count');
    const cartTotalTxt = document.getElementById('cart-total-txt');
    const clienteSelect = document.getElementById('cliente-select');
    const clienteNombreTxt = document.getElementById('cliente-nombre-txt');

    // Mantener foco automático continuo para la Handheld
    document.addEventListener('click', (e) => {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'SELECT' && e.target.tagName !== 'BUTTON') {
            scannerInput.focus();
        }
    });

    clienteSelect.addEventListener('change', () => {
        const opt = clienteSelect.options[clienteSelect.selectedIndex];
        clienteNombreTxt.textContent = opt.dataset.nombre || 'CONSUMIDOR FINAL';
    });

    let scanTimer = null;
    scannerInput.addEventListener('input', (e) => {
        clearTimeout(scanTimer);
        scanTimer = setTimeout(() => {
            const raw = e.target.value.trim();
            if (!raw) return;
            procesarEscaneo(raw);
            scannerInput.value = '';
        }, 150);
    });

    scannerInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const raw = e.target.value.trim();
            if (raw) {
                procesarEscaneo(raw);
                scannerInput.value = '';
            }
        }
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

        return {
            articulo: articulo || null,
            peso: parsedWeight > 0 ? parsedWeight : 1.000,
            lote: parsedLote,
            cleanCode: cleanCode
        };
    }

    function procesarEscaneo(code) {
        const result = parseBarcodeForHandheld(code, articulosCatalogo);

        if (!result || !result.articulo) {
            alert('Producto no encontrado: ' + code);
            return;
        }

        let articulo = result.articulo;
        let peso = Math.round(result.peso * 1000) / 1000;

        let pvp = parseFloat(articulo.pvp || articulo.precio_sin_iva || 0);
        let existing = cart.find(i => i.articulo_id === articulo.id);

        if (existing) {
            existing.cantidad = Math.round((existing.cantidad + peso) * 1000) / 1000;
            existing.subtotal = Math.round(existing.cantidad * pvp * 100) / 100;
        } else {
            cart.push({
                articulo_id: articulo.id,
                descripcion: articulo.descripcion,
                precio_unitario: pvp,
                cantidad: peso,
                subtotal: Math.round(peso * pvp * 100) / 100
            });
        }

        renderCart();
    }

    function renderCart() {
        if (cart.length === 0) {
            cartContainer.innerHTML = `<div id="cart-empty-msg" style="text-align: center; padding: 1.5rem 0; color: var(--text-muted); font-size: 0.85rem;">Escanea un producto con el lector de la Handheld.</div>`;
            cartCountTxt.textContent = '0';
            cartTotalTxt.textContent = '$0.00';
            return;
        }

        let html = '';
        let total = 0;
        let count = 0;

        cart.forEach((item, index) => {
            total += item.subtotal;
            count += item.cantidad;
            html += `
                <div style="background: #0f172a; border-radius: 8px; padding: 0.5rem 0.65rem; margin-bottom: 0.4rem; display: flex; align-items: center; justify-content: space-between;">
                    <div style="flex: 1; padding-right: 0.5rem;">
                        <strong style="font-size: 0.85rem; color: white; display: block;">${item.descripcion}</strong>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">$${item.precio_unitario.toFixed(2)} x ${item.cantidad.toFixed(3)}</span>
                    </div>
                    <div style="text-align: right; display: flex; align-items: center; gap: 0.5rem;">
                        <strong style="font-size: 0.95rem; color: #34d399;">$${item.subtotal.toFixed(2)}</strong>
                        <button type="button" onclick="removeItem(${index})" style="background: transparent; border: none; color: #ef4444; font-size: 0.9rem; padding: 0.3rem;"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            `;
        });

        cartContainer.innerHTML = html;
        cartCountTxt.textContent = cart.length;
        cartTotalTxt.textContent = `$${total.toFixed(2)}`;
    }

    function removeItem(index) {
        cart.splice(index, 1);
        renderCart();
    }

    let isSubmittingHandheldCobro = false;

    function procesarCobroHandheld(metodo) {
        if (isSubmittingHandheldCobro) return;

        if (cart.length === 0) {
            alert('El carrito está vacío.');
            return;
        }

        let clienteId = clienteSelect.value || null;
        if (metodo === 'credito' && !clienteId) {
            alert('Selecciona un cliente para realizar una venta a crédito.');
            return;
        }

        const btnEfectivo = document.getElementById('btn-hh-efectivo');
        const btnTarjeta = document.getElementById('btn-hh-tarjeta');
        const btnCredito = document.getElementById('btn-hh-credito');

        isSubmittingHandheldCobro = true;
        [btnEfectivo, btnTarjeta, btnCredito].forEach(b => {
            if (b) {
                b.disabled = true;
                b.style.pointerEvents = 'none';
                b.style.opacity = '0.6';
            }
        });

        let activeBtn = metodo === 'efectivo' ? btnEfectivo : (metodo === 'tarjeta' ? btnTarjeta : btnCredito);
        let origHtml = activeBtn ? activeBtn.innerHTML : '';
        if (activeBtn) {
            activeBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Cobrando...';
        }

        let total = cart.reduce((acc, item) => acc + item.subtotal, 0);

        let payload = {
            cliente_id: clienteId,
            tipo_venta: metodo === 'credito' ? 'credito' : 'normal',
            metodo_pago: metodo === 'credito' ? 'efectivo' : metodo,
            total: total.toFixed(2),
            subtotal: total.toFixed(2),
            impuestos: 0,
            monto_recibido: total.toFixed(2),
            vuelto: '0.00',
            descuento: 0,
            items: cart.map(i => ({
                articulo_id: i.articulo_id,
                cantidad: i.cantidad,
                precio: i.precio_unitario,
                descuento: 0,
                subtotal: i.subtotal
            }))
        };

        fetch("{{ route('vender.cobrar') }}", {
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
            if (data.success || data.venta) {
                alert('¡Venta realizada con éxito!');
                cart = [];
                renderCart();
            } else {
                alert('Error al procesar cobro: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(err => {
            alert('Error de comunicación con el servidor LAN.');
        })
        .finally(() => {
            isSubmittingHandheldCobro = false;
            [btnEfectivo, btnTarjeta, btnCredito].forEach(b => {
                if (b) {
                    b.disabled = false;
                    b.style.pointerEvents = '';
                    b.style.opacity = '';
                }
            });
            if (activeBtn) {
                activeBtn.innerHTML = origHtml;
            }
        });
    }
</script>
@endpush
@endsection
