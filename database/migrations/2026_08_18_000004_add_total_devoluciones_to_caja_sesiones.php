<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_sesiones', function (Blueprint $table) {
            $table->decimal('total_devoluciones', 10, 2)->default(0)->after('total_descuentos');
        });
    }

    public function down(): void
    {
        Schema::table('caja_sesiones', function (Blueprint $table) {
            $table->dropColumn('total_devoluciones');
        });
    }
};
