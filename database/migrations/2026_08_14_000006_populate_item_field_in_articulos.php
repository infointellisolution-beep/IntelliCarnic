<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $articulos = DB::table('articulos')->get();
        foreach ($articulos as $art) {
            $code = preg_replace('/[()\-\s]/', '', $art->codigo);
            if (preg_match('/01(\d{14})/', $code, $m)) {
                $gtin = $m[1];
                $item = substr($gtin, -6, 5); // Extrae los 5 dígitos puros del ITEM
                DB::table('articulos')->where('id', $art->id)->update(['item' => $item]);
            } elseif (strlen($art->codigo_cliente) >= 6) {
                $item = substr($art->codigo_cliente, 0, 5);
                DB::table('articulos')->where('id', $art->id)->update(['item' => $item]);
            }
        }
    }

    public function down(): void
    {
    }
};
