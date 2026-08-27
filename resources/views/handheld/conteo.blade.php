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

{{-- Indicador de Modo de Inventario --}}
<div style="background: {{ $modoInventario === 'dinamico' ? '#1e3a5f' : '#1e293b' }}; border: 1px solid {{ $modoInventario === 'dinamico' ? '#3b82f6' : 'var(--border-color)' }}; border-radius: 8px; padding: 0.4rem 0.65rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
    <i class="fa-solid {{ $modoInventario === 'dinamico' ? 'fa-cubes' : 'fa-cube' }}" style="color: {{ $modoInventario === 'dinamico' ? '#60a5fa' : '#94a3b8' }}; font-size: 0.85rem;"></i>
    <span style="font-size: 0.75rem; font-weight: 700; color: {{ $modoInventario === 'dinamico' ? '#93c5fd' : '#94a3b8' }};">
        Modo: {{ $modoInventario === 'dinamico' ? 'Dinámico (Por Lotes)' : 'Simple (Stock General)' }}
    </span>
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
    <input type="hidden" name="lote_id" id="conteo-lote-id">

    <!-- FICHA PRODUCTO SELECCIONADO -->
    <div id="conteo-card" style="background: #0f172a; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem; text-align: center; border: 1px dashed var(--border-color);">
        <div id="conteo-nombre" style="font-weight: 800; font-size: 1rem; color: white;">Ningún producto seleccionado</div>
        <div id="conteo-meta" style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Escanea con el lector láser de la Handheld</div>
        {{-- Info de Lote (solo visible en modo dinámico cuando se detecta un lote) --}}
        <div id="conteo-lote-info" style="display: none; margin-top: 0.5rem; padding: 0.5rem; background: #1e3a5f; border-radius: 6px; border: 1px solid #3b82f6;">
            <div style="font-size: 0.7rem; font-weight: 700; color: #60a5fa; margin-bottom: 0.25rem;">
                <i class="fa-solid fa-boxes-stacked"></i> LOTE ESPECÍFICO
            </div>
            <div id="conteo-lote-detalle" style="font-size: 0.8rem; color: #93c5fd; font-weight: 600;"></div>
        </div>
    </div>

    <!-- CANTIDAD / PESO REGISTRADO (LABEL SOLO LECTURA / NO TOCABLE) -->
    <div id="conteo-peso-display-card" style="display: none; background: #0b1329; border: 1.5px solid #38bdf8; border-radius: 8px; padding: 0.65rem; margin-bottom: 0.75rem; text-align: center;">
        <div style="font-size: 0.7rem; font-weight: 700; color: #38bdf8; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; justify-content: center; gap: 0.4rem;">
            <i class="fa-solid fa-lock" style="font-size: 0.65rem;"></i>
            <span id="conteo-peso-label-title">CANTIDAD / PESO REGISTRADO</span>
        </div>
        <div id="conteo-peso-display-val" style="font-size: 1.6rem; font-weight: 900; color: #38bdf8; margin: 0.15rem 0;">
            0.000 {{ $settings['unidad_peso'] ?? 'kg' }}
        </div>
        <div id="conteo-peso-display-sub" style="font-size: 0.72rem; color: #94a3b8;">
            Solo lectura
        </div>
    </div>

    {{-- SECCIÓN "OTROS LOTES" (solo visible en modo dinámico) --}}
    <div id="conteo-otros-lotes" style="display: none; margin-bottom: 0.75rem;">
        <button type="button" id="btn-toggle-otros-lotes" onclick="toggleOtrosLotes()" style="width: 100%; background: #0f172a; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.5rem 0.65rem; color: #94a3b8; font-size: 0.8rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: space-between;">
            <span><i class="fa-solid fa-layer-group" style="color: #f59e0b;"></i> Otros Lotes de este Artículo (<span id="otros-lotes-count">0</span>)</span>
            <i class="fa-solid fa-chevron-down" id="icon-toggle-lotes" style="transition: transform 0.2s;"></i>
        </button>
        <div id="lista-otros-lotes" style="display: none; margin-top: 0.35rem; max-height: 200px; overflow-y: auto; border-radius: 8px;">
        </div>
    </div>

    <!-- SECCIÓN DE AJUSTE (CUANDO SEA NECESARIO) -->
    <div style="background: #131d31; border: 1px solid var(--border-color); border-radius: 8px; padding: 0.65rem; margin-bottom: 0.75rem;">
        <div style="font-size: 0.75rem; font-weight: 700; color: #34d399; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem;">
            <i class="fa-solid fa-pen-to-square"></i> MODIFICAR O AJUSTAR STOCK (SI APLICA)
        </div>

        <!-- TIPO DE OPERACIÓN -->
        <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">
            TIPO DE ACCIÓN
        </label>
        <select name="tipo_ajuste" id="tipo-ajuste" class="hh-input" style="margin-bottom: 0.65rem;">
            <option value="reemplazo">Reemplazar Stock (Físico Total)</option>
            <option value="suma">Sumar a Stock (+)</option>
            <option value="resta">Restar a Stock (-)</option>
        </select>

        <!-- CANTIDAD FÍSICA PARA AJUSTAR -->
        <label id="label-conteo-cantidad" style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">
            CANTIDAD / PESO FÍSICO A AJUSTAR ({{ $settings['unidad_peso'] ?? 'kg' }})
        </label>
        <input type="number" step="0.001" min="0.001" name="cantidad" id="conteo-cantidad" class="hh-input" style="font-size: 1.25rem; font-weight: 800; text-align: center; color: #34d399;" placeholder="0.000" required>

        <!-- MOTIVO OBSERVACIÓN -->
        <input type="text" name="motivo" class="hh-input" style="margin-bottom: 0;" placeholder="Motivo o ubicación (opcional)...">
    </div>

    <button type="submit" class="hh-btn hh-btn-success" id="btn-conteo-submit" disabled>
        <i class="fa-solid fa-check-double"></i> Actualizar Existencia
    </button>
