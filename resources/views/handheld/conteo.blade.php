@extends('layouts.handheld')

@section('title', 'Conteo e Inventario Móvil')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
    <h3 style="font-size: 1.1rem; font-weight: 800; color: white;">
        <i class="fa-solid fa-clipboard-check" style="color: #34d399;"></i> Conteo / Ajuste
    </h3>
    <a href="{{ route('handheld.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 0.85rem; font-weight: 600;">
        <i class="fa-solid fa-arrow-left"></i> Volver
    </a>
</div>

<!-- ESCÁNER DE PRODUCTO -->
<div style="background: #1e293b; border: 1.5px solid #34d399; border-radius: 10px; padding: 0.65rem; margin-bottom: 0.75rem;">
    <label style="font-size: 0.75rem; font-weight: 700; color: #34d399; display: block; margin-bottom: 0.25rem;">
        <i class="fa-solid fa-barcode"></i> ESCANEAR PRODUCTO A CONTAR
    </label>
    <input type="text" id="conteo-scanner" class="hh-input" style="margin-bottom: 0; font-size: 1rem; font-weight: 700; color: #34d399; background: #0f172a;" placeholder="Escanea código o busca..." autofocus autocomplete="off">
</div>

<!-- FORMULARIO DE AJUSTE -->
<form action="{{ route('handheld.conteo.store') }}" method="POST" id="form-conteo" style="background: #1e293b; border: 1px solid var(--border-color); border-radius: 10px; padding: 0.85rem;">
    @csrf
    <input type="hidden" name="articulo_id" id="conteo-articulo-id">

    <!-- FICHA PRODUCTO SELECCIONADO -->
    <div id="conteo-card" style="background: #0f172a; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem; text-align: center; border: 1px dashed var(--border-color);">
        <div id="conteo-nombre" style="font-weight: 800; font-size: 1rem; color: white;">Ningún producto seleccionado</div>
        <div id="conteo-meta" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Escanea con el lector láser de la Handheld</div>
    </div>

    <!-- TIPO DE OPERACIÓN -->
    <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">
        TIPO DE ACCIÓN
    </label>
    <select name="tipo_ajuste" id="tipo-ajuste" class="hh-input" style="margin-bottom: 0.75rem;">
        <option value="reemplazo">Reemplazar Stock (Físico Total)</option>
        <option value="suma">Sumar a Stock (+)</option>
        <option value="resta">Restar a Stock (-)</option>
    </select>

    <!-- CANTIDAD FÍSICA -->
    <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">
        CANTIDAD / PESO FÍSICO ({{ $settings['unidad_peso'] ?? 'kg' }})
    </label>
    <input type="number" step="0.001" min="0.001" name="cantidad" id="conteo-cantidad" class="hh-input" style="font-size: 1.25rem; font-weight: 800; text-align: center; color: #34d399;" placeholder="0.000" required>

    <!-- MOTIVO OBSERVACIÓN -->
    <input type="text" name="motivo" class="hh-input" style="margin-bottom: 0.85rem;" placeholder="Motivo o ubicación (opcional)...">

    <button type="submit" class="hh-btn hh-btn-success" id="btn-conteo-submit" disabled>
        <i class="fa-solid fa-check-double"></i> Actualizar Existencia
    </button>
</form>

@push('scripts')
<script>
    const articulosCatalogo = @json($articulos);
    const scannerInput = document.getElementById('conteo-scanner');
    const articuloIdInput = document.getElementById('conteo-articulo-id');
    const nombreTxt = document.getElementById('conteo-nombre');
    const metaTxt = document.getElementById('conteo-meta');
    const cantidadInput = document.getElementById('conteo-cantidad');
    const submitBtn = document.getElementById('btn-conteo-submit');

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
            seleccionarProducto(raw);
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

        return {
            articulo: articulo || null,
            peso: parsedWeight > 0 ? parsedWeight : 1.000,
            lote: parsedLote,
            cleanCode: cleanCode
        };
    }

    function seleccionarProducto(code) {
        const result = parseBarcodeForHandheld(code, articulosCatalogo);

        if (!result || !result.articulo) {
            alert('Producto no encontrado: ' + code);
            return;
        }

        let articulo = result.articulo;

        articuloIdInput.value = articulo.id;
        nombreTxt.textContent = articulo.descripcion;
        metaTxt.innerHTML = `Stock Actual: <strong style="color: #38bdf8;">${Number(articulo.stock).toFixed(3)} ${window.unidadPeso || 'kg'}</strong> | Cód: ${articulo.codigo}`;
        cantidadInput.value = Number(articulo.stock).toFixed(3);
        submitBtn.disabled = false;
        cantidadInput.focus();
        cantidadInput.select();
    }
</script>
@endpush
@endsection
