<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticuloController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\FamiliaController;
use Illuminate\Support\Facades\Route;

Route::any('/', [AuthController::class, 'showLogin']);
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard.index');

    Route::resource('articulos', ArticuloController::class)->except(['show']);
    Route::post('/articulos/stock-ajuste', [ArticuloController::class, 'adjustStock'])->name('articulos.stock-adjust');
    Route::post('/familias', [FamiliaController::class, 'store'])->name('familias.store');

    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion/general', [ConfiguracionController::class, 'updateGeneral'])->name('configuracion.general.update');
    Route::post('/configuracion/empresa', [ConfiguracionController::class, 'updateEmpresa'])->name('configuracion.empresa.update');
    Route::post('/configuracion/usuarios', [ConfiguracionController::class, 'storeUser'])->name('configuracion.users.store');
    Route::put('/configuracion/usuarios/{user}', [ConfiguracionController::class, 'updateUser'])->name('configuracion.users.update');
    Route::delete('/configuracion/usuarios/{user}', [ConfiguracionController::class, 'destroyUser'])->name('configuracion.users.destroy');
    
    // Sistema
    Route::get('/configuracion/sistema/descargar-actualizador', [ConfiguracionController::class, 'downloadUpdater'])->name('configuracion.sistema.downloadUpdater');
    Route::post('/configuracion/sistema/github', [ConfiguracionController::class, 'updateGithub'])->name('configuracion.sistema.github');
    Route::post('/configuracion/sistema/actualizar', [ConfiguracionController::class, 'actualizarSistema'])->name('configuracion.sistema.actualizar');

    Route::get('/vender/normal', [\App\Http\Controllers\VenderController::class, 'normal'])->name('vender.normal');
    Route::get('/vender/tactil', [\App\Http\Controllers\VenderController::class, 'tactil'])->name('vender.tactil');
    Route::post('/vender/cobrar', [\App\Http\Controllers\VenderController::class, 'cobrar'])->name('vender.cobrar');
    Route::get('/vender/ticket/{id}', [\App\Http\Controllers\VenderController::class, 'getTicket'])->name('vender.ticket.get');
    Route::post('/vender/devolucion', [\App\Http\Controllers\VenderController::class, 'procesarDevolucion'])->name('vender.devolucion.store');

    // Compras (Acceso directo a registro)
    Route::get('/compras', [CompraController::class, 'create'])->name('compras.index');
    Route::get('/compras/crear', [CompraController::class, 'create'])->name('compras.create');
    Route::post('/compras', [CompraController::class, 'store'])->name('compras.store');
    Route::get('/compras/historial', [CompraController::class, 'historial'])->name('compras.historial');
    Route::get('/compras/{compra}', [CompraController::class, 'show'])->name('compras.show');
    // Reportes
    Route::get('/reportes', [\App\Http\Controllers\ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/exportar/{tipo}', [\App\Http\Controllers\ReporteController::class, 'exportarCsv'])->name('reportes.exportar');

    // Control de Caja
    Route::get('/caja', [\App\Http\Controllers\CajaController::class, 'index'])->name('caja.index');
    Route::post('/caja/aperturar', [\App\Http\Controllers\CajaController::class, 'aperturar'])->name('caja.aperturar');
    Route::post('/caja/movimiento', [\App\Http\Controllers\CajaController::class, 'storeMovimiento'])->name('caja.movimiento.store');
    Route::post('/caja/cerrar', [\App\Http\Controllers\CajaController::class, 'cerrar'])->name('caja.cerrar');
    Route::get('/caja/ticket-cierre/{cajaSesion}', [\App\Http\Controllers\CajaController::class, 'ticketCierre'])->name('caja.ticketCierre');
});

// Ruta de mantenimiento para ejecutar comandos sin SSH
Route::get('/dev/run-cmd', function (\Illuminate\Http\Request $request) {
    // Por seguridad, requiere un token: /dev/run-cmd?token=mipassword123
    if ($request->token !== 'IntelliDeploy2026') {
        abort(403, 'Unauthorized access.');
    }

    $output = [];
    
    // Reparar storage manualmente: Copiar archivos físicos porque symlink está deshabilitado
    $publicStorage = public_path('storage');
    $target = storage_path('app/public');
    
    // Si era un symlink roto, eliminarlo
    if (is_link($publicStorage)) {
        @unlink($publicStorage);
    }
    
    // Asegurar que exista el directorio de destino
    if (!file_exists($publicStorage)) {
        mkdir($publicStorage, 0755, true);
    }
    
    // Copiar todo el contenido físicamente y arreglar permisos (Evitar error 403)
    try {
        \Illuminate\Support\Facades\File::copyDirectory($target, $publicStorage);
        
        // Función recursiva para forzar permisos 0755 a carpetas y 0644 a archivos
        $setPermissions = function($dir) use (&$setPermissions) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $path = $dir . '/' . $file;
                    if (is_dir($path)) {
                        @chmod($path, 0755);
                        $setPermissions($path);
                    } else {
                        @chmod($path, 0644);
                    }
                }
            }
        };
        
        @chmod($publicStorage, 0755);
        $setPermissions($publicStorage);
        
        $output[] = "Imágenes copiadas y permisos (0755/0644) aplicados con éxito para evitar Error 403.";
    } catch (\Exception $e) {
        $output[] = "Error al copiar imágenes: " . $e->getMessage();
    }
    
    // Migraciones
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $output[] = "Migrate: " . \Illuminate\Support\Facades\Artisan::output();

    // Limpiar cachés
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    $output[] = "Optimize Clear: " . \Illuminate\Support\Facades\Artisan::output();
    
    // Optimizar para producción
    \Illuminate\Support\Facades\Artisan::call('optimize');
    $output[] = "Optimize: " . \Illuminate\Support\Facades\Artisan::output();

    return response()->json([
        'message' => 'Comandos ejecutados exitosamente.',
        'output' => $output
    ]);
});
