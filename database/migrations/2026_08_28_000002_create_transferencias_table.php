<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->unique();                // TRN-20260828-0001
            $table->foreignId('sucursal_origen_id')->constrained('sucursales');
            $table->foreignId('sucursal_destino_id')->constrained('sucursales');
            $table->foreignId('user_id')->nullable()->constrained('users');          // Quien envía
            $table->foreignId('user_recibe_id')->nullable()->constrained('users');   // Quien recibe
            $table->enum('estado', ['en_transito', 'recibida', 'cancelada'])->default('en_transito');
            $table->enum('tipo_sincronizacion', ['cloud', 'manual_trn'])->default('cloud');
            $table->decimal('total_peso', 12, 3)->default(0);
            $table->unsignedInteger('total_unidades')->default(0);
            $table->decimal('costo_total', 12, 2)->default(0);
            $table->text('notas')->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->timestamp('fecha_recepcion')->nullable();
            $table->longText('payload_json')->nullable();         // JSON completo para sync
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};
