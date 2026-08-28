<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\TransferenciaSyncService;
use Illuminate\Support\Facades\Http;

$endpoint = 'https://intellicarnicsync.intellisolution.net';
$token = 'IntelliCarnic_Sync_2026_Key';

// 1. Enviar una transferencia nueva limpia
$folioTest = 'TRN-TEST-' . time();
$postData = [
    'folio' => $folioTest,
    'sucursal_origen' => 'SUC-01',
    'sucursal_destino' => 'SUC-02',
    'usuario_envio' => 'Admin Test',
    'payload' => [
        'items' => [
            ['descripcion' => 'Corte Premium', 'cantidad' => 10, 'costo' => 50]
        ]
    ]
];

echo "Enviando POST a {$endpoint}/?action=enviar...\n";
$resPost = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json'
])->post("{$endpoint}/?action=enviar", $postData);

echo "Respuesta POST status: " . $resPost->status() . "\n";
echo "Respuesta POST body:\n" . $resPost->body() . "\n\n";

// 2. Consultar pendientes para SUC-02
echo "Consultando GET a {$endpoint}/?action=pendientes&sucursal=SUC-02...\n";
$resGet = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json'
])->get("{$endpoint}/?action=pendientes", [
    'sucursal' => 'SUC-02'
]);

echo "Respuesta GET status: " . $resGet->status() . "\n";
echo "Respuesta GET body:\n" . $resGet->body() . "\n\n";
