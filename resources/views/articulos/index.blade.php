@extends('layouts.app')

@section('title', 'Artículos e Inventario')

@section('header-actions')
    <button type="button" class="btn-modern btn-secondary js-familia-create-open" style="width: auto; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
        <i class="fa-solid fa-folder-plus"></i> Nueva Familia
    </button>
    <button type="button" class="btn-modern btn-secondary js-familias-list-open" style="width: auto; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
        <i class="fa-solid fa-folder-tree"></i> Ver Familias
    </button>
    <button type="button" class="btn-modern btn-secondary js-stock-open" style="width: auto; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
        <i class="fa-solid fa-boxes-stacked"></i> Ajuste de Stock
    </button>
    <button type="button" class="btn-modern btn-accent js-articulo-create" data-modal-title="Nuevo artículo" data-modal-action="{{ route('articulos.store') }}" style="width: auto; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
        <i class="fa-solid fa-plus"></i> Nuevo Artículo
    </button>
@endsection

@section('content')
@php
    $usarImpuestos = $usarImpuestos ?? (bool) ((int) ($settings['usar_impuestos'] ?? 1));
@endphp
@php
    $currentSearch = $search ?? '';
    $modalArticuloId = old('articulo_id');
    $modalArticulo = $modalArticuloId ? $articulos->firstWhere('id', (int) $modalArticuloId) : null;
    $shouldOpenModal = $errors->any() && (old('codigo') !== null || $modalArticulo !== null);
    $shouldOpenStockModal = $errors->any() && old('stock_articulo_id') !== null;
    $shouldOpenFamiliaModal = $errors->any() && old('familia_nombre') !== null;
    $modalAction = $modalArticulo ? route('articulos.update', $modalArticulo) : route('articulos.store');
    $catalogoModalArticulos = $catalogoArticulos->map(function ($articulo) {
        return [
            'id' => $articulo->id,
            'codigo' => $articulo->codigo,
            'codigo_cliente' => $articulo->codigo_cliente,
            'descripcion' => $articulo->descripcion,
            'familia_id' => $articulo->familia_id,
            'familia_nombre' => $articulo->familia?->nombre,
            'stock' => $articulo->stock,
            'stock_minimo' => $articulo->stock_minimo,
            'estado' => $articulo->estado,
            'precio_sin_iva' => $articulo->precio_sin_iva,
            'iva' => $articulo->effective_iva,
            'pvp' => $articulo->effective_pvp,
        ];
    })->values();
    $familiasCatalogo = $familias->map(function ($familia) {
        return [
            'id' => $familia->id,
            'nombre' => $familia->nombre,
            'descripcion' => $familia->descripcion,
            'articulos_count' => $familia->articulos?->count() ?? 0,
        ];
    })->values();
@endphp

<section class="page-hero">
    <div class="hero-top">
        <div>
            <div class="hero-kicker"><i class="fa-solid fa-store"></i> Inventario profesional</div>
            <p class="hero-copy">Desde aquí puedes crear, editar y eliminar artículos reales guardados en la base de datos.</p>
        </div>
    </div>
</section>

@if(session('status'))
    <div class="card" style="margin-bottom: 1rem; background: #ecfdf5; border-color: #bbf7d0; color: #166534;">
        {{ session('status') }}
    </div>
@endif

