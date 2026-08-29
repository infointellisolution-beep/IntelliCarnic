<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role', 30)->default('vendedor')->after('email');
            }
            if (!Schema::hasColumn('users', 'permissions')) {
                $table->text('permissions')->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'sucursal_id')) {
                $table->unsignedBigInteger('sucursal_id')->nullable()->after('permissions');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('sucursal_id');
            }
        });

        // Asegurar que el usuario admin principal tenga rol 'administrador'
        DB::table('users')->where('email', 'admin@gmail.com')->update([
            'role' => 'administrador',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions', 'sucursal_id', 'is_active']);
        });
    }
};
