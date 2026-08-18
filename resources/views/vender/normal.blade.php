@extends('layouts.app')

@section('title', 'Venta Normal')



@section('content')
<div class="venta-normal-layout" style="height: calc(100vh - 64px - 4rem);">
    
    <!-- Top Panel: Búsqueda y Catálogo -->
    <div class="card vn-top-panel" style="flex: 0 0 35%; display: flex; flex-direction: column; padding: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 0.5rem;">
            <label style="margin:0; font-weight: 700; white-space: nowrap;">Buscar / Escanear:</label>
            <div class="input-group" style="margin:0; flex:1;">
                <input type="text" id="search-articulo" class="input-modern" placeholder="Escriba texto o escanee un código de barras inteligente..." style="background: #fff7ed; border-color: #fdba74;">
            </div>
            <button class="btn-modern btn-secondary" style="width: auto; padding: 0.65rem 1rem;" onclick="renderSearchResults(document.getElementById('search-articulo').value)"><i class="fa-solid fa-barcode"></i></button>
            <button class="btn-modern btn-secondary" style="width: auto; padding: 0.65rem 1rem;" onclick="renderSearchResults(document.getElementById('search-articulo').value)"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
        <div style="font-size: 0.75rem; color: #10b981; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-microchip"></i> Analizador Inteligente de Pesos Activo (GS1-128 & Báscula)
        </div>
        
        <div style="flex: 1; overflow-y: auto; border: 1px solid var(--border-color); border-radius: var(--radius-md);">
            <table class="modern-table" style="margin: 0; width: 100%; text-align: left; border-collapse: collapse;">
                <thead style="background: #f8fafc; position: sticky; top: 0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <tr>
                        <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">Código</th>
                        <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">Nombre</th>
                        <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); text-align: right;">Precio + IVA</th>
                        <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); text-align: center; width: 100px;">Cantidad</th>
                    </tr>
                </thead>
                <tbody id="search-results-body">
                    <!-- La tabla de resultados de búsqueda se llena vía JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bottom Panel: Factura y Botones -->
    <div class="vn-bottom-panel" style="flex: 1; display: flex; gap: 1rem; min-height: 0;">
        
        <!-- Ticket Area -->
        <div class="card vn-invoice-area" style="flex: 1; display: flex; flex-direction: column; padding: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; border-bottom: 1px solid var(--border-color); background: #f8fafc;">
                <div style="font-weight: 700;">Cliente: <span style="font-weight: 800; color: var(--text-main);">Cliente Contado</span></div>
                <div style="font-weight: 700; color: var(--text-main);">Sin Serie <i class="fa-solid fa-file-invoice" style="color: var(--text-muted); margin-left: 0.5rem;"></i></div>
            </div>
            
            <div style="flex: 1; overflow-y: auto;">
                <table class="modern-table" style="margin: 0; width: 100%; text-align: left; border-collapse: collapse;">
                    <thead style="background: #f8fafc; position: sticky; top: 0; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                        <tr>
                            <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">Cód.</th>
                            <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color);">Descripción</th>
                            <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); text-align: center;">Cantidad</th>
                            <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); text-align: right;">P + IVA</th>
                            <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); text-align: right;">%Dto</th>
                            <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); text-align: right;">IVA</th>
                            <th style="padding: 0.75rem; border-bottom: 1px solid var(--border-color); text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="ticket-body-normal">
                        <!-- Fila vacía, a la espera de que se añadan artículos dinámicamente -->
                    </tbody>
                </table>
            </div>

            <!-- Totales Footer -->
            <div style="display: flex; justify-content: space-between; align-items: flex-end; padding: 1rem; border-top: 1px solid var(--border-color); background: #f8fafc;">
                <div>
                    <button class="btn-modern btn-secondary" style="width: auto; padding: 0.5rem 1rem; font-size: 0.85rem;"><i class="fa-solid fa-pause"></i> APARCAR VENTA</button>
                </div>
                <div style="text-align: right;">
                    <div id="ticket-stats-normal" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">0 líneas. 0 artículos. 0 uds.</div>
                    <div style="display: flex; align-items: baseline; justify-content: flex-end; gap: 1rem;">
                        <span style="font-size: 2rem; font-family: serif; font-weight: 700;">TOTAL:</span>
                        <span id="ticket-total-normal" style="font-size: 2.5rem; font-weight: 800;">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botonera Lateral Derecha -->
        <div style="width: 280px; display: flex; flex-direction: column; gap: 0.75rem;">
            <!-- Top Right Añadir button -->
            <button class="btn-action-huge" onclick="addSelectedToCart()" style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #166534; border: 1px solid #86efac; box-shadow: var(--shadow-sm); padding: 1.5rem 1rem;">
                <i class="fa-solid fa-circle-plus" style="color: #22c55e;"></i> AÑADIR A LA VENTA
            </button>

            <div style="flex: 1;"></div> <!-- Spacer -->

            <!-- Bottom Right Action Buttons -->
            <button class="btn-action-huge" onclick="clearCart()" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #991b1b; border: 1px solid #fca5a5; padding: 1rem; font-size: 0.95rem;">
                <i class="fa-solid fa-circle-xmark" style="color: #ef4444;"></i> CANCELAR Y VACIAR VENTA
            </button>
            
            <button class="btn-action-huge" style="background: white; color: var(--text-main); border: 1px solid var(--border-color); padding: 1rem; font-size: 0.95rem;">
                <i class="fa-solid fa-pen" style="color: var(--text-muted);"></i> EDITAR LÍNEA <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem; margin-left: 0.5rem;"></i>
            </button>
            
            <button class="btn-action-huge" onclick="removeFromCart()" style="background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%); color: #9a3412; border: 1px solid #fdba74; padding: 1rem; font-size: 0.95rem;">
                <i class="fa-solid fa-circle-minus" style="color: #f97316;"></i> QUITAR ARTÍCULO SELECCIONADO
            </button>

            <button class="btn-action-huge" onclick="procesarCobro()" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 1.5rem 1rem; font-size: 1.1rem; margin-top: 0.5rem;">
                <i class="fa-solid fa-check-double"></i> REALIZAR VENTA
            </button>
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
