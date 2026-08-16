<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caja_sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('monto_inicial', 10, 2)->default(0);
            $table->decimal('total_ventas_efectivo', 10, 2)->default(0);
            $table->decimal('total_ventas_tarjeta', 10, 2)->default(0);
            $table->decimal('total_ventas_transferencia', 10, 2)->default(0);
            $table->decimal('total_entradas', 10, 2)->default(0);
            $table->decimal('total_salidas', 10, 2)->default(0);
            $table->decimal('saldo_esperado', 10, 2)->default(0);
            $table->decimal('saldo_real', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->default(0);
            $table->timestamp('fecha_apertura')->useCurrent();
            $table->timestamp('fecha_cierre')->nullable();
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caja_sesiones');
    }
};
