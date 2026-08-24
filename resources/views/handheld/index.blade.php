@extends('layouts.handheld')

@section('title', 'Menú Principal Handheld')

@section('content')
<div style="margin-bottom: 1rem; text-align: center;">
    <h2 style="font-size: 1.3rem; font-weight: 800; color: white;">Operaciones Rápidas</h2>
    <p style="font-size: 0.85rem; color: var(--text-muted);">Selecciona la tarea a realizar en esta terminal</p>
</div>

<!-- TARJETA 1: TPV MÓVIL (VENTA EXPRESS) -->
<a href="{{ route('handheld.tpv') }}" class="touch-card">
    <div class="touch-card-icon" style="background: rgba(37, 99, 235, 0.2); color: #60a5fa;">
        <i class="fa-solid fa-cart-shopping"></i>
    </div>
    <div>
        <div class="touch-card-title">TPV Móvil</div>
        <div class="touch-card-desc">Venta express y cobro rápido de artículos en pasillo.</div>
    </div>
</a>

<!-- TARJETA 2: RECEPCIÓN DE COMPRAS (BODEGA) -->
<a href="{{ route('handheld.compras') }}" class="touch-card">
    <div class="touch-card-icon" style="background: rgba(217, 119, 6, 0.2); color: #fbbf24;">
        <i class="fa-solid fa-boxes-packing"></i>
    </div>
    <div>
        <div class="touch-card-title">Recepción Compras</div>
        <div class="touch-card-desc">Escaneo de cajas y recepción de proveedores en bodega.</div>
    </div>
</a>

<!-- TARJETA 3: CONTEO DE INVENTARIO -->
<a href="{{ route('handheld.conteo') }}" class="touch-card">
    <div class="touch-card-icon" style="background: rgba(16, 185, 129, 0.2); color: #34d399;">
        <i class="fa-solid fa-clipboard-check"></i>
    </div>
    <div>
        <div class="touch-card-title">Conteo / Ajuste</div>
        <div class="touch-card-desc">Escaneo rápido de existencias físicas en cámaras frías.</div>
    </div>
</a>

<div style="margin-top: 1.5rem; background: rgba(30, 41, 59, 0.5); border: 1px dashed var(--border-color); border-radius: 10px; padding: 0.85rem; text-align: center;">
    <div style="font-size: 0.8rem; color: var(--text-muted);">
        <i class="fa-solid fa-circle-info" style="color: #38bdf8;"></i> Mapeo de escáner activo (DataWedge). Listo para recibir lecturas láser de códigos GS1-128 y báscula.
    </div>
</div>
@endsection
