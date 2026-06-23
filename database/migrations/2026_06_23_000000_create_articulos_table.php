<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion');
            $table->string('categoria', 120)->nullable();
            $table->decimal('precio_sin_iva', 10, 2);
            $table->decimal('iva', 5, 2)->default(21);
            $table->decimal('pvp', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulos');
    }
};