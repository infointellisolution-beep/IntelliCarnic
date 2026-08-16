<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Cierre Z - Turno #{{ $cajaSesion->id }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: 80mm;
            color: #000;
            background: #fff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .flex { display: flex; justify-content: space-between; }
        .btn-print {
            background: #000;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 14px;
            cursor: pointer;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print text-center">
        <button onclick="window.print()" class="btn-print">🖨️ Imprimir Ticket de Corte</button>
    </div>

    @php
        $settings = \App\Models\Setting::values();
        $nombreEmpresa = $settings['empresa_nombre'] ?? 'INTELLICARNIC';
    @endphp

    <div class="text-center bold" style="font-size: 14px;">{{ strtoupper($nombreEmpresa) }}</div>
    <div class="text-center bold">CORTE DE CAJA (REPORT Z)</div>
    <div class="text-center">TURNO #{{ $cajaSesion->id }}</div>

    <div class="divider"></div>

    <div class="flex"><span>Cajero:</span> <span class="bold">{{ $cajaSesion->user->name ?? 'N/A' }}</span></div>
    <div class="flex"><span>Estado:</span> <span class="bold">{{ strtoupper($cajaSesion->estado) }}</span></div>
    <div class="flex"><span>Apertura:</span> <span>{{ $cajaSesion->fecha_apertura->format('d/m/Y H:i') }}</span></div>
    <div class="flex"><span>Cierre:</span> <span>{{ $cajaSesion->fecha_cierre ? $cajaSesion->fecha_cierre->format('d/m/Y H:i') : 'EN CURSO' }}</span></div>

    <div class="divider"></div>

    <div class="bold text-center">DESGLOSE DE SALDOS</div>
    
    <div class="flex"><span>Fondo Inicial:</span> <span>${{ number_format($cajaSesion->monto_inicial, 2) }}</span></div>
    <div class="flex"><span>(+) Ventas Efectivo:</span> <span>${{ number_format($cajaSesion->total_ventas_efectivo, 2) }}</span></div>
    <div class="flex"><span>(+) Entradas Manuales:</span> <span>${{ number_format($cajaSesion->total_entradas, 2) }}</span></div>
    <div class="flex"><span>(-) Salidas / Gastos:</span> <span>-${{ number_format($cajaSesion->total_salidas, 2) }}</span></div>
    
    <div class="divider"></div>
    
    <div class="flex bold" style="font-size: 13px;"><span>ESPERADO EN CAJA:</span> <span>${{ number_format($cajaSesion->saldo_esperado, 2) }}</span></div>
    
    @if($cajaSesion->estado === 'cerrada')
        <div class="flex bold"><span>CONTADO FISICO:</span> <span>${{ number_format($cajaSesion->saldo_real, 2) }}</span></div>
        
        <div class="divider"></div>

        <div class="flex bold" style="font-size: 13px;">
            <span>DIFERENCIA:</span> 
            <span>
                @if(abs($cajaSesion->diferencia) < 0.01)
                    $0.00 (CUADRADA)
                @elseif($cajaSesion->diferencia > 0)
                    +${{ number_format($cajaSesion->diferencia, 2) }} (SOBRANTE)
                @else
                    -${{ number_format(abs($cajaSesion->diferencia), 2) }} (FALTANTE)
                @endif
            </span>
        </div>
    @endif

    <div class="divider"></div>

    <div class="bold text-center">OTRAS FORMAS DE PAGO</div>
    <div class="flex"><span>Ventas Tarjeta:</span> <span>${{ number_format($cajaSesion->total_ventas_tarjeta, 2) }}</span></div>
    <div class="flex"><span>Ventas Transferencia:</span> <span>${{ number_format($cajaSesion->total_ventas_transferencia, 2) }}</span></div>
    <div class="flex bold"><span>TOTAL GENERAL VENTAS:</span> <span>${{ number_format($cajaSesion->total_ventas_efectivo + $cajaSesion->total_ventas_tarjeta + $cajaSesion->total_ventas_transferencia, 2) }}</span></div>

    @if($cajaSesion->observaciones)
        <div class="divider"></div>
        <div class="bold">OBSERVACIONES:</div>
        <div>{{ $cajaSesion->observaciones }}</div>
    @endif

    <div class="divider"></div>
    <br>
    <div class="text-center" style="margin-top: 25px;">_______________________</div>
    <div class="text-center">FIRMA DEL CAJERO</div>
    <br><br>

</body>
</html>
