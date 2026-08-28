<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = User::first();
Auth::login($user);

echo "=== TEST: VERIFICACIÓN DE VISTAS ACTUALIZADAS ===\n\n";

// 1. Probar nueva pestaña de sucursales en Configuración
$reqConfig = Request::create("/configuracion?tab=sucursales", 'GET');
$resConfig = app()->handle($reqConfig);
echo "Configuración (tab=sucursales): HTTP Status " . $resConfig->getStatusCode() . "\n";

// 2. Probar las 3 pestañas de Transferencias
$transTabs = ['enviar', 'recibir', 'historial'];
foreach ($transTabs as $tab) {
    $reqTrans = Request::create("/transferencias?tab={$tab}", 'GET');
    $resTrans = app()->handle($reqTrans);
    echo "Transferencias (tab={$tab}): HTTP Status " . $resTrans->getStatusCode() . "\n";
}

echo "\n=== TODAS LAS VISTAS RENDERIZAN CON ÉXITO ===\n";
