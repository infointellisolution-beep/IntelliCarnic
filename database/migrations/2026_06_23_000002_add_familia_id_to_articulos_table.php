<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->foreignId('familia_id')
                ->nullable()
                ->after('descripcion')
                ->constrained('familias')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('familia_id');
        });
    }
};