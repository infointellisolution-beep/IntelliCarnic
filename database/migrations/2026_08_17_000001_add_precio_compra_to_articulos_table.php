<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->decimal('precio_compra', 10, 2)->default(0)->after('descripcion');
        });

        // Poblar precio_compra inicial con el valor de precio_sin_iva existente
        DB::statement('UPDATE articulos SET precio_compra = precio_sin_iva WHERE precio_compra = 0');
    }

    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropColumn('precio_compra');
        });
    }
};
