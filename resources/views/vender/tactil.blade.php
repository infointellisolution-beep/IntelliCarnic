@extends('layouts.app')

@section('title', 'TPV Táctil')

@section('content')
<!-- Header Toolbar -->
<div class="tactil-header" style="background: var(--surface); padding: 1rem; border-radius: var(--radius-lg); border: 1px solid var(--border-color); margin-bottom: 1.5rem;">
    <button class="btn-icon-top"><i class="fa-solid fa-receipt"></i> Ticket</button>
    <button class="btn-icon-top"><i class="fa-solid fa-utensils"></i> Cocina</button>
    <button class="btn-icon-top"><i class="fa-solid fa-beer-mug-empty"></i> Barra</button>
    <div style="border-left: 1px solid var(--border-color); margin: 0 1rem;"></div>
    <button class="btn-icon-top"><i class="fa-solid fa-print"></i> Reimprimir Último</button>
    <button class="btn-icon-top"><i class="fa-solid fa-list-check"></i> Artículos Servidos</button>
    <button class="btn-icon-top"><i class="fa-solid fa-user"></i> Elegir Cliente</button>
    <button class="btn-icon-top"><i class="fa-solid fa-cash-register"></i> Abrir Cajón</button>
    <button class="btn-icon-top"><i class="fa-solid fa-file-invoice"></i> Reabrir Ticket</button>
    <button class="btn-icon-top"><i class="fa-solid fa-motorcycle"></i> Pedido a Domicilio</button>
    <button class="btn-icon-top"><i class="fa-solid fa-users"></i> Usuarios</button>
</div>

<div class="tactil-layout">
    <!-- Left Panel: Touch Grid (Familias y Artículos) -->
    <div class="tactil-left">
        <div class="flex-between" style="margin-bottom: 1rem;">
            <h3 style="margin:0;">Catálogo</h3>
            <div class="input-group" style="margin:0; width: 250px;">
                <input type="text" class="input-modern" placeholder="Buscar artículo...">
            </div>
        </div>
        
        <div style="display: flex; gap: 1rem; flex: 1; overflow: hidden;">
            <!-- Columna de Familias -->
            <div style="display: flex; flex-direction: column; gap: 0.75rem; width: 130px; overflow-y: auto; padding-right: 0.5rem; border-right: 1px solid var(--border-color);">
                @foreach($familias as $familia)
                <button class="btn-tactil familia" style="min-height: 110px;">
                    <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    {{ $familia->nombre }}
                </button>
                @endforeach
            </div>

            <!-- Grilla de Artículos -->
            <div class="tactil-grid" style="flex: 1; padding-left: 0.5rem;">
                @foreach($articulos as $articulo)
                <button class="btn-tactil" style="min-height: 110px;">
                    <!-- Simulamos íconos según el nombre del artículo para la demo -->
                    @if(stripos($articulo->descripcion, 'carne') !== false)
                        <div style="font-size: 2rem; color: #f59e0b; margin-bottom: 0.5rem;"><i class="fa-solid fa-burger"></i></div>
                    @elseif(stripos($articulo->descripcion, 'pollo') !== false)
                        <div style="font-size: 2rem; color: #ef4444; margin-bottom: 0.5rem;"><i class="fa-solid fa-drumstick-bite"></i></div>
                    @else
                        <div style="font-size: 2rem; color: #94a3b8; margin-bottom: 0.5rem;"><i class="fa-solid fa-image"></i></div>
                    @endif
                    <div style="line-height: 1.2;">{{ $articulo->descripcion }}</div>
                    <div style="color: var(--text-muted); margin-top: 0.25rem; font-weight: 700;">${{ number_format($articulo->pvp, 2) }}</div>
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
                        <th style="width: 50px;">Can.</th>
                        <th>Artículo</th>
                        <th>%Dto.</th>
                        <th>P. c/IVA</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Fila de ejemplo vacía como en la captura nueva -->
                </tbody>
            </table>
        </div>

        <!-- Checkout Area -->
        <div class="cobro-area">
            <div style="text-align: right; font-weight: 700; color: var(--text-muted); font-size: 1.2rem; text-transform: uppercase;">Total:</div>
            <div class="cobro-total">0.00</div>
            
            <div class="cobro-buttons">
                <button class="btn-unico"><i class="fa-solid fa-check"></i> COBRO ÚNICO</button>
                <button class="btn-separado"><i class="fa-solid fa-columns"></i> POR SEPARADO</button>
            </div>
        </div>
    </div>
</div>
@endsection
