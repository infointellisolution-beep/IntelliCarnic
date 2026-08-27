@php
    $modalArticulo = $modalArticulo ?? null;
    $familias = $familias ?? collect();
    $settings = $settings ?? [];
    $usarImpuestos = (bool) ((int) ($settings['usar_impuestos'] ?? 1));
    $ivaGlobalEnabled = (int) ($settings['iva_global_enabled'] ?? 1) === 1;
    $ivaGlobalRate = (float) ($settings['iva_global_rate'] ?? 21);
    $unidadPeso = $settings['unidad_peso'] ?? 'kg';
@endphp

<div class="form-grid">
    <!-- SELECTOR DE TIPO DE ARTÍCULO: PESABLE A GRANEL VS SIMPLE POR UNIDAD -->
    <div style="grid-column: 1 / -1; margin-bottom: 0.5rem; padding: 0.85rem; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px;">
        <label style="font-size: 0.82rem; font-weight: 800; color: var(--text-main); text-transform: uppercase; margin-bottom: 0.5rem; display: block;">
            <i class="fa-solid fa-shapes" style="color: var(--primary);"></i> Naturaleza / Tipo de Artículo
        </label>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem;">
            <label id="label-tipo-pesable" style="display: flex; align-items: flex-start; gap: 0.6rem; padding: 0.75rem; border: {{ old('tipo_articulo', $modalArticulo->tipo_articulo ?? 'pesable') === 'pesable' ? '2px solid #2563eb' : '1.5px solid #cbd5e1' }}; background: {{ old('tipo_articulo', $modalArticulo->tipo_articulo ?? 'pesable') === 'pesable' ? '#eff6ff' : '#fff' }}; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                <input type="radio" name="tipo_articulo" value="pesable" id="tipo-articulo-pesable" @checked(old('tipo_articulo', $modalArticulo->tipo_articulo ?? 'pesable') === 'pesable') onchange="onTipoArticuloChange()" style="margin-top: 0.2rem;">
                <div>
                    <div style="font-weight: 800; color: {{ old('tipo_articulo', $modalArticulo->tipo_articulo ?? 'pesable') === 'pesable' ? '#1e40af' : '#334155' }}; font-size: 0.92rem;">
                        <i class="fa-solid fa-scale-balanced"></i> Pesable a Granel ({{ strtoupper($unidadPeso) }})
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.15rem;">
                        Carnes, pollo, vísceras y cortes que se pesan con báscula o código GS1 con decimales.
                    </div>
                </div>
            </label>

            <label id="label-tipo-unidad" style="display: flex; align-items: flex-start; gap: 0.6rem; padding: 0.75rem; border: {{ old('tipo_articulo', $modalArticulo->tipo_articulo ?? '') === 'unidad' ? '2px solid #ea580c' : '1.5px solid #cbd5e1' }}; background: {{ old('tipo_articulo', $modalArticulo->tipo_articulo ?? '') === 'unidad' ? '#fff7ed' : '#fff' }}; border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                <input type="radio" name="tipo_articulo" value="unidad" id="tipo-articulo-unidad" @checked(old('tipo_articulo', $modalArticulo->tipo_articulo ?? '') === 'unidad') onchange="onTipoArticuloChange()" style="margin-top: 0.2rem;">
                <div>
                    <div style="font-weight: 800; color: {{ old('tipo_articulo', $modalArticulo->tipo_articulo ?? '') === 'unidad' ? '#c2410c' : '#334155' }}; font-size: 0.92rem;">
                        <i class="fa-solid fa-box"></i> Simple / Por Unidad (UND)
                    </div>
                    <div style="font-size: 0.75rem; color: #64748b; margin-top: 0.15rem;">
                        Salsas, condimentos, especias, carbón, bebidas y artículos que se venden por pieza.
                    </div>
                </div>
            </label>
        </div>
    </div>

    <div class="input-group" id="group-codigo-field">
        <label id="label-codigo-field">Código Proveedor / Barras</label>
        <input type="text" class="input-modern" name="codigo" id="field-codigo" value="{{ old('codigo', $modalArticulo->codigo ?? '') }}" required placeholder="Código de barras o SKU">
    </div>
    <div class="input-group" id="group-codigo-cliente">
        <label>Código cliente (Báscula)</label>
        <input type="text" class="input-modern" name="codigo_cliente" id="field-codigo-cliente" value="{{ old('codigo_cliente', $modalArticulo->codigo_cliente ?? '') }}" placeholder="Código interno o del cliente" oninput="updateScaleCodePreview()">
        <div id="scale-code-preview" style="font-size: 0.85rem; color: #64748b; margin-top: 0.25rem;"></div>
    </div>
    <div class="input-group" id="group-item">
        <label>Nº ITEM (5 dígitos)</label>
        <input type="text" class="input-modern" name="item" id="field-item" value="{{ old('item', $modalArticulo->item ?? '') }}" placeholder="Ej. 00449">
    </div>
    <div class="input-group">
        <label>Familia</label>
        <select class="input-modern" name="familia_id" required>
            <option value="">Selecciona una familia</option>
            @foreach($familias as $familia)
                <option value="{{ $familia->id }}" @selected((string) old('familia_id', $modalArticulo->familia_id ?? '') === (string) $familia->id)>{{ $familia->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div class="input-group">
        <label>Descripción</label>
        <input type="text" class="input-modern" name="descripcion" value="{{ old('descripcion', $modalArticulo->descripcion ?? '') }}" required>
    </div>
    <div class="input-group">
        <label>Precio de Compra (Costo)</label>
        <input type="number" step="0.01" min="0" class="input-modern" name="precio_compra" id="field-precio-compra" value="{{ old('precio_compra', $modalArticulo->precio_compra ?? '') }}" placeholder="Costo unitario/peso">
    </div>
    <div class="input-group">
        <label>Precio Venta {{ $usarImpuestos ? 'sin IVA' : '' }}</label>
        <input type="number" step="0.01" min="0" class="input-modern" name="precio_sin_iva" id="field-precio-sin-iva" value="{{ old('precio_sin_iva', $modalArticulo->precio_sin_iva ?? '') }}" required oninput="calculatePvp()">
    </div>
    @if($usarImpuestos)
        @if($ivaGlobalEnabled)
            <input type="hidden" name="iva" id="field-iva" value="{{ $ivaGlobalRate }}">
            <div class="input-group">
                <label>IVA global aplicado</label>
                <input type="text" class="input-modern" value="{{ number_format($ivaGlobalRate, 2) }}%" disabled>
            </div>
        @else
            <div class="input-group">
                <label>IVA (%)</label>
                <input type="number" step="0.01" min="0" max="100" class="input-modern" name="iva" id="field-iva" value="{{ old('iva', $modalArticulo->iva ?? 21) }}" required oninput="calculatePvp()">
            </div>
            <div class="input-group" style="display: flex; align-items: center; gap: 0.65rem; padding-top: 1.8rem;">
                <input type="checkbox" id="aplica_iva" name="aplica_iva" value="1" @checked((bool) old('aplica_iva', $modalArticulo->aplica_iva ?? true)) onchange="calculatePvp()">
                <label for="aplica_iva" style="margin: 0; color: var(--text-main);">Este producto aplica IVA</label>
            </div>
        @endif
        <div class="input-group">
            <label>PVP (Precio con IVA)</label>
            <input type="number" step="0.01" min="0" class="input-modern" name="pvp" id="field-pvp" value="{{ old('pvp', $modalArticulo->pvp ?? '') }}" placeholder="Se calcula si lo dejas vacío">
        </div>
    @endif

    <!-- PRECIOS ADICIONALES -->
    <div style="grid-column: 1 / -1; margin-top: 0.5rem; padding: 1.1rem; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <label style="font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
                    <i class="fa-solid fa-tags" style="color: var(--primary);"></i> Precios Adicionales / Configurar más precios
                </label>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.15rem;">Configura listas de precios alternativas para este artículo (ej. Mayoreo, Especial, Restaurante).</div>
            </div>
            <button type="button" class="btn-modern btn-secondary" style="width: auto; padding: 0.4rem 0.9rem; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 0.4rem;" onclick="addPrecioAdicionalRow()">
                <i class="fa-solid fa-plus"></i> Añadir precio adicional
            </button>
        </div>

        <!-- Encabezados de columnas -->
        <div style="display: grid; grid-template-columns: 2fr 1.2fr 42px; gap: 0.5rem; margin-bottom: 0.35rem; padding: 0 0.2rem;">
            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Nombre / Tipo de Precio</span>
            <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Monto ($)</span>
            <span></span>
        </div>

        <div id="precios-adicionales-container" style="display: flex; flex-direction: column; gap: 0.5rem;">
            @php
                $oldPrecios = old('precios_adicionales', $modalArticulo->precios_adicionales ?? []);
            @endphp
            @if(is_array($oldPrecios) && count($oldPrecios) > 0)
                @foreach($oldPrecios as $idx => $p)
                    <div class="precio-adicional-row" style="display: grid; grid-template-columns: 2fr 1.2fr 42px; gap: 0.5rem; align-items: center;">
                        <input type="text" class="input-modern" name="precios_adicionales[{{ $idx }}][nombre]" value="{{ $p['nombre'] ?? '' }}" placeholder="Ej. Precio Mayoreo" style="width: 100%; box-sizing: border-box;">
                        <input type="number" step="0.01" min="0" class="input-modern" name="precios_adicionales[{{ $idx }}][precio]" value="{{ $p['precio'] ?? '' }}" placeholder="0.00" style="width: 100%; box-sizing: border-box;">
                        <button type="button" onclick="this.closest('.precio-adicional-row').remove()" style="width: 40px; height: 40px; border-radius: 8px; border: 1px solid #fecaca; background: #fff; color: #ef4444; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;" title="Eliminar este precio" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">&times;</button>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

<script>
function calculatePvp() {
    const precioBaseInput = document.getElementById('field-precio-sin-iva');
    const pvpInput = document.getElementById('field-pvp');
    if (!precioBaseInput || !pvpInput) return;

    const precioBase = parseFloat(precioBaseInput.value) || 0;
    
    // Variables de configuración obtenidas de Blade
    const usarImpuestos = {{ $usarImpuestos ? 'true' : 'false' }};
    const ivaGlobalEnabled = {{ $ivaGlobalEnabled ? 'true' : 'false' }};
    const ivaGlobalRate = {{ $ivaGlobalRate }};
    
    let iva = 0;
    if (usarImpuestos) {
        if (ivaGlobalEnabled) {
            iva = ivaGlobalRate;
        } else {
            const aplicaIvaCheckbox = document.getElementById('aplica_iva');
            const ivaInput = document.getElementById('field-iva');
            if (aplicaIvaCheckbox && aplicaIvaCheckbox.checked && ivaInput) {
                iva = parseFloat(ivaInput.value) || 0;
            }
        }
    }

    const pvp = precioBase * (1 + (iva / 100));
    pvpInput.value = pvp > 0 ? pvp.toFixed(2) : '';
}

function updateScaleCodePreview() {
    const ccInput = document.getElementById('field-codigo-cliente');
    const stockInput = document.getElementById('field-stock');
    const preview = document.getElementById('scale-code-preview');
    if(!ccInput || !stockInput || !preview) return;

    let cc = ccInput.value.trim();
    if (cc.length > 0 && cc.length <= 6 && /^\d+$/.test(cc)) {
        let padCc = cc.padStart(6, '0');
        let weight = parseFloat(stockInput.value) || 0;
        let weightInt = Math.round(weight * 100);
        let weightStr = weightInt.toString().padStart(5, '0');
        
        preview.innerHTML = `<i class="fa-solid fa-barcode"></i> Código de Báscula: <strong>${padCc}${weightStr}</strong>`;
        preview.style.color = '#059669'; // Emerald 600
    } else {
        preview.innerHTML = 'Ingresa un código cliente de hasta 6 dígitos numéricos para previsualizar el código de báscula.';
        preview.style.color = '#64748b';
    }
}

let precioAdicionalIndex = {{ is_array(old('precios_adicionales', $modalArticulo->precios_adicionales ?? [])) ? count(old('precios_adicionales', $modalArticulo->precios_adicionales ?? [])) : 0 }};

function addPrecioAdicionalRow(nombre = '', precio = '') {
    const container = document.getElementById('precios-adicionales-container');
    if (!container) return;
    const idx = precioAdicionalIndex++;
    const row = document.createElement('div');
    row.className = 'precio-adicional-row';
    row.style.cssText = 'display: grid; grid-template-columns: 2fr 1.2fr 42px; gap: 0.5rem; align-items: center; width: 100%;';
    row.innerHTML = `
        <input type="text" class="input-modern" name="precios_adicionales[${idx}][nombre]" value="${nombre}" placeholder="Ej. Precio Mayoreo" style="width: 100%; box-sizing: border-box;">
        <input type="number" step="0.01" min="0" class="input-modern" name="precios_adicionales[${idx}][precio]" value="${precio}" placeholder="0.00" style="width: 100%; box-sizing: border-box;">
        <button type="button" onclick="this.closest('.precio-adicional-row').remove()" style="width: 40px; height: 40px; border-radius: 8px; border: 1px solid #fecaca; background: #fff; color: #ef4444; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;" title="Eliminar este precio" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">&times;</button>
    `;
    container.appendChild(row);
}

function clearPreciosAdicionales() {
    const container = document.getElementById('precios-adicionales-container');
    if (container) container.innerHTML = '';
}

function onTipoArticuloChange() {
    const pesableRadio = document.getElementById('tipo-articulo-pesable');
    const isPesable = pesableRadio ? pesableRadio.checked : true;
    const labelPesable = document.getElementById('label-tipo-pesable');
    const labelUnidad = document.getElementById('label-tipo-unidad');
    const stockLabel = document.getElementById('label-stock-field');
    const stockMinLabel = document.getElementById('label-stock-min-field');
    const stockInput = document.getElementById('field-stock');
    const stockMinInput = document.getElementById('field-stock-minimo');
    const scalePreviewContainer = document.getElementById('scale-code-preview');
    const labelCodigo = document.getElementById('label-codigo-field');
    const groupCodigoCliente = document.getElementById('group-codigo-cliente');
    const groupItem = document.getElementById('group-item');
    const fieldCodigo = document.getElementById('field-codigo');
    const unitSymbol = (window.unidadPeso || '{{ $unidadPeso }}').toUpperCase();

    if (isPesable) {
        if (labelPesable) {
            labelPesable.style.borderColor = '#2563eb';
            labelPesable.style.background = '#eff6ff';
            const titleEl = labelPesable.querySelector('div div');
            if (titleEl) titleEl.style.color = '#1e40af';
        }
        if (labelUnidad) {
            labelUnidad.style.borderColor = '#cbd5e1';
            labelUnidad.style.background = '#fff';
            const titleEl = labelUnidad.querySelector('div div');
            if (titleEl) titleEl.style.color = '#334155';
        }
        if (labelCodigo) labelCodigo.innerText = 'Código Proveedor / Barras (GS1-128)';
        if (fieldCodigo) fieldCodigo.placeholder = 'Código de caja o proveedor';
        if (groupCodigoCliente) groupCodigoCliente.style.display = '';
        if (groupItem) groupItem.style.display = '';

        if (stockLabel) stockLabel.innerText = 'Peso / stock actual (' + unitSymbol + ')';
        if (stockMinLabel) stockMinLabel.innerText = 'Stock mínimo (' + unitSymbol + ')';
        if (stockInput) stockInput.step = '0.001';
        if (stockMinInput) stockMinInput.step = '0.001';
        updateScaleCodePreview();
    } else {
        if (labelPesable) {
            labelPesable.style.borderColor = '#cbd5e1';
            labelPesable.style.background = '#fff';
            const titleEl = labelPesable.querySelector('div div');
            if (titleEl) titleEl.style.color = '#334155';
        }
        if (labelUnidad) {
            labelUnidad.style.borderColor = '#ea580c';
            labelUnidad.style.background = '#fff7ed';
            const titleEl = labelUnidad.querySelector('div div');
            if (titleEl) titleEl.style.color = '#c2410c';
        }
        if (labelCodigo) labelCodigo.innerText = 'Código de Barras';
        if (fieldCodigo) fieldCodigo.placeholder = 'Escanea o ingresa el código de barras';
        if (groupCodigoCliente) groupCodigoCliente.style.display = 'none';
        if (groupItem) groupItem.style.display = 'none';

        if (stockLabel) stockLabel.innerText = 'Cantidad / stock actual (UND)';
        if (stockMinLabel) stockMinLabel.innerText = 'Stock mínimo (UND)';
        if (stockInput) stockInput.step = '1';
        if (stockMinInput) stockMinInput.step = '1';
        if (scalePreviewContainer) {
            scalePreviewContainer.innerHTML = '';
        }
    }
}

// Ejecutar al iniciar
document.addEventListener('DOMContentLoaded', () => {
    updateScaleCodePreview();
    onTipoArticuloChange();
});
</script>
    <div class="input-group">
        <label id="label-stock-field">Peso / stock actual ({{ strtoupper($unidadPeso) }})</label>
        <input type="number" step="0.001" min="0" class="input-modern" name="stock" id="field-stock" value="{{ old('stock', $modalArticulo->stock ?? 0) }}" required oninput="updateScaleCodePreview()">
    </div>
    <div class="input-group">
        <label id="label-stock-min-field">Stock mínimo ({{ strtoupper($unidadPeso) }})</label>
        <input type="number" step="0.001" min="0" class="input-modern" name="stock_minimo" id="field-stock-minimo" value="{{ old('stock_minimo', $modalArticulo->stock_minimo ?? 0) }}" required>
    </div>
    <div class="input-group">
        <label>Estado</label>
        <select class="input-modern" name="estado" required>
            @php($currentState = old('estado', $modalArticulo->estado ?? 'activo'))
            <option value="activo" @selected($currentState === 'activo')>Activo</option>
            <option value="sin_stock" @selected($currentState === 'sin_stock')>Sin stock</option>
            <option value="inactivo" @selected($currentState === 'inactivo')>Inactivo</option>
        </select>
    </div>
    <div class="input-group" style="grid-column: 1 / -1;">
        <label>Imagen del producto</label>
        @if(isset($modalArticulo) && $modalArticulo->imagen)
            <div style="margin-bottom: 0.5rem;">
                <img src="{{ asset('storage/' . $modalArticulo->imagen) }}" alt="Imagen actual" style="max-height: 80px; border-radius: 4px;">
            </div>
        @endif
        <input type="file" class="input-modern" name="imagen" accept="image/*">
        <small style="color: var(--text-muted);">Formatos soportados: JPG, PNG, WEBP. Tamaño máximo: 2MB.</small>
    </div>
</div>