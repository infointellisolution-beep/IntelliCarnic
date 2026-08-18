<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('identificacion')->nullable(); // DNI, Cédula, RUC, NIT
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->decimal('limite_credito', 10, 2)->default(0);
            $table->decimal('saldo_deudor', 10, 2)->default(0);
            $table->string('estado')->default('activo'); // activo, inactivo
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('caja_sesion_id')->constrained('clientes')->nullOnDelete();
            $table->string('tipo_venta')->default('normal')->after('metodo_pago'); // normal, credito
        });

        Schema::create('abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('caja_sesion_id')->nullable()->constrained('caja_sesiones')->nullOnDelete();
            $table->decimal('monto', 10, 2)->default(0);
            $table->string('metodo_pago')->default('efectivo'); // efectivo, tarjeta, transferencia
            $table->decimal('saldo_anterior', 10, 2)->default(0);
            $table->decimal('saldo_nuevo', 10, 2)->default(0);
            $table->string('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonos');

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn(['cliente_id', 'tipo_venta']);
        });

        Schema::dropIfExists('clientes');
    }
};
