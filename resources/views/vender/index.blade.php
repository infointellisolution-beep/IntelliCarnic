@extends('layouts.app')

@section('title', 'Punto de Venta / Facturación')

@section('content')
<div class="pos-layout">
    <!-- Left Panel: Client and Document Details -->
    <div class="pos-panel">
        <div class="pos-header">
            Datos de la Factura
        </div>
        <div class="pos-body">
            <div class="input-group">
                <label>Cliente</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" class="input-modern" placeholder="Buscar cliente..." value="Cliente Público en General">
                    <button class="btn-modern" style="width: auto; padding: 0.75rem;"><i class="fa-solid fa-search"></i></button>
                </div>
            </div>
            
            <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="input-group">
                    <label>Serie/Doc.</label>
                    <select class="input-modern">
                        <option>FAC-2026</option>
                        <option>TICKET-26</option>
                    </select>
                </div>
                <div class="input-group">
                    <label>Fecha</label>
                    <input type="date" class="input-modern" value="2026-06-16">
                </div>
            </div>

            <div class="input-group">
                <label>Forma de Pago</label>
                <select class="input-modern">
                    <option>Efectivo</option>
                    <option>Tarjeta de Crédito</option>
                    <option>Transferencia</option>
                </select>
            </div>

            <div class="input-group">
                <label>Estado</label>
                <select class="input-modern" style="color: var(--success); font-weight: bold;">
                    <option>Pagado</option>
                    <option>Pendiente</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Right Panel: Invoice Lines and Totals -->
    <div class="pos-panel">
        <div class="pos-header flex-between">
            <span>Líneas de Artículo</span>
            <div style="display: flex; gap: 0.5rem;">
                <input type="text" class="input-modern" style="padding: 0.4rem 0.75rem; font-size: 0.85rem; width: 250px;" placeholder="Escanea código de barras o busca...">
                <button class="btn-modern" style="width: auto; padding: 0.4rem 0.75rem;"><i class="fa-solid fa-plus"></i> Añadir</button>
            </div>
        </div>
        <div class="pos-body" style="padding: 0;">
            <table class="modern-table" style="margin-bottom: 0;">
                <thead style="background: white; position: sticky; top: 0;">
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>IVA</th>
                        <th>Cant.</th>
                        <th>Precio</th>
                        <th>Importe</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight: 500;">ART-001</td>
                        <td>Monitor Dell 27" 4K</td>
                        <td>21%</td>
                        <td>
                            <input type="number" class="input-modern" style="width: 70px; padding: 0.4rem;" value="1">
                        </td>
                        <td>$350.00</td>
                        <td style="font-weight: 600;">$423.50</td>
                        <td><button style="border: none; background: transparent; color: var(--danger); cursor: pointer;"><i class="fa-solid fa-trash"></i></button></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 500;">ART-002</td>
                        <td>Teclado Mecánico Keychron</td>
                        <td>21%</td>
                        <td>
                            <input type="number" class="input-modern" style="width: 70px; padding: 0.4rem;" value="2">
                        </td>
                        <td>$85.00</td>
                        <td style="font-weight: 600;">$205.70</td>
                        <td><button style="border: none; background: transparent; color: var(--danger); cursor: pointer;"><i class="fa-solid fa-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Totals Area -->
        <div class="pos-footer flex-between" style="align-items: flex-end;">
            <div style="display: flex; gap: 2rem;">
                <div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Base Imponible</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">$520.00</div>
                </div>
                <div>
                    <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Total IVA</div>
                    <div style="font-size: 1.25rem; font-weight: 700;">$109.20</div>
                </div>
            </div>

            <div style="text-align: right; width: 300px;">
                <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total a Pagar</div>
                <div class="total-display">$629.20</div>
                <button class="btn-modern btn-huge" style="background-color: var(--success);"><i class="fa-solid fa-cash-register"></i> COBRAR VENTA</button>
            </div>
        </div>
    </div>
</div>
@endsection
