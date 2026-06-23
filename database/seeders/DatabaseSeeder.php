<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        DB::table('articulos')->delete();

        Articulo::query()->insert([
            [
                'codigo' => 'ART-001',
                'descripcion' => 'Monitor Dell 27" 4K',
                'categoria' => 'Informática > Monitores',
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
                'descripcion' => 'Teclado Mecánico Keychron',
                'categoria' => 'Informática > Periféricos',
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
                'descripcion' => 'Ratón Logitech MX Master 3',
                'categoria' => 'Informática > Periféricos',
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
