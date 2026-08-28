<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manifiesto de Transferencia - {{ $transferencia->folio }}</title>
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
        table { width: 100%; border-collapse: collapse; }
        table th, table td { text-align: left; padding: 2px 0; font-size: 11px; }
        table th { border-bottom: 1px dashed #000; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print text-center">
        <button onclick="window.print()" class="btn-print">🖨️ Imprimir Manifiesto</button>
        <button onclick="window.close()" class="btn-print" style="background:#666;">✖ Cerrar</button>
    </div>

    @php
        $nombreEmpresa = $settings['empresa_nombre'] ?? 'INTELLICARNIC';
        $unidadPeso = strtoupper($settings['unidad_peso'] ?? 'LB');
    @endphp

    <div class="text-center bold" style="font-size: 14px;">{{ strtoupper($nombreEmpresa) }}</div>
    <div class="text-center bold">MANIFIESTO DE TRANSFERENCIA</div>
    <div class="text-center bold" style="font-size: 13px;">{{ $transferencia->folio }}</div>

    <div class="divider"></div>

    <div class="flex"><span>Origen:</span> <span class="bold">{{ $transferencia->sucursalOrigen->nombre ?? 'N/A' }}</span></div>
    <div class="flex"><span>Destino:</span> <span class="bold">{{ $transferencia->sucursalDestino->nombre ?? 'N/A' }}</span></div>
    <div class="flex"><span>Enviado por:</span> <span>{{ $transferencia->usuario->name ?? 'N/A' }}</span></div>
    <div class="flex"><span>Fecha:</span> <span>{{ $transferencia->fecha_envio ? $transferencia->fecha_envio->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</span></div>
    <div class="flex"><span>Estado:</span> <span class="bold">{{ strtoupper($transferencia->estado) }}</span></div>

    <div class="divider"></div>

    <div class="bold text-center">DETALLE DE PRODUCTOS</div>
    <div style="margin-top: 4px;"></div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th style="text-align:right;">Cant.</th>
                <th style="text-align:right;">Costo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transferencia->detalles as $detalle)
            <tr>
                <td>{{ \Illuminate\Support\Str::limit($detalle->descripcion, 22) }}</td>
                <td style="text-align:right;">{{ number_format($detalle->cantidad_enviada, $detalle->tipo_articulo === 'unidad' ? 0 : 3) }} {{ $detalle->unidad_medida }}</td>
                <td style="text-align:right;">${{ number_format($detalle->subtotal_costo, 2) }}</td>
            </tr>
            @if($detalle->lote)
            <tr>
                <td colspan="3" style="font-size:10px; color:#555;">  Lote: {{ $detalle->lote }} @if($detalle->fecha_vencimiento_lote) | Venc: {{ $detalle->fecha_vencimiento_lote->format('d/m/Y') }} @endif</td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="flex"><span>Total Peso:</span> <span class="bold">{{ number_format($transferencia->total_peso, 3) }} {{ $unidadPeso }}</span></div>
    <div class="flex"><span>Total Unidades:</span> <span class="bold">{{ $transferencia->total_unidades }} UND</span></div>
    <div class="flex bold" style="font-size: 13px;"><span>COSTO TOTAL:</span> <span>${{ number_format($transferencia->costo_total, 2) }}</span></div>

    @if($transferencia->notas)
    <div class="divider"></div>
    <div class="bold">NOTAS:</div>
    <div>{{ $transferencia->notas }}</div>
    @endif

    <div class="divider"></div>

    {{-- QR Code con datos de la transferencia --}}
    <div class="text-center" style="margin: 8px 0;">
        <img id="qrCode" alt="QR" style="width: 120px; height: 120px;">
    </div>
    <div class="text-center" style="font-size: 10px;">Escanear para verificar</div>

    <div class="divider"></div>

    <div class="text-center" style="margin-top: 20px;">_______________________</div>
    <div class="text-center">FIRMA REMITENTE</div>
    <br>
    <div class="text-center">_______________________</div>
    <div class="text-center">FIRMA RECEPTOR</div>
    <br>

    <script>
        // Generar QR con la información de la transferencia
        document.addEventListener('DOMContentLoaded', function() {
            const qrData = @json($transferencia->folio . '|' . ($transferencia->sucursalOrigen->codigo ?? '') . '|' . ($transferencia->sucursalDestino->codigo ?? '') . '|' . $transferencia->costo_total);
            const qrImg = document.getElementById('qrCode');
            qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' + encodeURIComponent(qrData);
        });
    </script>
</body>
</html>
