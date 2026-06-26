@php
    $modalArticulo = $modalArticulo ?? null;
    $familias = $familias ?? collect();
    $settings = $settings ?? [];
    $ivaGlobalEnabled = (int) ($settings['iva_global_enabled'] ?? 1) === 1;
    $ivaGlobalRate = (float) ($settings['iva_global_rate'] ?? 21);
    $unidadPeso = $settings['unidad_peso'] ?? 'kg';
@endphp

<div class="form-grid">
    <div class="input-group">
        <label>Código</label>
        <input type="text" class="input-modern" name="codigo" value="{{ old('codigo', $modalArticulo->codigo ?? '') }}" required>
    </div>
    <div class="input-group">
        <label>Código cliente</label>
        <input type="text" class="input-modern" name="codigo_cliente" value="{{ old('codigo_cliente', $modalArticulo->codigo_cliente ?? '') }}" placeholder="Código interno o del cliente" required>
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
        <input type="number" step="0.01" min="0" class="input-modern" name="precio_sin_iva" value="{{ old('precio_sin_iva', $modalArticulo->precio_sin_iva ?? '') }}" required>
    </div>
    @if($ivaGlobalEnabled)
        <input type="hidden" name="iva" value="{{ $ivaGlobalRate }}">
        <div class="input-group">
            <label>IVA global aplicado</label>
            <input type="text" class="input-modern" value="{{ number_format($ivaGlobalRate, 2) }}%" disabled>
        </div>
    @else
        <div class="input-group">
            <label>IVA (%)</label>
            <input type="number" step="0.01" min="0" max="100" class="input-modern" name="iva" value="{{ old('iva', $modalArticulo->iva ?? 21) }}" required>
        </div>
        <div class="input-group" style="display: flex; align-items: center; gap: 0.65rem; padding-top: 1.8rem;">
            <input type="checkbox" id="aplica_iva" name="aplica_iva" value="1" @checked((bool) old('aplica_iva', $modalArticulo->aplica_iva ?? true))>
            <label for="aplica_iva" style="margin: 0; color: var(--text-main);">Este producto aplica IVA</label>
        </div>
    @endif
    <div class="input-group">
        <label>PVP</label>
        <input type="number" step="0.01" min="0" class="input-modern" name="pvp" value="{{ old('pvp', $modalArticulo->pvp ?? '') }}" placeholder="Se calcula si lo dejas vacío">
    </div>
    <div class="input-group">
        <label>Peso / stock ({{ strtoupper($unidadPeso) }})</label>
        <input type="number" step="0.001" min="0" class="input-modern" name="stock" value="{{ old('stock', $modalArticulo->stock ?? 0) }}" required>
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
</div>