<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('caja_sesiones', function (Blueprint $table) {
            $table->decimal('total_ventas_credito', 10, 2)->default(0)->after('total_devoluciones');
            $table->decimal('total_abonos_credito', 10, 2)->default(0)->after('total_ventas_credito');
        });
    }

    public function down(): void
    {
        Schema::table('caja_sesiones', function (Blueprint $table) {
            $table->dropColumn(['total_ventas_credito', 'total_abonos_credito']);
        });
    }
};
