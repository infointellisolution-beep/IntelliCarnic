@extends('layouts.app')

@section('title', 'Registrar Compra de Mercancía - IntelliCarnic')

@section('content')
<div class="content-header">
    <div>
        <h1 class="page-title">Registrar Compra / Recepción de Mercancía</h1>
        <p class="page-subtitle">Escanea códigos de barras GS1-128 de cajas mayoristas o ingresa productos manualmente.</p>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success" style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-circle-check"></i> {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem;">
        <ul style="margin: 0; padding-left: 1.2rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('compras.store') }}" method="POST" id="form-compra">
    @csrf

    <!-- Datos del Proveedor y Factura -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="hero-kicker"><i class="fa-solid fa-file-invoice-dollar"></i> Datos del Comprobante</div>
        <div class="form-grid" style="margin-top: 1rem; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
            
            <div class="input-group">
                <label>Proveedor <span style="color: var(--accent);">*</span></label>
                <div style="display: flex; gap: 0.5rem;">
                    <select name="proveedor_id" id="select-proveedor" class="input-modern" style="flex: 1;">
                        <option value="">-- Seleccionar o Escribir Abajo --</option>
                        @foreach($proveedores as $p)
                            <option value="{{ $p->id }}" data-nombre="{{ $p->nombre }}">{{ $p->nombre }} ({{ $p->identificacion ?: 'Sin ID' }})</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn-modern btn-secondary" id="btn-nuevo-proveedor" title="Agregar Proveedor Nuevo" style="width: auto;">
                        <i class="fa-solid fa-plus"></i>
                    </button>
                </div>
                <input type="text" name="proveedor_nombre" id="input-proveedor-nombre" class="input-modern" placeholder="O escribe el nombre del proveedor aquí..." value="{{ old('proveedor_nombre') }}" required style="margin-top: 0.5rem;">
            </div>

            <div class="input-group">
                <label>Nº Factura / Comprobante</label>
                <input type="text" name="numero_factura" class="input-modern" placeholder="Ej. FAC-99482" value="{{ old('numero_factura') }}">
            </div>

            <div class="input-group">
                <label>Fecha de Compra <span style="color: var(--accent);">*</span></label>
                <input type="datetime-local" name="fecha_compra" class="input-modern" value="{{ old('fecha_compra', now()->format('Y-m-d\TH:i')) }}" required>
            </div>

        </div>
    </div>

    <!-- Escáner Inteligente y Búsqueda de Productos -->
    <div class="card" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, rgba(37,99,235,0.03) 0%, rgba(249,115,22,0.03) 100%); border: 2px dashed var(--border-color);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="flex: 1; min-width: 280px;">
                <div class="hero-kicker"><i class="fa-solid fa-barcode"></i> Escáner Láser de Cajas (GS1-128 / Báscula)</div>
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-top: 0.25rem;">Escanea el código de barras mayorista</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                    El sistema extraerá automáticamente el SKU/ITEM, lote, serie y convertirá el peso a <strong>{{ strtoupper($settings['unidad_peso'] ?? 'lb') }}</strong> en tiempo real.
                </p>
            </div>
            <div style="flex: 1.5; min-width: 300px;">
                <input type="text" id="input-scan-barcode" class="input-modern" placeholder="Haz clic aquí o escanea directo con el láser..." autofocus style="font-size: 1.1rem; font-weight: 600; font-family: monospace; border-color: var(--accent);">
                <div id="scan-feedback" style="font-size: 0.85rem; margin-top: 0.4rem; font-weight: 600; min-height: 1.2rem;"></div>
            </div>
        </div>

        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 1.25rem 0;">

        <!-- Búsqueda Manual alternativa -->
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <span style="font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">O Agregar Manualmente:</span>
            <select id="select-articulo-manual" class="input-modern" style="flex: 1; min-width: 250px;">
                <option value="">-- Buscar producto por nombre o código --</option>
                @foreach($articulos as $art)
                    <option value="{{ $art->id }}" 
                            data-codigo="{{ $art->codigo }}" 
                            data-descripcion="{{ $art->descripcion }}" 
                            data-costo="{{ $art->precio_sin_iva }}">
                        {{ $art->descripcion }} (SKU: {{ $art->codigo }} - Costo: ${{ number_format($art->precio_sin_iva, 2) }})
                    </option>
                @endforeach
            </select>
            <button type="button" id="btn-add-manual" class="btn-modern btn-secondary" style="width: auto;">
                <i class="fa-solid fa-plus"></i> Agregar Fila
            </button>
        </div>
    </div>

    <!-- Tabla de Ítems en la Compra -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 700;">Detalle de Mercancía Recibida</h3>
            <span id="items-count-badge" class="badge badge-info" style="font-size: 0.9rem;">0 ítems en lista</span>
        </div>

        <div class="table-responsive">
            <table class="table-modern" id="table-compra-items">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Producto / SKU</th>
                        <th>Lote / Serie</th>
                        <th style="width: 130px;">Fecha Venc.</th>
                        <th style="width: 120px;">Peso/Cant ({{ strtoupper($settings['unidad_peso'] ?? 'lb') }})</th>
                        <th style="width: 120px;">Costo Unit. ($)</th>
                        <th style="width: 120px;">Subtotal ($)</th>
                        <th style="width: 50px; text-align: center;">Acción</th>
                    </tr>
                </thead>
                <tbody id="tbody-compra-items">
                    <tr id="tr-empty-compra">
                        <td colspan="8" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            <i class="fa-solid fa-barcode" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.4;"></i>
                            <p style="font-weight: 600;">Ningún producto escaneado aún</p>
                            <p style="font-size: 0.85rem;">Utiliza el lector de código de barras o la búsqueda manual arriba para agregar mercancía.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Totales y Resumen -->
    <div class="card" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
        <div style="flex: 1; min-width: 250px;">
            <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Observaciones o Notas del Comprobante</label>
            <textarea name="observaciones" class="input-modern" rows="2" placeholder="Ej. Mercancía entregada en camión #4 en buen estado..."></textarea>
        </div>

        <div style="text-align: right; min-width: 220px;">
            <div style="font-size: 0.9rem; color: var(--text-muted); font-weight: 600;">TOTAL DE LA COMPRA</div>
            <div style="font-size: 2.2rem; font-weight: 800; color: var(--accent);" id="label-total-compra">$0.00</div>
            <button type="submit" id="btn-guardar-compra" class="btn-modern btn-accent" style="margin-top: 0.75rem; width: 100%; font-size: 1.05rem; padding: 0.85rem;" disabled>
                <i class="fa-solid fa-check-circle"></i> Confirmar y Guardar Compra
            </button>
        </div>
    </div>
