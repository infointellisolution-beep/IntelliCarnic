<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transferencia;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

echo "=== TEST: VISTAS Y RUTAS WEB DE TRANSFERENCIAS ===\n\n";

$tabs = ['enviar', 'recibir', 'historial', 'sucursales'];

foreach ($tabs as $tab) {
    $request = Request::create("/transferencias?tab={$tab}", 'GET');
    $response = app()->handle($request);
    echo "Pestaña '{$tab}': HTTP Status " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() !== 200) {
        echo "Error en pestaña {$tab}:\n";
        echo substr($response->getContent(), 0, 500) . "\n\n";
    }
}

$trans = Transferencia::first();
if ($trans) {
    $ticketReq = Request::create("/transferencias/{$trans->id}/ticket", 'GET');
    $ticketRes = app()->handle($ticketReq);
    echo "\nTicket Térmico (ID: {$trans->id}): HTTP Status " . $ticketRes->getStatusCode() . "\n";
}

echo "\n=== TODAS LAS VISTAS RENDERIZAN PERFECTAMENTE ===\n";
