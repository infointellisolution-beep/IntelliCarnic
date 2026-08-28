<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();           // SUC-01, SUC-02
            $table->string('nombre', 150);                      // Sucursal Matriz
            $table->string('direccion', 500)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->boolean('es_sucursal_actual')->default(false); // Marca cuál es ESTA sucursal en este equipo
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};
