@extends('layouts.app')

@section('title', 'TPV Táctil')

@section('content')
@section('header-actions')
<div style="display: flex; gap: 0.5rem; align-items: center; padding: 0 1rem; margin-right: auto;">
    <button class="btn-icon-top"><i class="fa-solid fa-receipt"></i> Ticket</button>
    <button class="btn-icon-top"><i class="fa-solid fa-utensils"></i> Cocina</button>
    <button class="btn-icon-top"><i class="fa-solid fa-beer-mug-empty"></i> Barra</button>
    <div style="border-left: 1px solid var(--border-color); height: 30px; margin: 0 0.5rem;"></div>
    <button class="btn-icon-top"><i class="fa-solid fa-print"></i> Reimprimir Último</button>
    <button class="btn-icon-top"><i class="fa-solid fa-list-check"></i> Artículos Servidos</button>
    <button class="btn-icon-top"><i class="fa-solid fa-user"></i> Elegir Cliente</button>
    <button class="btn-icon-top"><i class="fa-solid fa-cash-register"></i> Abrir Cajón</button>
    <button class="btn-icon-top"><i class="fa-solid fa-file-invoice"></i> Reabrir Ticket</button>
    <button class="btn-icon-top"><i class="fa-solid fa-motorcycle"></i> Pedido a Domicilio</button>
    <button class="btn-icon-top"><i class="fa-solid fa-users"></i> Usuarios</button>
</div>
@endsection

<div class="tactil-layout" style="height: calc(100vh - 64px - 4rem);">
    <!-- Left Panel: Touch Grid (Familias y Artículos) -->
    <div class="tactil-left">
        <div class="flex-between" style="margin-bottom: 1rem;">
            <h3 style="margin:0;">Catálogo</h3>
            <div class="input-group" style="margin:0; width: 300px;">
                <input type="text" id="search-tactil" class="input-modern" placeholder="Buscar o escanear código..." onkeyup="filterTactilCatalog()">
            </div>
            <div style="font-size: 0.75rem; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 0.5rem; margin-left: auto;">
                <i class="fa-solid fa-microchip"></i> Analizador Inteligente Activo
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; flex: 1; overflow: hidden;">
            <!-- Columna de Familias -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 130px; overflow-y: auto; padding-right: 0.5rem; border-right: 1px solid var(--border-color);">
                <button class="btn-tactil familia" style="min-height: 80px;" onclick="filterFamilia(null, this)">
                    <i class="fa-solid fa-border-all" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></i>
                    TODOS
                </button>
                @foreach($familias as $familia)
                <button class="btn-tactil familia" style="min-height: 110px;" onclick="filterFamilia({{ $familia->id }}, this)">
                    <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    {{ $familia->nombre }}
                </button>
                @endforeach
            </div>

            <!-- Grilla de Artículos -->
            <div class="tactil-grid" style="flex: 1; padding-left: 0.5rem;">
                @foreach($articulos as $articulo)
                <button class="btn-tactil" style="min-height: 90px; height: auto; padding: 0.25rem; position: relative;" data-familia-id="{{ $articulo->familia_id }}" data-codigo="{{ $articulo->codigo }}" data-codigo-cliente="{{ $articulo->codigo_cliente }}" onclick='openScaleModal({!! json_encode($articulo) !!})'>
                    @if($articulo->imagen)
                        <div style="height: 40px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.25rem;">
                            <img src="{{ asset('storage/' . $articulo->imagen) }}" alt="{{ $articulo->descripcion }}" style="max-height: 100%; max-width: 100%; border-radius: 4px; object-fit: contain;">
                        </div>
                    @else
                        <!-- Simulamos íconos si no hay imagen -->
                        @if(stripos($articulo->descripcion, 'carne') !== false)
                            <i class="fa-solid fa-drumstick-bite" style="font-size: 1.5rem; color: #ef4444; margin-bottom: 0.25rem; margin-top: 0.25rem;"></i>
                        @elseif(stripos($articulo->descripcion, 'pollo') !== false)
                            <i class="fa-solid fa-kiwi-bird" style="font-size: 1.5rem; color: #f59e0b; margin-bottom: 0.25rem; margin-top: 0.25rem;"></i>
                        @else
                            <i class="fa-solid fa-image" style="font-size: 1.5rem; color: #94a3b8; margin-bottom: 0.25rem; margin-top: 0.25rem;"></i>
                        @endif
                    @endif
                    
                    <div style="font-weight: 600; font-size: 0.75rem; margin-bottom: 0.15rem; line-height: 1.1; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">{{ $articulo->descripcion }}</div>
                    <div style="color: var(--text-muted); margin-top: 0.25rem; font-weight: 700;">${{ number_format($articulo->pvp, 2) }}</div>
                    @if($articulo->stock !== null && $articulo->stock !== '')
                        <div id="tactil-stock-{{ $articulo->id }}" style="font-size: 0.8rem; color: #10b981; margin-top: 0.25rem; font-weight: 600;">
                            Stock: {{ floatval($articulo->stock) }} {{ strtoupper($settings['unidad_peso'] ?? 'kg') }}
                        </div>
                    @endif
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Right Panel: Ticket & Checkout -->
    <div class="tactil-right">
        <!-- Ticket Info Header -->
        <div class="flex-between" style="padding: 1rem; background: #f8fafc; border-bottom: 1px solid var(--border-color);">
            <div style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-user" style="color: var(--primary);"></i> Cliente Contado
            </div>
            <div style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem; color: var(--text-muted);">
                <i class="fa-solid fa-chair"></i> Elegir Mesa (F2)
            </div>
        </div>

        <!-- Order Lines -->
        <div class="ticket-area">
            <table class="modern-table" style="margin: 0;">
                <thead style="background: #f8fafc; position: sticky; top: 0;">
                    <tr>
                        <th style="width: 50px; text-align: center;">Can.</th>
                        <th>Artículo</th>
                        <th style="text-align: center;">%Dto.</th>
                        <th style="text-align: right;">P. c/IVA</th>
                    </tr>
                </thead>
                <tbody id="ticket-body-tactil">
                    <!-- Fila de ejemplo vacía como en la captura nueva -->
                </tbody>
            </table>
        </div>

        <!-- Checkout Area -->
        <div class="cobro-area">
            <div style="text-align: right; font-weight: 700; color: var(--text-muted); font-size: 1.2rem; text-transform: uppercase;">Total:</div>
            <div class="cobro-total" id="ticket-total-tactil">0.00</div>
            
            <div class="cobro-buttons">
                <button class="btn-unico" onclick="procesarCobro()"><i class="fa-solid fa-check"></i> COBRO ÚNICO</button>
                <button id="btn-vaciar-tactil" class="btn-separado" onclick="handleVaciarEliminarTactil()"><i class="fa-solid fa-trash"></i> VACIAR</button>
            </div>
        </div>
    </div>
