<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('articulos', function (Blueprint $table) {
                $table->decimal('stock', 10, 3)->default(0)->change();
            });

            return;
        }

        if (Schema::hasTable('articulos_old')) {
            DB::statement('DROP TABLE articulos_old');

            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('ALTER TABLE articulos RENAME TO articulos_old');
        DB::statement('DROP INDEX IF EXISTS articulos_codigo_unique');

        Schema::create('articulos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('codigo_cliente', 50)->nullable();
            $table->foreignId('familia_id')->nullable()->constrained('familias')->nullOnDelete();
            $table->string('descripcion');
            $table->string('categoria', 120)->nullable();
            $table->decimal('precio_sin_iva', 10, 2);
            $table->decimal('iva', 5, 2)->default(21);
            $table->decimal('pvp', 10, 2)->nullable();
            $table->decimal('stock', 10, 3)->default(0);
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
        });

        DB::statement('INSERT INTO articulos (id, codigo, codigo_cliente, familia_id, descripcion, categoria, precio_sin_iva, iva, pvp, stock, estado, created_at, updated_at)
            SELECT id, codigo, codigo_cliente, familia_id, descripcion, categoria, precio_sin_iva, iva, pvp, CAST(stock AS REAL), estado, created_at, updated_at
            FROM articulos_old');

        DB::statement('DROP TABLE articulos_old');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('articulos', function (Blueprint $table) {
                $table->unsignedInteger('stock')->default(0)->change();
            });

            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('ALTER TABLE articulos RENAME TO articulos_old');
        DB::statement('DROP INDEX IF EXISTS articulos_codigo_unique');

        Schema::create('articulos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('codigo_cliente', 50)->nullable();
            $table->foreignId('familia_id')->nullable()->constrained('familias')->nullOnDelete();
            $table->string('descripcion');
            $table->string('categoria', 120)->nullable();
            $table->decimal('precio_sin_iva', 10, 2);
            $table->decimal('iva', 5, 2)->default(21);
            $table->decimal('pvp', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
        });

        DB::statement('INSERT INTO articulos (id, codigo, codigo_cliente, familia_id, descripcion, categoria, precio_sin_iva, iva, pvp, stock, estado, created_at, updated_at)
            SELECT id, codigo, codigo_cliente, familia_id, descripcion, categoria, precio_sin_iva, iva, pvp, CAST(stock AS INTEGER), estado, created_at, updated_at
            FROM articulos_old');

        DB::statement('DROP TABLE articulos_old');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};