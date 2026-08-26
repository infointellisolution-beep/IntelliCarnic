<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('caja:recalcular', function () {
    $sesiones = \App\Models\CajaSesion::all();
    foreach ($sesiones as $s) {
        $s->recargarTotales();
        $this->info("Sesion #{$s->id}: Entradas Manuales = \${$s->total_entradas} | Abonos = \${$s->total_abonos_credito} | Salidas = \${$s->total_salidas} | Esperado = \${$s->saldo_esperado}");
    }
})->purpose('Recalcular totales de todas las sesiones de caja');