<div class="modal-overlay" id="familia-modal" aria-hidden="true" @if($shouldOpenFamiliaModal) data-open="true" @endif>
    <div class="modal-backdrop js-familia-modal-close" role="presentation"></div>
    <div class="modal-dialog modal-dialog-details" role="dialog" aria-modal="true" aria-labelledby="familia-modal-title">
        <div class="modal-header">
            <div>
                <div class="hero-kicker"><i class="fa-solid fa-folder-plus"></i> Familias</div>
                <h2 class="hero-title" id="familia-modal-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Crear familia</h2>
            </div>
            <button type="button" class="modal-close js-familia-modal-close" aria-label="Cerrar modal">&times;</button>
        </div>

        <form action="{{ route('familias.store') }}" method="POST" class="modal-form">
            @csrf
            <div class="modal-body">
                <div class="input-group">
                    <label>Nombre de la familia</label>
                    <input type="text" class="input-modern" name="familia_nombre" value="{{ old('familia_nombre') }}" placeholder="Ej. Informática" required>
                </div>
                <div class="input-group">
                    <label>Descripción</label>
                    <input type="text" class="input-modern" name="familia_descripcion" value="{{ old('familia_descripcion') }}" placeholder="Descripción opcional">
                </div>
            </div>
            <div class="modal-footer">
                <div style="color: var(--text-muted); font-size: 0.9rem;">Cada artículo deberá pertenecer a una familia.</div>
                <div class="flex-gap" style="width: auto;">
                    <button type="button" class="btn-modern btn-secondary js-familia-modal-close" style="width: auto;">Cancelar</button>
                    <button type="submit" class="btn-modern btn-accent" style="width: auto;">Guardar familia</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="familias-list-modal">
    <div class="modal-backdrop js-familias-list-close" role="presentation"></div>
    <div class="modal-dialog modal-dialog-details" role="dialog" aria-modal="true" aria-labelledby="familias-list-title">
        <div class="modal-header">
            <div>
                <div class="hero-kicker"><i class="fa-solid fa-list"></i> Familias registradas</div>
                <h2 class="hero-title" id="familias-list-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Ver familias</h2>
            </div>
            <button type="button" class="modal-close js-familias-list-close" aria-label="Cerrar modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="familias-grid">
                @forelse ($familias as $familia)
                    <div class="familia-card">
                        <div class="familia-card-head">
                            <div class="familia-card-title">{{ $familia->nombre }}</div>
                            <span class="familia-card-badge">{{ $familia->articulos_count }} artículos</span>
                        </div>
                        <div class="familia-card-desc">{{ $familia->descripcion ?: 'Sin descripción' }}</div>
                    </div>
                @empty
                    <div class="stock-empty">Todavía no hay familias registradas.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<section class="stats-grid">
    <article class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-label">Total artículos</div>
                <div class="stat-value">{{ $articulos->count() }}</div>
            </div>
            <div class="stat-card-icon blue"><i class="fa-solid fa-boxes-stacked"></i></div>
        </div>
        <div class="stat-note">Registros en catálogo</div>
    </article>
    <article class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-label">Stock total</div>
                <div class="stat-value">{{ number_format($articulos->sum('stock'), 3) }}</div>
            </div>
            <div class="stat-card-icon green"><i class="fa-solid fa-warehouse"></i></div>
        </div>
        <div class="stat-note">Peso total disponible</div>
    </article>
    <article class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-label">Valor inventario</div>
                <div class="stat-value">${{ number_format($articulos->sum(fn($a) => $a->effective_pvp * $a->stock), 2) }}</div>
            </div>
            <div class="stat-card-icon orange"><i class="fa-solid fa-coins"></i></div>
        </div>
        <div class="stat-note">A precio de venta</div>
    </article>
    <article class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-label">Sin stock</div>
                <div class="stat-value">{{ $articulos->where('stock', 0)->count() }}</div>
            </div>
            <div class="stat-card-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>
        <div class="stat-note">Requieren reposición</div>
    </article>
</section>

