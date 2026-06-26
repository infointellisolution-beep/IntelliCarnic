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
    Route::resource('articulos', ArticuloController::class)->except(['show']);
    Route::post('/articulos/stock-ajuste', [ArticuloController::class, 'adjustStock'])->name('articulos.stock-adjust');
    Route::post('/familias', [FamiliaController::class, 'store'])->name('familias.store');

    Route::get('/configuracion', [ConfiguracionController::class, 'index'])->name('configuracion.index');
    Route::post('/configuracion/general', [ConfiguracionController::class, 'updateGeneral'])->name('configuracion.general.update');
    Route::post('/configuracion/usuarios', [ConfiguracionController::class, 'storeUser'])->name('configuracion.users.store');
    Route::put('/configuracion/usuarios/{user}', [ConfiguracionController::class, 'updateUser'])->name('configuracion.users.update');
    Route::delete('/configuracion/usuarios/{user}', [ConfiguracionController::class, 'destroyUser'])->name('configuracion.users.destroy');

    Route::get('/vender', function () {
        return view('vender.index');
    })->name('vender.index');
});
