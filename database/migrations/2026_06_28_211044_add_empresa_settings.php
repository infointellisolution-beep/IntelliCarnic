<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::setValue('empresa_nombre', '');
        Setting::setValue('empresa_direccion', '');
        Setting::setValue('empresa_logo', '');
        Setting::setValue('empresa_correo', '');
        Setting::setValue('empresa_celular', '');
    }

    public function down(): void
    {
        Setting::query()->whereIn('key', [
            'empresa_nombre',
            'empresa_direccion',
            'empresa_logo',
            'empresa_correo',
            'empresa_celular',
        ])->delete();
    }
};
