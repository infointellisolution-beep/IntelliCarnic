<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articulos_new', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100)->unique();
            $table->string('codigo_cliente', 50)->nullable();
            $table->foreignId('familia_id')->nullable()->constrained('familias');
            $table->boolean('aplica_iva')->default(true);
            $table->string('descripcion');
            $table->string('categoria', 120)->nullable();
            $table->decimal('precio_sin_iva', 10, 2);
            $table->decimal('iva', 5, 2)->default(21);
            $table->decimal('pvp', 10, 2)->nullable();
            $table->decimal('stock', 10, 3)->default(0);
            $table->decimal('stock_minimo', 10, 3)->default(0);
            $table->string('imagen')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
        });

        DB::statement('INSERT INTO articulos_new (id, codigo, codigo_cliente, familia_id, aplica_iva, descripcion, categoria, precio_sin_iva, iva, pvp, stock, stock_minimo, imagen, estado, created_at, updated_at) SELECT id, codigo, codigo_cliente, familia_id, aplica_iva, descripcion, categoria, precio_sin_iva, iva, pvp, stock, stock_minimo, imagen, estado, created_at, updated_at FROM articulos');

        Schema::dropIfExists('articulos');
        Schema::rename('articulos_new', 'articulos');
    }

    public function down(): void
    {
        Schema::create('articulos_new', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('codigo_cliente', 50)->nullable();
            $table->foreignId('familia_id')->nullable()->constrained('familias');
            $table->boolean('aplica_iva')->default(true);
            $table->string('descripcion');
            $table->string('categoria', 120)->nullable();
            $table->decimal('precio_sin_iva', 10, 2);
            $table->decimal('iva', 5, 2)->default(21);
            $table->decimal('pvp', 10, 2)->nullable();
            $table->decimal('stock', 10, 3)->default(0);
            $table->decimal('stock_minimo', 10, 3)->default(0);
            $table->string('imagen')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
        });

        DB::statement('INSERT INTO articulos_new (id, codigo, codigo_cliente, familia_id, aplica_iva, descripcion, categoria, precio_sin_iva, iva, pvp, stock, stock_minimo, imagen, estado, created_at, updated_at) SELECT id, codigo, codigo_cliente, familia_id, aplica_iva, descripcion, categoria, precio_sin_iva, iva, pvp, stock, stock_minimo, imagen, estado, created_at, updated_at FROM articulos');

        Schema::dropIfExists('articulos');
        Schema::rename('articulos_new', 'articulos');
    }
};
