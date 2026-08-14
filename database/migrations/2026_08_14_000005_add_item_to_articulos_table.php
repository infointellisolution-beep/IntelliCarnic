<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            if (!Schema::hasColumn('articulos', 'item')) {
                $table->string('item', 50)->nullable()->after('codigo_cliente');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articulos', function (Blueprint $table) {
            if (Schema::hasColumn('articulos', 'item')) {
                $table->dropColumn('item');
            }
        });
    }
};
