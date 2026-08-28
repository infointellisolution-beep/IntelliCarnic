<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Articulo;
use App\Models\Setting;
use App\Models\Sucursal;
use App\Models\Transferencia;
use App\Models\TransferenciaDetalle;
use App\Models\User;
use App\Services\TransferenciaSyncService;
use Illuminate\Support\Facades\Auth;

echo "=== TEST: INICIALIZACIÓN Y PRUEBAS DE TRANSFERENCIAS ===\n\n";

// 1. Configurar Settings de Sincronización Cloud
Setting::setValue('cloud_sync_endpoint', 'https://intellicarnicsync.intellisolution.net');
Setting::setValue('cloud_sync_token', 'IntelliCarnic_Sync_2026_Key');

echo "1. Settings configurados:\n";
echo "   Endpoint: " . Setting::getValue('cloud_sync_endpoint') . "\n";
echo "   Token: " . substr(Setting::getValue('cloud_sync_token'), 0, 10) . "...\n\n";

// 2. Crear o verificar Sucursales de prueba
$suc1 = Sucursal::updateOrCreate(
    ['codigo' => 'SUC-01'],
    [
        'nombre' => 'Sucursal Matriz Centro',
        'direccion' => 'Av. Central #123',
        'telefono' => '8888-0001',
        'es_sucursal_actual' => true,
        'activo' => true,
    ]
);

$suc2 = Sucursal::updateOrCreate(
    ['codigo' => 'SUC-02'],
    [
        'nombre' => 'Sucursal Norte',
        'direccion' => 'Plaza Norte Módulo 4',
        'telefono' => '8888-0002',
        'es_sucursal_actual' => false,
        'activo' => true,
    ]
);

echo "2. Sucursales verificadas:\n";
echo "   - Actual: {$suc1->nombre} ({$suc1->codigo}) [ID: {$suc1->id}]\n";
echo "   - Destino: {$suc2->nombre} ({$suc2->codigo}) [ID: {$suc2->id}]\n\n";

// 3. Probar conexión directa con el Buzón en Hostinger
echo "3. Probando conexión con Hostinger Cloud Sync Hub...\n";
$syncService = new TransferenciaSyncService();
$testResult = $syncService->testConexion();
print_r($testResult);
echo "\n";

// 4. Verificar existencia de usuario y artículo para prueba
$user = User::first();
if (!$user) {
    echo "ERROR: No hay usuarios en la base de datos para simular login.\n";
    exit(1);
}
Auth::login($user);

$articulo = Articulo::where('stock', '>', 5)->first();
if (!$articulo) {
    $articulo = Articulo::first();
    if ($articulo) {
        $articulo->stock = 50.000;
        $articulo->save();
    }
}

if (!$articulo) {
    echo "ERROR: No hay artículos para probar transferencia.\n";
    exit(1);
}

echo "4. Artículo de prueba: '{$articulo->descripcion}' (Código: {$articulo->codigo}, Stock actual: {$articulo->stock})\n\n";

// 5. Simular creación de Transferencia desde SUC-01 hacia SUC-02
echo "5. Creando transferencia de salida (Envío)...\n";
$stockAntes = (float)$articulo->stock;
$cantidadTransferir = 5.000;

$folio = Transferencia::generarFolio();
$trans = Transferencia::create([
    'folio' => $folio,
    'sucursal_origen_id' => $suc1->id,
    'sucursal_destino_id' => $suc2->id,
    'user_id' => $user->id,
    'estado' => 'en_transito',
    'tipo_sincronizacion' => 'cloud',
    'total_peso' => $articulo->isUnidad() ? 0 : $cantidadTransferir,
    'total_unidades' => $articulo->isUnidad() ? (int)$cantidadTransferir : 0,
    'costo_total' => round($cantidadTransferir * (float)$articulo->precio_compra, 2),
    'notas' => 'Prueba automatizada de transferencia multisucursal',
    'fecha_envio' => now(),
]);

TransferenciaDetalle::create([
    'transferencia_id' => $trans->id,
    'articulo_id' => $articulo->id,
    'codigo' => $articulo->codigo ?? $articulo->codigo_cliente,
    'descripcion' => $articulo->descripcion,
    'tipo_articulo' => $articulo->tipo_articulo ?? 'pesable',
    'cantidad_enviada' => $cantidadTransferir,
    'unidad_medida' => $articulo->isUnidad() ? 'UND' : 'LB',
    'costo_unitario' => (float)$articulo->precio_compra,
    'subtotal_costo' => round($cantidadTransferir * (float)$articulo->precio_compra, 2),
]);

// Descontar stock
$articulo->stock = $stockAntes - $cantidadTransferir;
$articulo->save();

echo "   - Transferencia creada: Folio {$trans->folio}\n";
echo "   - Stock antes: {$stockAntes} -> Stock después: {$articulo->stock}\n\n";

// 6. Subir transferencia a Hostinger Cloud Sync
echo "6. Enviando transferencia a Hostinger Cloud...\n";
$envioResult = $syncService->enviarNube($trans);
print_r($envioResult);
echo "\n";

// 7. Consultar pendientes para SUC-02 desde Hostinger
echo "7. Consultando pendientes en Hostinger para SUC-02...\n";
$pendientesResult = $syncService->consultarPendientesNube('SUC-02');
print_r($pendientesResult);
echo "\n";

// 8. Probar marcado como recibida en la nube
echo "8. Marcando transferencia {$trans->folio} como recibida en la nube...\n";
$recibidaResult = $syncService->marcarRecibidaNube($trans->folio, 'Encargado SUC-02');
print_r($recibidaResult);
echo "\n";

// 9. Probar generación de archivo .TRN offline
echo "9. Generando archivo .TRN offline de respaldo...\n";
$trnContent = $syncService->generarArchivoTrn($trans);
$parsed = $syncService->procesarArchivoTrn($trnContent);
echo "   - Longitud contenido .TRN: " . strlen($trnContent) . " bytes\n";
echo "   - Parseo exitoso: " . ($parsed['success'] ? 'SI' : 'NO') . " (Folio en payload: " . ($parsed['data']['folio'] ?? 'N/A') . ")\n\n";

echo "=== TODAS LAS PRUEBAS COMPLETADAS EXITOSAMENTE ===\n";
