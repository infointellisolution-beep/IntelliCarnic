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

        // Clientes Demo
        \App\Models\Cliente::firstOrCreate([
            'nombre' => 'Carnicería El Ganadero S.A.',
        ], [
            'identificacion' => '0801199512345',
            'telefono' => '9988-1122',
            'email' => 'ganadero@carnes.com',
            'direccion' => 'Av. Los Próceres #102, Tegucigalpa',
            'limite_credito' => 1500.00,
            'saldo_deudor' => 0.00,
            'estado' => 'activo',
            'notas' => 'Cliente mayorista con crédito habilitado.'
        ]);

        \App\Models\Cliente::firstOrCreate([
            'nombre' => 'Restaurante Asados El Corral',
        ], [
            'identificacion' => '0801199088776',
            'telefono' => '9544-3322',
            'email' => 'compras@elcorral.com',
            'direccion' => 'Col. Palmira, Calle Principal #45',
            'limite_credito' => 800.00,
            'saldo_deudor' => 0.00,
            'estado' => 'activo',
            'notas' => 'Compras semanales a crédito.'
        ]);

        \App\Models\Cliente::firstOrCreate([
            'nombre' => 'María Fernanda Gómez',
        ], [
            'identificacion' => '0801198845612',
            'telefono' => '3322-1100',
            'email' => 'mfgomez@gmail.com',
            'direccion' => 'Residencial El Trapiche',
            'limite_credito' => 200.00,
            'saldo_deudor' => 0.00,
            'estado' => 'activo',
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
