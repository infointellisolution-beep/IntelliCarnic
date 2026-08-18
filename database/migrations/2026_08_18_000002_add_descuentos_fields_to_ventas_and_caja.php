<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('descuento', 10, 2)->default(0)->after('subtotal');
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->decimal('descuento', 10, 2)->default(0)->after('precio_unitario');
        });

        Schema::table('caja_sesiones', function (Blueprint $table) {
            $table->decimal('total_descuentos', 10, 2)->default(0)->after('total_salidas');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('descuento');
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropColumn('descuento');
        });

        Schema::table('caja_sesiones', function (Blueprint $table) {
            $table->dropColumn('total_descuentos');
        });
    }
};