</form>

@push('scripts')
<script>
    const articulosCatalogo = @json($articulos);
    const modoInventario = '{{ $modoInventario }}';
    const lotesActivosCatalogo = @json($lotesActivos);
    const unidadPeso = '{{ $settings["unidad_peso"] ?? "kg" }}';
    window.unidadPeso = unidadPeso;

    const scannerInput = document.getElementById('conteo-scanner');
    const articuloIdInput = document.getElementById('conteo-articulo-id');
    const loteIdInput = document.getElementById('conteo-lote-id');
    const nombreTxt = document.getElementById('conteo-nombre');
    const metaTxt = document.getElementById('conteo-meta');
    const loteInfoDiv = document.getElementById('conteo-lote-info');
    const loteDetalleDiv = document.getElementById('conteo-lote-detalle');
    const pesoDisplayCard = document.getElementById('conteo-peso-display-card');
    const pesoLabelTitle = document.getElementById('conteo-peso-label-title');
    const pesoDisplayVal = document.getElementById('conteo-peso-display-val');
    const pesoDisplaySub = document.getElementById('conteo-peso-display-sub');
    const cantidadInput = document.getElementById('conteo-cantidad');
    const submitBtn = document.getElementById('btn-conteo-submit');
    const otrosLotesSection = document.getElementById('conteo-otros-lotes');
    const otrosLotesCount = document.getElementById('otros-lotes-count');
    const listaOtrosLotes = document.getElementById('lista-otros-lotes');

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
        loteIdInput.value = '';

        if (modoInventario === 'dinamico') {
            seleccionarProductoDinamico(articulo, result);
        } else {
            seleccionarProductoSimple(articulo, result);
        }

        submitBtn.disabled = false;
        cantidadInput.focus();
        cantidadInput.select();
    }

    function getUnidadArt(articulo) {
        return (articulo && articulo.tipo_articulo === 'unidad') ? 'UND' : unidadPeso;
    }

    function formatQtyArt(articulo, qty) {
        if (articulo && articulo.tipo_articulo === 'unidad') {
            return Number(qty).toFixed(0);
        }
        return Number(qty).toFixed(3);
    }

    function actualizarInputAjuste(articulo, qty) {
        var isUnidad = (articulo && articulo.tipo_articulo === 'unidad');
        var u = isUnidad ? 'UND' : unidadPeso.toUpperCase();
        var labelEl = document.getElementById('label-conteo-cantidad');
        if (labelEl) {
            labelEl.textContent = isUnidad ? 'CANTIDAD FÍSICA A AJUSTAR (UND)' : ('PESO FÍSICO A AJUSTAR (' + u + ')');
        }
        cantidadInput.step = isUnidad ? '1' : '0.001';
        cantidadInput.value = formatQtyArt(articulo, qty);
    }

    function seleccionarProductoSimple(articulo, result) {
        var u = getUnidadArt(articulo);
        nombreTxt.textContent = articulo.descripcion;
        metaTxt.innerHTML = 'Cód: ' + articulo.codigo + (articulo.tipo_articulo === 'unidad' ? ' | <span style="color: #fb923c;"><i class="fa-solid fa-box"></i> Unidad</span>' : ' | <span style="color: #60a5fa;"><i class="fa-solid fa-scale-balanced"></i> Granel</span>');
        
        // Label de texto no editable
        if (pesoDisplayCard) {
            pesoDisplayCard.style.display = 'block';
            pesoLabelTitle.textContent = (articulo.tipo_articulo === 'unidad') ? 'EXISTENCIA ACTUAL REGISTRADA (SISTEMA)' : 'STOCK ACTUAL REGISTRADO (SISTEMA)';
            pesoDisplayVal.textContent = formatQtyArt(articulo, articulo.stock) + ' ' + u;
            pesoDisplaySub.textContent = 'Total general registrado en sistema (Solo lectura)';
        }

        actualizarInputAjuste(articulo, articulo.stock);
        loteInfoDiv.style.display = 'none';
        otrosLotesSection.style.display = 'none';
    }

    function seleccionarProductoDinamico(articulo, result) {
        var lotesDelArticulo = lotesActivosCatalogo.filter(function(l) { return l.articulo_id === articulo.id; });
        var loteEncontrado = null;
        var u = getUnidadArt(articulo);

        if (result.cleanCode && lotesDelArticulo.length > 0) {
            loteEncontrado = lotesDelArticulo.find(function(l) {
                return l.codigo_escaneado && l.codigo_escaneado.replace(/[()\-\s]/g, '') === result.cleanCode;
            });

            if (!loteEncontrado && result.lote) {
                loteEncontrado = lotesDelArticulo.find(function(l) {
                    return l.lote && String(l.lote).trim() === String(result.lote).trim();
                });
            }
        }

        if (loteEncontrado) {
            loteIdInput.value = loteEncontrado.id;
            nombreTxt.textContent = articulo.descripcion;
            metaTxt.innerHTML = 'Stock General: <strong style="color: #94a3b8;">' + formatQtyArt(articulo, articulo.stock) + ' ' + u + '</strong> | Cód: ' + articulo.codigo;

            loteInfoDiv.style.display = 'block';
            var detalleHtml = '<div>Lote: <strong>' + (loteEncontrado.lote || '-') + '</strong> | Serie: ' + (loteEncontrado.serie || '-') + '</div>';
            if (loteEncontrado.fecha_vencimiento) {
                detalleHtml += '<div style="margin-top: 0.15rem; font-size: 0.75rem; color: #a5b4fc;">Vence: ' + loteEncontrado.fecha_vencimiento + '</div>';
            }
            if (loteEncontrado.created_at) {
                detalleHtml += '<div style="margin-top: 0.1rem; font-size: 0.7rem; color: #64748b;">Recibido: ' + loteEncontrado.created_at + '</div>';
            }
            loteDetalleDiv.innerHTML = detalleHtml;

            // Label de texto no editable para la cantidad del lote
            if (pesoDisplayCard) {
                pesoDisplayCard.style.display = 'block';
                pesoLabelTitle.textContent = (articulo.tipo_articulo === 'unidad') ? 'CANTIDAD REGISTRADA DEL LOTE' : 'PESO REGISTRADO DEL LOTE';
                pesoDisplayVal.textContent = formatQtyArt(articulo, loteEncontrado.cantidad_peso) + ' ' + u;
                pesoDisplaySub.textContent = 'Lote: ' + (loteEncontrado.lote || '-') + ' | Serie: ' + (loteEncontrado.serie || '-') + ' (Solo lectura)';
            }

            actualizarInputAjuste(articulo, loteEncontrado.cantidad_peso);
        } else {
            nombreTxt.textContent = articulo.descripcion;
            metaTxt.innerHTML = 'Cód: ' + articulo.codigo + (articulo.tipo_articulo === 'unidad' ? ' | <span style="color: #fb923c;"><i class="fa-solid fa-box"></i> Unidad</span>' : ' | <span style="color: #60a5fa;"><i class="fa-solid fa-scale-balanced"></i> Granel</span>');
            loteInfoDiv.style.display = 'none';

            // Label de texto no editable para stock total
            if (pesoDisplayCard) {
                pesoDisplayCard.style.display = 'block';
                pesoLabelTitle.textContent = (articulo.tipo_articulo === 'unidad') ? 'EXISTENCIA TOTAL REGISTRADA (SISTEMA)' : 'STOCK TOTAL REGISTRADO (SISTEMA)';
                pesoDisplayVal.textContent = formatQtyArt(articulo, articulo.stock) + ' ' + u;
                pesoDisplaySub.textContent = 'Stock acumulado sin lote específico detectado (Solo lectura)';
            }

            actualizarInputAjuste(articulo, articulo.stock);
        }

        mostrarOtrosLotes(articulo, lotesDelArticulo, loteEncontrado);
    }

    function mostrarOtrosLotes(articulo, lotesDelArticulo, loteActual) {
        var lotesAMostrar = loteActual
            ? lotesDelArticulo.filter(function(l) { return l.id !== loteActual.id; })
            : lotesDelArticulo;

        if (lotesAMostrar.length === 0) {
            otrosLotesSection.style.display = 'none';
            return;
        }

        var u = getUnidadArt(articulo);
        otrosLotesCount.textContent = lotesAMostrar.length;
        otrosLotesSection.style.display = 'block';
        listaOtrosLotes.style.display = 'none';
        document.getElementById('icon-toggle-lotes').style.transform = 'rotate(0deg)';

        var html = '';
        lotesAMostrar.forEach(function(lote) {
            var vencLabel = lote.fecha_vencimiento ? 'Vence: ' + lote.fecha_vencimiento : 'Sin vencimiento';
            var recibidoLabel = lote.created_at ? 'Recibido: ' + lote.created_at : '';
            html += '<div style="background: #0f172a; border: 1px solid var(--border-color); border-radius: 6px; padding: 0.5rem 0.65rem; margin-bottom: 0.3rem; display: flex; align-items: center; justify-content: space-between;">';
            html += '<div style="flex: 1;">';
            html += '<div style="font-size: 0.8rem; font-weight: 700; color: #e2e8f0;">Lote: ' + (lote.lote || '-') + ' <span style="color: #64748b; font-weight: 400;">| Serie: ' + (lote.serie || '-') + '</span></div>';
            html += '<div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.1rem;">' + vencLabel + (recibidoLabel ? ' | ' + recibidoLabel : '') + '</div>';
            html += '</div>';
            html += '<div style="text-align: right; margin-left: 0.5rem;">';
            html += '<div style="font-size: 0.9rem; font-weight: 800; color: #f59e0b;">' + formatQtyArt(articulo, lote.cantidad_peso) + ' ' + u + '</div>';
            html += '<button type="button" onclick="seleccionarLoteDirecto(' + articulo.id + ', ' + lote.id + ')" style="background: #1e3a5f; border: 1px solid #3b82f6; color: #60a5fa; font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 4px; cursor: pointer; margin-top: 0.15rem;"><i class="fa-solid fa-hand-pointer"></i> Seleccionar</button>';
            html += '</div></div>';
        });
        listaOtrosLotes.innerHTML = html;
    }

    function toggleOtrosLotes() {
        var isVisible = listaOtrosLotes.style.display !== 'none';
        listaOtrosLotes.style.display = isVisible ? 'none' : 'block';
        document.getElementById('icon-toggle-lotes').style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    function seleccionarLoteDirecto(articuloId, loteId) {
        var articulo = articulosCatalogo.find(function(a) { return a.id === articuloId; });
        var lote = lotesActivosCatalogo.find(function(l) { return l.id === loteId; });
        if (!articulo || !lote) return;

        var u = getUnidadArt(articulo);
        articuloIdInput.value = articulo.id;
        loteIdInput.value = lote.id;

        nombreTxt.textContent = articulo.descripcion;
        metaTxt.innerHTML = 'Stock General: <strong style="color: #94a3b8;">' + formatQtyArt(articulo, articulo.stock) + ' ' + u + '</strong> | Cód: ' + articulo.codigo;

        loteInfoDiv.style.display = 'block';
        var detalleHtml = '<div>Lote: <strong>' + (lote.lote || '-') + '</strong> | Serie: ' + (lote.serie || '-') + '</div>';
        if (lote.fecha_vencimiento) {
            detalleHtml += '<div style="margin-top: 0.15rem; font-size: 0.75rem; color: #a5b4fc;">Vence: ' + lote.fecha_vencimiento + '</div>';
        }
        if (lote.created_at) {
            detalleHtml += '<div style="margin-top: 0.1rem; font-size: 0.7rem; color: #64748b;">Recibido: ' + lote.created_at + '</div>';
        }
        loteDetalleDiv.innerHTML = detalleHtml;

        // Actualizar label de texto no editable
        if (pesoDisplayCard) {
            pesoDisplayCard.style.display = 'block';
            pesoLabelTitle.textContent = (articulo.tipo_articulo === 'unidad') ? 'CANTIDAD REGISTRADA DEL LOTE' : 'PESO REGISTRADO DEL LOTE';
            pesoDisplayVal.textContent = formatQtyArt(articulo, lote.cantidad_peso) + ' ' + u;
            pesoDisplaySub.textContent = 'Lote: ' + (lote.lote || '-') + ' | Serie: ' + (lote.serie || '-') + ' (Solo lectura)';
        }

        actualizarInputAjuste(articulo, lote.cantidad_peso);
        submitBtn.disabled = false;

        var lotesDelArticulo = lotesActivosCatalogo.filter(function(l) { return l.articulo_id === articulo.id; });
        mostrarOtrosLotes(articulo, lotesDelArticulo, lote);

        cantidadInput.focus();
        cantidadInput.select();

        listaOtrosLotes.style.display = 'none';
        document.getElementById('icon-toggle-lotes').style.transform = 'rotate(0deg)';
    }

        listaOtrosLotes.style.display = 'none';
        document.getElementById('icon-toggle-lotes').style.transform = 'rotate(0deg)';
    }
</script>
@endpush
@endsection
