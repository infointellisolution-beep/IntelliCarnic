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
    <div class="input-group">
        <label>Código Proveedor</label>
        <input type="text" class="input-modern" name="codigo" value="{{ old('codigo', $modalArticulo->codigo ?? '') }}" required>
    </div>
    <div class="input-group">
        <label>Código cliente</label>
        <input type="text" class="input-modern" name="codigo_cliente" id="field-codigo-cliente" value="{{ old('codigo_cliente', $modalArticulo->codigo_cliente ?? '') }}" placeholder="Código interno o del cliente" oninput="updateScaleCodePreview()">
        <div id="scale-code-preview" style="font-size: 0.85rem; color: #64748b; margin-top: 0.25rem;"></div>
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
        <label>Precio sin IVA</label>
        <input type="number" step="0.01" min="0" class="input-modern" name="precio_sin_iva" id="field-precio-sin-iva" value="{{ old('precio_sin_iva', $modalArticulo->precio_sin_iva ?? '') }}" required oninput="calculatePvp()">
    </div>
    @if(!$usarImpuestos)
        <div class="input-group">
            <label>IVA global aplicado</label>
            <input type="text" class="input-modern" value="0.00% (Desactivado)" disabled>
        </div>
    @elseif($ivaGlobalEnabled)
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
        <label>PVP</label>
        <input type="number" step="0.01" min="0" class="input-modern" name="pvp" id="field-pvp" value="{{ old('pvp', $modalArticulo->pvp ?? '') }}" placeholder="Se calcula si lo dejas vacío">
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
        let weightInt = Math.round(weight * 10);
        let weightStr = weightInt.toString().padStart(5, '0');
        
        preview.innerHTML = `<i class="fa-solid fa-barcode"></i> Código de Báscula: <strong>${padCc}${weightStr}</strong>`;
        preview.style.color = '#059669'; // Emerald 600
    } else {
        preview.innerHTML = 'Ingresa un código cliente de hasta 6 dígitos numéricos para previsualizar el código de báscula.';
        preview.style.color = '#64748b';
    }
}

// Ejecutar al iniciar
document.addEventListener('DOMContentLoaded', () => {
    updateScaleCodePreview();
});
</script>
    <div class="input-group">
        <label>Peso / stock actual ({{ strtoupper($unidadPeso) }})</label>
        <input type="number" step="0.001" min="0" class="input-modern" name="stock" id="field-stock" value="{{ old('stock', $modalArticulo->stock ?? 0) }}" required oninput="updateScaleCodePreview()">
    </div>
    <div class="input-group">
        <label>Stock mínimo</label>
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