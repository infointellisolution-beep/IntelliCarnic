<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\Familia;
use App\Models\Setting;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Setting::setValue('unidad_peso', 'kg');
        Setting::setValue('iva_global_enabled', '1');
        Setting::setValue('iva_global_rate', '21');

        User::query()->updateOrCreate([
            'email' => 'admin@gmail.com',
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('admin123'),
        ]);

        User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
        ]);

        DB::table('familias')->delete();
        DB::table('articulos')->delete();

        $familiaMonitores = Familia::query()->create([
            'nombre' => 'Monitores',
            'descripcion' => 'Pantallas y monitores para informática.',
        ]);

        $familiaPerifericos = Familia::query()->create([
            'nombre' => 'Periféricos',
            'descripcion' => 'Teclados, ratones y accesorios.',
        ]);

        Articulo::query()->insert([
            [
                'codigo' => 'ART-001',
                'codigo_cliente' => 'MON-4K-27',
                'familia_id' => $familiaMonitores->id,
                'aplica_iva' => 1,
                'descripcion' => 'Monitor Dell 27" 4K',
                'precio_sin_iva' => 350.00,
                'iva' => 21,
                'pvp' => 423.50,
                'stock' => 24,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'ART-002',
                'codigo_cliente' => 'KEY-MECH-01',
                'familia_id' => $familiaPerifericos->id,
                'aplica_iva' => 1,
                'descripcion' => 'Teclado Mecánico Keychron',
                'precio_sin_iva' => 85.00,
                'iva' => 21,
                'pvp' => 102.85,
                'stock' => 5,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'ART-003',
                'codigo_cliente' => 'MOUSE-MX3',
                'familia_id' => $familiaPerifericos->id,
                'aplica_iva' => 1,
                'descripcion' => 'Ratón Logitech MX Master 3',
                'precio_sin_iva' => 90.00,
                'iva' => 21,
                'pvp' => 108.90,
                'stock' => 0,
                'estado' => 'sin_stock',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