<section class="card">
    <div class="toolbar">
        <div class="toolbar-search input-group" style="margin-bottom: 0;">
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <input type="text" class="input-modern" id="catalog-search-input" value="{{ $currentSearch }}" placeholder="Buscar por código, código cliente, descripción o familia...">
                <button type="button" class="btn-modern btn-secondary" style="width: auto;" onclick="document.getElementById('catalog-search-input').value=''; filterCatalog();">Limpiar</button>
            </div>
        </div>
        <div class="toolbar-actions">
            <span class="toolbar-chip"><i class="fa-solid fa-layer-group"></i> <span id="catalog-results-count">{{ $articulos->count() }}</span> resultados</span>
            <span class="toolbar-chip"><i class="fa-solid fa-database"></i> Fuente: DB</span>
        </div>
    </div>

    <table class="modern-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Precio {{ $usarImpuestos ? 'S/IVA' : 'Venta' }}</th>
                @if($usarImpuestos)
                    <th>IVA</th>
                    <th>PVP</th>
                @endif
                <th>Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="catalog-table-body">
            @forelse ($articulos as $articulo)
                @php
                    $articuloModalData = [
                        'id' => $articulo->id,
                        'codigo' => $articulo->codigo,
                        'codigo_cliente' => $articulo->codigo_cliente,
                        'item' => $articulo->item,
                        'aplica_iva' => $articulo->aplica_iva,
                        'descripcion' => $articulo->descripcion,
                        'familia_id' => $articulo->familia_id,
                        'familia_nombre' => $articulo->familia?->nombre,
                        'precio_compra' => $articulo->precio_compra,
                        'precio_sin_iva' => $articulo->precio_sin_iva,
                        'iva' => $articulo->effective_iva,
                        'pvp' => $articulo->effective_pvp,
                        'precios_adicionales' => $articulo->precios_adicionales,
                        'stock' => $articulo->stock,
                        'stock_minimo' => $articulo->stock_minimo,
                        'estado' => $articulo->estado,
                        'imagen_url' => $articulo->imagen ? asset('storage/' . $articulo->imagen) : null,
                        'lotes_desglose' => $articulo->lotes_desglose ?? [],
                    ];
                @endphp
                <tr class="catalog-row">
                    <td style="font-weight: 700; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $articulo->codigo }}">{{ Str::limit($articulo->codigo, 20, '...') }}</td>
                    <td>
                        <div style="font-weight: 600;">{{ $articulo->descripcion }}</div>
                        <div style="font-size: 0.82rem; color: var(--text-muted);">
                            Código cliente: <strong>{{ $articulo->codigo_cliente ?? 'Sin código' }}</strong>
                            @if($articulo->item)
                                | ITEM: <span class="badge badge-info" style="font-family: monospace; font-size: 0.78rem;">{{ $articulo->item }}</span>
                            @endif
                        </div>
                        <div style="font-size: 0.82rem; color: var(--text-muted);">{{ $articulo->familia?->nombre ?: 'Sin familia' }}</div>
                    </td>
                    <td>${{ number_format($articulo->precio_sin_iva, 2) }}</td>
                    @if($usarImpuestos)
                        <td>{{ number_format($articulo->effective_iva, 0) }}%</td>
                        <td style="font-weight: 700;">${{ number_format($articulo->effective_pvp, 2) }}</td>
                    @endif
                    <td>
                        @php
                            $badgeClass = 'badge-success';
                            if ($articulo->stock <= 0) {
                                $badgeClass = 'badge-danger';
                            } elseif ($articulo->stock <= $articulo->stock_minimo) {
                                $badgeClass = 'badge-warning';
                            }
                        @endphp
                        <span class="badge {{ $badgeClass }}">
                            {{ number_format($articulo->stock, 3) }} {{ $settings['unidad_peso'] ?? 'kg' }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $articulo->estado === 'activo' ? 'badge-success' : ($articulo->estado === 'sin_stock' ? 'badge-danger' : 'badge-warning') }}">
                            {{ ucfirst(str_replace('_', ' ', $articulo->estado)) }}
                        </span>
                    </td>
                    <td>
                        <div class="flex-gap" style="gap: 0.35rem;">
                            <button type="button" class="btn-modern js-articulo-details" data-articulo-detalles='@json($articuloModalData)' style="width: auto; padding: 0.45rem 0.55rem; background: transparent; color: var(--text-main); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center;" title="Ver detalles"><i class="fa-solid fa-eye"></i></button>
                            <button type="button" class="btn-modern js-articulo-print" data-articulo='@json($articuloModalData)' style="width: auto; padding: 0.45rem 0.55rem; background: transparent; color: #059669; border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center;" title="Imprimir etiqueta térmica"><i class="fa-solid fa-print"></i></button>
                            <button type="button" class="btn-modern js-articulo-edit" data-modal-title="Editar artículo" data-modal-action="{{ route('articulos.update', $articulo) }}" data-articulo='@json($articuloModalData)' style="width: auto; padding: 0.45rem 0.55rem; background: transparent; color: var(--primary); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center;" title="Editar"><i class="fa-solid fa-pen"></i></button>
                            <form action="{{ route('articulos.destroy', $articulo) }}" method="POST" onsubmit="return confirm('¿Eliminar este artículo?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-modern" style="width: auto; padding: 0.45rem 0.55rem; background: transparent; color: var(--danger); border: 1px solid var(--border-color);"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">Todavía no hay artículos registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<div class="modal-overlay" id="stock-modal" aria-hidden="true" @if($shouldOpenStockModal) data-open="true" @endif>
    <div class="modal-backdrop js-stock-modal-close" role="presentation"></div>
    <div class="modal-dialog modal-dialog-stock" role="dialog" aria-modal="true" aria-labelledby="stock-modal-title">
        <div class="modal-header">
            <div>
                <div class="hero-kicker"><i class="fa-solid fa-boxes-stacked"></i> Control de stock</div>
                <h2 class="hero-title" id="stock-modal-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Ajuste de stock</h2>
            </div>
            <button type="button" class="modal-close js-stock-modal-close" aria-label="Cerrar modal">&times;</button>
        </div>

        <form id="stock-modal-form" action="{{ route('articulos.stock-adjust') }}" method="POST" class="modal-form">
            @csrf
            <div class="modal-body">
                @if ($errors->has('stock_articulo_id') || $errors->has('cantidad') || $errors->has('movimiento'))
                    <div class="card" style="margin-bottom: 1rem; border-color: rgba(239, 68, 68, 0.35); background: #fef2f2; color: #7f1d1d;">
                        <strong>Revisa los datos del ajuste de stock.</strong>
                        <ul style="margin: 0.5rem 0 0; padding-left: 1.2rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="stock-search-panel">
                    <div class="input-group">
                        <label>Buscar producto</label>
                        <input type="text" id="stock-search-input" class="input-modern" placeholder="Escribe código o descripción..." value="{{ old('stock_search') }}">
                    </div>
                    <div id="stock-results" class="stock-results"></div>
                </div>

                <div class="stock-selected-card" id="stock-selected-card">
                    <div class="stock-selected-label">Producto seleccionado</div>
                    <div class="stock-selected-name" id="stock-selected-name">Selecciona un artículo para ajustar stock</div>
                    <div class="stock-selected-meta" id="stock-selected-meta">Código, código cliente, familia y peso actual se mostrarán aquí.</div>
                    <div id="stock-selected-barcode" style="font-size: 0.95rem; color: #059669; margin-top: 0.5rem; display: none; padding-top: 0.5rem; border-top: 1px dashed rgba(0,0,0,0.1);"></div>
                </div>

                <input type="hidden" name="stock_articulo_id" id="stock-articulo-id" value="{{ old('stock_articulo_id') }}">

                <div class="form-grid" style="margin-top: 1rem;">
                    <div class="input-group">
                        <label>Movimiento</label>
                        <select class="input-modern" name="movimiento" id="stock-movimiento" required>
                            <option value="sumar" @selected(old('movimiento') === 'sumar')>Sumar stock</option>
                            <option value="restar" @selected(old('movimiento') === 'restar')>Restar stock</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Cantidad</label>
                        <input type="number" step="0.001" min="0.001" class="input-modern" name="cantidad" id="stock-cantidad" value="{{ old('cantidad') }}" required>
                    </div>
                    <div class="input-group" style="grid-column: 1 / -1;">
                        <label>Motivo</label>
                        <input type="text" class="input-modern" name="motivo" id="stock-motivo" value="{{ old('motivo') }}" placeholder="Ej. Inventario físico, corrección, recepción de mercadería">
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <div style="color: var(--text-muted); font-size: 0.9rem;">Ajusta peso con búsqueda rápida y operación sumar o restar.</div>
                <div class="flex-gap" style="width: auto;">
                    <button type="button" class="btn-modern btn-secondary js-stock-modal-close" style="width: auto;">Cancelar</button>
                    <button type="submit" class="btn-modern btn-accent" style="width: auto;">Aplicar ajuste</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="detalle-modal">
    <div class="modal-backdrop js-detalle-modal-close" role="presentation"></div>
    <div class="modal-dialog modal-dialog-details" role="dialog" aria-modal="true" aria-labelledby="detalle-modal-title">
        <div class="modal-header">
            <div>
                <div class="hero-kicker"><i class="fa-solid fa-eye"></i> Vista detallada</div>
                <h2 class="hero-title" id="detalle-modal-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Detalle del artículo</h2>
            </div>
            <button type="button" class="modal-close js-detalle-modal-close" aria-label="Cerrar modal">&times;</button>
        </div>

        <div class="modal-body">
            <div class="detail-panel detail-panel-light">
                <div class="detail-chip-row">
                    <span class="detail-chip" id="detalle-codigo-chip">ART-001</span>
                    <span class="detail-chip orange" id="detalle-estado-chip">Activo</span>
                </div>

                <div class="detail-metric" id="detalle-codigo-proveedor-container" style="display: none; background: #eff6ff; border: 1px dashed #60a5fa; border-radius: 6px; padding: 0.5rem; margin-top: 1rem;">
                    <div class="detail-metric-label" style="color: #1d4ed8; margin-bottom: 0.25rem;"><i class="fa-solid fa-box"></i> Código Proveedor (Dinámico)</div>
                    <div class="detail-metric-value" id="detalle-codigo-proveedor" style="color: #2563eb; font-size: 1.05rem; font-family: monospace; letter-spacing: 0.5px;">(01)00000000000000(3202)000000</div>
                </div>

                <div class="detail-metric" style="margin-top: 1rem;">
                    <div class="detail-metric-label">Código cliente</div>
                    <div class="detail-metric-value" id="detalle-codigo-cliente">CL-1001</div>
                </div>

                <div class="detail-metric" id="detalle-codigo-bascula-container" style="display: none; background: #ecfdf5; border: 1px dashed #34d399; border-radius: 6px; padding: 0.5rem;">
                    <div class="detail-metric-label" style="color: #047857; margin-bottom: 0.25rem;"><i class="fa-solid fa-barcode"></i> Código Báscula (Dinámico)</div>
                    <div class="detail-metric-value" id="detalle-codigo-bascula" style="color: #059669; font-size: 1.1rem;">000000000000</div>
                </div>

                <div class="detail-metric">
                    <div class="detail-metric-label">Descripción</div>
                    <div class="detail-metric-value" id="detalle-descripcion">Monitor Dell 27" 4K</div>
                </div>

                <div class="detail-list">
                    <div class="detail-list-item" id="detalle-imagen-container" style="display: none; text-align: center; grid-column: 1 / -1; padding: 1rem 0;">
                        <img id="detalle-imagen" src="" alt="Imagen del producto" style="max-width: 100%; max-height: 200px; border-radius: 8px; object-fit: contain;">
                    </div>
                    <div class="detail-list-item">
                        <div><strong>Familia</strong><span>Clasificación comercial</span></div>
                        <strong id="detalle-familia">Informática</strong>
                    </div>
                    <div class="detail-list-item">
                        <div><strong>Precio de Compra</strong><span>Costo unitario / peso</span></div>
                        <strong id="detalle-precio-compra" style="color: #d97706;">$0.00</strong>
                    </div>
                    <div class="detail-list-item">
                        <div><strong>Precio de Venta {{ $usarImpuestos ? '(sin IVA)' : '' }}</strong><span>Base de cálculo</span></div>
                        <strong id="detalle-precio">$350.00</strong>
                    </div>
                    @if($usarImpuestos)
                        <div class="detail-list-item">
                            <div><strong>IVA</strong><span>Impuesto aplicado</span></div>
                            <strong id="detalle-iva">15%</strong>
                        </div>
                        <div class="detail-list-item">
                            <div><strong>PVP</strong><span>Precio de venta final</span></div>
                            <strong id="detalle-pvp">$423.50</strong>
                        </div>
                    @endif
                    <div class="detail-list-item">
                        <div><strong>Stock</strong><span>Disponibilidad actual</span></div>
                        <strong id="detalle-stock">24 unidades</strong>
                    </div>

                    <div id="detalle-lotes-container" style="grid-column: 1 / -1; margin-top: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="fa-solid fa-boxes-packing" style="color: var(--accent);"></i> Desglose de Lotes y Vencimientos Recibidos
                        </h4>
                        <div id="detalle-lotes-body" style="font-size: 0.85rem;">
                            <!-- Cargado dinámicamente por JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="articulo-modal" aria-hidden="true" @if($shouldOpenModal) data-open="true" @endif>
    <div class="modal-backdrop js-articulo-modal-close" role="presentation"></div>
    <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="articulo-modal-title">
        <div class="modal-header">
            <div>
                <div class="hero-kicker"><i class="fa-solid fa-box-open"></i> Mantenimiento de artículos</div>
                <h2 class="hero-title" id="articulo-modal-title" style="font-size: 1.5rem; margin-top: 0.25rem;">{{ $modalArticulo ? 'Editar artículo' : 'Nuevo artículo' }}</h2>
            </div>
            <button type="button" class="modal-close js-articulo-modal-close" aria-label="Cerrar modal">&times;</button>
        </div>

        @if ($errors->any())
            <div class="card" style="margin-bottom: 1rem; border-color: rgba(239, 68, 68, 0.35); background: #fef2f2; color: #7f1d1d;">
                <strong>Revisa los campos del formulario.</strong>
                <ul style="margin: 0.5rem 0 0; padding-left: 1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="articulo-modal-form" action="{{ $modalAction }}" method="POST" enctype="multipart/form-data" class="modal-form">
            @csrf
            <input type="hidden" name="articulo_id" id="articulo-modal-id" value="{{ old('articulo_id', $modalArticulo->id ?? '') }}">
            <input type="hidden" name="_method" id="articulo-modal-method" value="{{ $modalArticulo ? 'PUT' : '' }}" @unless($modalArticulo) disabled @endunless>

            <div class="modal-body">
                @include('articulos._fields', ['modalArticulo' => $modalArticulo, 'familias' => $familias])
            </div>

            <div class="modal-footer">
                <div style="color: var(--text-muted); font-size: 0.9rem;">El PVP se calcula si lo dejas vacío.</div>
                <div class="flex-gap" style="width: auto;">
                    <button type="button" class="btn-modern btn-secondary js-articulo-modal-close" style="width: auto;">Cancelar</button>
                    <button type="submit" class="btn-modern btn-accent" style="width: auto;">Guardar artículo</button>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    function filterCatalog() {
        const query = document.getElementById('catalog-search-input').value.toLowerCase().trim();
        const rows = document.querySelectorAll('#catalog-table-body .catalog-row');
        let count = 0;
        
        rows.forEach(row => {
            const textContent = row.textContent.toLowerCase();
            if (textContent.includes(query)) {
                row.style.display = '';
                count++;
            } else {
                row.style.display = 'none';
            }
        });
        
        document.getElementById('catalog-results-count').innerText = count;
    }
    
    document.getElementById('catalog-search-input')?.addEventListener('keyup', filterCatalog);

    window.articulosCatalogo = @json($catalogoModalArticulos);
    window.familiasCatalogo = @json($familiasCatalogo);
    window.unidadPeso = '{{ $settings['unidad_peso'] ?? 'kg' }}';
    window.modoInventario = '{{ $settings['modo_inventario'] ?? 'dinamico' }}';
</script>
<script src="{{ asset('js/articulos-modal.js') }}?v={{ time() }}"></script>
@endsection
