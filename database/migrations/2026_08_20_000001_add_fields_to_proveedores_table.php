<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('contacto_nombre')->nullable()->after('nombre');
            $table->enum('estado', ['activo', 'inactivo'])->default('activo')->after('direccion');
            $table->text('notas')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn(['contacto_nombre', 'estado', 'notas']);
        });
    }
};
