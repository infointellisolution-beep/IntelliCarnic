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

        $familiaBebidas = Familia::query()->create([
            'nombre' => 'BEBIDAS',
            'descripcion' => 'Bebidas frías y calientes.',
            'color' => '#2563eb'
        ]);

        $familiaCarnes = Familia::query()->create([
            'nombre' => 'CARNES',
            'descripcion' => 'Carnes rojas y blancas.',
            'color' => '#2563eb'
        ]);

        $familiaPostres = Familia::query()->create([
            'nombre' => 'POSTRES',
            'descripcion' => 'Postres y dulces.',
            'color' => '#2563eb'
        ]);

        Articulo::query()->insert([
            [
                'codigo' => '0001',
                'codigo_cliente' => 'CARNE-01',
                'familia_id' => $familiaCarnes->id,
                'aplica_iva' => 1,
                'descripcion' => 'Carne de res',
                'precio_sin_iva' => 123.96,
                'iva' => 21,
                'pvp' => 150.00,
                'stock' => 10,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => '0002',
                'codigo_cliente' => 'POLLO-01',
                'familia_id' => $familiaCarnes->id,
                'aplica_iva' => 1,
                'descripcion' => 'Pollo entero',
                'precio_sin_iva' => 70.24,
                'iva' => 21,
                'pvp' => 85.00,
                'stock' => 5,
                'estado' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
