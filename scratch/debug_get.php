<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$endpoint = 'https://intellicarnicsync.intellisolution.net';
$token = 'IntelliCarnic_Sync_2026_Key';

// Consultar pendientes pasando toda la query en la URL
echo "Consultando GET con URL completa...\n";
$resGet = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
    'Accept' => 'application/json'
])->get("{$endpoint}/?action=pendientes&sucursal=SUC-02");

echo "Respuesta GET status: " . $resGet->status() . "\n";
echo "Respuesta GET body:\n" . $resGet->body() . "\n\n";
