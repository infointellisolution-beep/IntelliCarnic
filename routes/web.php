<?php

use App\Http\Controllers\ArticuloController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    // Mock login logic, just redirect to articulos
    return redirect()->route('articulos.index');
})->name('login.post');

Route::resource('articulos', ArticuloController::class)->except(['show']);
Route::post('/articulos/stock-ajuste', [ArticuloController::class, 'adjustStock'])->name('articulos.stock-adjust');

Route::get('/vender', function () {
    return view('vender.index');
})->name('vender.index');
