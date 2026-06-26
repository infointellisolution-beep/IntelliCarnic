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
            'estado' => $articulo->estado,
            'precio_sin_iva' => $articulo->precio_sin_iva,
            'iva' => $articulo->iva,
            'pvp' => $articulo->pvp,
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

<div class="modal-overlay" id="familias-list-modal" aria-hidden="true">
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
                <div class="stat-value">${{ number_format($articulos->sum('pvp'), 2) }}</div>
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
        <form class="toolbar-search input-group" style="margin-bottom: 0;" method="GET" action="{{ route('articulos.index') }}">
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <input type="text" class="input-modern" name="search" value="{{ $currentSearch }}" placeholder="Buscar por código, código cliente, descripción o familia...">
                <button type="submit" class="btn-modern btn-secondary" style="width: auto;">Buscar</button>
                @if($currentSearch !== '')
                    <a href="{{ route('articulos.index') }}" class="btn-modern btn-secondary" style="width: auto; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Limpiar</a>
                @endif
            </div>
        </form>
        <div class="toolbar-actions">
            <span class="toolbar-chip"><i class="fa-solid fa-layer-group"></i> {{ $articulos->count() }} resultados</span>
            <span class="toolbar-chip"><i class="fa-solid fa-database"></i> Fuente: DB</span>
        </div>
    </div>

    <table class="modern-table">
        <thead>
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Precio S/IVA</th>
                <th>IVA</th>
                <th>PVP</th>
                <th>Stock</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($articulos as $articulo)
                @php
                    $articuloModalData = [
                        'id' => $articulo->id,
                        'codigo' => $articulo->codigo,
                        'codigo_cliente' => $articulo->codigo_cliente,
                        'descripcion' => $articulo->descripcion,
                        'familia_id' => $articulo->familia_id,
                        'familia_nombre' => $articulo->familia?->nombre,
                        'precio_sin_iva' => $articulo->precio_sin_iva,
                        'iva' => $articulo->iva,
                        'pvp' => $articulo->pvp,
                        'stock' => $articulo->stock,
                        'estado' => $articulo->estado,
                    ];
                @endphp
                <tr>
                    <td style="font-weight: 700;">{{ $articulo->codigo }}</td>
                    <td>
                        <div style="font-weight: 600;">{{ $articulo->descripcion }}</div>
                        <div style="font-size: 0.82rem; color: var(--text-muted);">Código cliente: {{ $articulo->codigo_cliente ?? 'Sin código' }}</div>
                        <div style="font-size: 0.82rem; color: var(--text-muted);">{{ $articulo->familia?->nombre ?: 'Sin familia' }}</div>
                    </td>
                    <td>${{ number_format($articulo->precio_sin_iva, 2) }}</td>
                    <td>{{ number_format($articulo->iva, 0) }}%</td>
                    <td style="font-weight: 700;">${{ number_format($articulo->pvp, 2) }}</td>
                    <td>
                        <span class="badge {{ $articulo->stock > 10 ? 'badge-success' : ($articulo->stock > 0 ? 'badge-warning' : 'badge-danger') }}">
                            {{ number_format($articulo->stock, 3) }} kg
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $articulo->estado === 'activo' ? 'badge-success' : ($articulo->estado === 'sin_stock' ? 'badge-danger' : 'badge-warning') }}">
                            {{ ucfirst(str_replace('_', ' ', $articulo->estado)) }}
                        </span>
                    </td>
                    <td>
                        <div class="flex-gap" style="gap: 0.35rem;">
                            <button type="button" class="btn-modern js-articulo-details" data-articulo-detalles='@json($articuloModalData)' style="width: auto; padding: 0.45rem 0.55rem; background: transparent; color: var(--text-main); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center;"><i class="fa-solid fa-eye"></i></button>
                            <button type="button" class="btn-modern js-articulo-edit" data-modal-title="Editar artículo" data-modal-action="{{ route('articulos.update', $articulo) }}" data-articulo='@json($articuloModalData)' style="width: auto; padding: 0.45rem 0.55rem; background: transparent; color: var(--primary); border: 1px solid var(--border-color); display: inline-flex; align-items: center; justify-content: center;"><i class="fa-solid fa-pen"></i></button>
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

<div class="modal-overlay" id="detalle-modal" aria-hidden="true">
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

                <div class="detail-metric">
                    <div class="detail-metric-label">Código cliente</div>
                    <div class="detail-metric-value" id="detalle-codigo-cliente">CL-1001</div>
                </div>

                <div class="detail-metric">
                    <div class="detail-metric-label">Descripción</div>
                    <div class="detail-metric-value" id="detalle-descripcion">Monitor Dell 27" 4K</div>
                </div>

                <div class="detail-list">
                    <div class="detail-list-item">
                        <div><strong>Familia</strong><span>Clasificación comercial</span></div>
                        <strong id="detalle-familia">Informática</strong>
                    </div>
                    <div class="detail-list-item">
                        <div><strong>Precio sin IVA</strong><span>Base de cálculo</span></div>
                        <strong id="detalle-precio">$350.00</strong>
                    </div>
                    <div class="detail-list-item">
                        <div><strong>IVA</strong><span>Impuesto aplicado</span></div>
                        <strong id="detalle-iva">15%</strong>
                    </div>
                    <div class="detail-list-item">
                        <div><strong>PVP</strong><span>Precio de venta</span></div>
                        <strong id="detalle-pvp">$423.50</strong>
                    </div>
                    <div class="detail-list-item">
                        <div><strong>Stock</strong><span>Disponibilidad actual</span></div>
                        <strong id="detalle-stock">24 unidades</strong>
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

        <form id="articulo-modal-form" action="{{ $modalAction }}" method="POST" class="modal-form">
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
    window.articulosCatalogo = @json($catalogoModalArticulos);
    window.familiasCatalogo = @json($familiasCatalogo);
</script>
<script src="/js/articulos-modal.js"></script>
@endsection
