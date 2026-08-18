<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('estado')->default('completada')->after('total');
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->decimal('cantidad_devuelta', 10, 3)->default(0)->after('cantidad');
        });

        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('caja_sesion_id')->nullable()->constrained('caja_sesiones')->nullOnDelete();
            $table->decimal('total_reembolsado', 10, 2)->default(0);
            $table->string('metodo_reembolso')->default('efectivo'); // efectivo, tarjeta, transferencia, vale
            $table->string('motivo')->nullable();
            $table->timestamps();
        });

        Schema::create('devolucion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_id')->constrained('devoluciones')->cascadeOnDelete();
            $table->foreignId('venta_detalle_id')->constrained('venta_detalles')->cascadeOnDelete();
            $table->foreignId('articulo_id')->nullable()->constrained('articulos')->nullOnDelete();
            $table->decimal('cantidad', 10, 3)->default(1);
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->boolean('reingresar_stock')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devolucion_detalles');
        Schema::dropIfExists('devoluciones');

        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropColumn('cantidad_devuelta');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
