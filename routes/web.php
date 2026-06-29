<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticuloController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\FamiliaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

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

    Route::get('/vender/normal', [\App\Http\Controllers\VenderController::class, 'normal'])->name('vender.normal');
    Route::get('/vender/tactil', [\App\Http\Controllers\VenderController::class, 'tactil'])->name('vender.tactil');
    Route::post('/vender/cobrar', [\App\Http\Controllers\VenderController::class, 'cobrar'])->name('vender.cobrar');
});

// Ruta de mantenimiento para ejecutar comandos sin SSH
Route::get('/dev/run-cmd', function (\Illuminate\Http\Request $request) {
    // Por seguridad, requiere un token: /dev/run-cmd?token=mipassword123
    if ($request->token !== 'IntelliDeploy2026') {
        abort(403, 'Unauthorized access.');
    }

    $output = [];
    
    // Reparar storage link
    $publicStorage = public_path('storage');
    if (file_exists($publicStorage) || is_link($publicStorage)) {
        if (is_link($publicStorage)) {
            unlink($publicStorage);
            $output[] = "Unlinked existing storage link.";
        } else if (is_dir($publicStorage)) {
            \Illuminate\Support\Facades\File::deleteDirectory($publicStorage);
            $output[] = "Deleted existing storage directory.";
        } else {
            unlink($publicStorage);
            $output[] = "Deleted existing storage file.";
        }
    }
    \Illuminate\Support\Facades\Artisan::call('storage:link');
    $output[] = "Storage Link: " . \Illuminate\Support\Facades\Artisan::output();
    
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
