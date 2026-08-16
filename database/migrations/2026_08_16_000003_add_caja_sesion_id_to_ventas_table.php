<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ventas') && !Schema::hasColumn('ventas', 'caja_sesion_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->foreignId('caja_sesion_id')->nullable()->after('user_id')->constrained('caja_sesiones')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ventas') && Schema::hasColumn('ventas', 'caja_sesion_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropForeign(['caja_sesion_id']);
                $table->dropColumn('caja_sesion_id');
            });
        }
    }
};