</div>

<!-- Scale Modal -->
<div id="scaleModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 2rem; width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-main); margin: 0;">
                <i class="fa-solid fa-weight-scale" style="color: var(--primary);"></i> Báscula Digital
            </h3>
            <button onclick="closeScaleModal()" style="background: none; border: none; font-size: 1.25rem; color: var(--text-muted); cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div id="scaleArticleName" style="font-weight: 600; font-size: 1.1rem; color: var(--text-main);"></div>
            <div id="scaleArticlePrice" style="color: var(--text-muted);"></div>
            <div id="scaleArticleStock" style="color: #10b981; font-size: 0.9rem; font-weight: 600; margin-top: 0.5rem;"></div>
        </div>

        <div style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; padding: 1rem; text-align: center; margin-bottom: 1.5rem;">
            <input type="number" id="scaleInput" step="0.001" min="0" oninput="updateScaleTotal()" style="width: 100%; text-align: center; font-size: 2.5rem; font-weight: 800; color: #0f172a; background: transparent; border: none; outline: none; font-family: monospace;" placeholder="0.000">
            <div style="color: var(--text-muted); font-weight: 600; margin-top: 0.5rem; text-transform: uppercase;">{{ $settings['unidad_peso'] ?? 'kg' }}</div>
            <div id="scaleTotal" style="margin-top: 1rem; font-size: 1.5rem; font-weight: 700; color: var(--primary);">Total: $0.00</div>
        </div>

        <div style="display: flex; gap: 1rem;">
            <button onclick="closeScaleModal()" class="btn-modern btn-secondary" style="flex: 1; justify-content: center;">Cancelar</button>
            <button onclick="confirmScaleAdd()" class="btn-modern btn-accent" style="flex: 1; justify-content: center;">Añadir</button>
        </div>
    </div>
</div>

@include('vender._checkout_modals')
@endsection

@push('scripts')
<script>
    // Pasar los artículos de Laravel a JavaScript
    windowArticulos = {!! json_encode($articulos) !!};
    windowSettings = {!! json_encode($settings) !!};
</script>
<script src="{{ asset('js/pos.js') }}?v={{ time() }}"></script>
@endpush
