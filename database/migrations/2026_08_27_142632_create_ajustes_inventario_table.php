<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ajustes_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('articulo_id')->constrained('articulos')->cascadeOnDelete();
            $table->foreignId('compra_detalle_id')->nullable()->constrained('compra_detalles')->nullOnDelete();
            $table->string('lote')->nullable();
            $table->string('serie')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo_ajuste', 20); // 'reemplazo', 'suma', 'resta'
            $table->decimal('stock_anterior', 12, 3)->default(0);
            $table->decimal('cantidad_ajustada', 12, 3)->default(0);
            $table->decimal('stock_nuevo', 12, 3)->default(0);
            $table->decimal('diferencia_stock', 12, 3)->default(0);
            $table->string('origen', 20)->default('web'); // 'handheld', 'web'
            $table->string('modo_inventario', 20)->default('dinamico'); // 'dinamico', 'simple'
            $table->string('motivo', 255)->nullable();
            $table->timestamps();

            $table->index(['articulo_id', 'created_at']);
            $table->index(['tipo_ajuste', 'created_at']);
            $table->index(['origen', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ajustes_inventario');
    }
};
