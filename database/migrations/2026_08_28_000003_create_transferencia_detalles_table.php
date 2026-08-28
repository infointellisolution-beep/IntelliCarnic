<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transferencia_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transferencia_id')->constrained('transferencias')->cascadeOnDelete();
            $table->foreignId('articulo_id')->constrained('articulos');
            $table->string('codigo', 100)->nullable();
            $table->string('descripcion', 255);
            $table->enum('tipo_articulo', ['pesable', 'unidad'])->default('pesable');
            $table->decimal('cantidad_enviada', 12, 3)->default(0);
            $table->decimal('cantidad_recibida', 12, 3)->nullable();
            $table->string('unidad_medida', 10)->default('LB');     // LB, KG, UND
            $table->decimal('costo_unitario', 12, 2)->default(0);
            $table->decimal('subtotal_costo', 12, 2)->default(0);
            $table->string('lote', 100)->nullable();
            $table->string('numero_lote', 100)->nullable();
            $table->date('fecha_vencimiento_lote')->nullable();
            $table->foreignId('compra_detalle_id')->nullable()->constrained('compra_detalles'); // Referencia al lote origen
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transferencia_detalles');
    }
};