</form>

<!-- Modal Crear Proveedor Rápido -->
<div id="modal-nuevo-proveedor" class="modal-overlay" style="display: none;">
    <div class="modal-card" style="max-width: 500px; width: 100%; background: #ffffff; border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); border: 1px solid var(--border-color); overflow: hidden;">
        <div class="modal-header">
            <h2 style="font-size: 1.2rem; font-weight: 700;">Registrar Nuevo Proveedor</h2>
            <button type="button" class="modal-close" id="btn-close-modal-proveedor">&times;</button>
        </div>
        <form id="form-quick-proveedor">
            <div class="modal-body">
                <div class="input-group">
                    <label>Nombre / Razón Social <span style="color: var(--accent);">*</span></label>
                    <input type="text" id="prov-nombre" class="input-modern" required>
                </div>
                <div class="input-group" style="margin-top: 1rem;">
                    <label>Identificación / RUC / NIT</label>
                    <input type="text" id="prov-id" class="input-modern">
                </div>
                <div class="input-group" style="margin-top: 1rem;">
                    <label>Teléfono</label>
                    <input type="text" id="prov-tel" class="input-modern">
                </div>
            </div>
            <div class="modal-footer" style="display: flex; gap: 0.5rem; justify-content: flex-end; padding: 1rem; border-top: 1px solid var(--border-color);">
                <button type="button" class="btn-modern btn-secondary" id="btn-cancel-modal-proveedor">Cancelar</button>
                <button type="submit" class="btn-modern btn-accent">Guardar Proveedor</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    window.windowArticulos = @json($articulos);
    window.unidadPeso = '{{ strtolower($settings['unidad_peso'] ?? 'lb') }}';
</script>
<script src="{{ asset('js/compras.js') }}?v={{ time() }}"></script>
@endpush
@endsection
