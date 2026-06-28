<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public $timestamps = false;

    public static function defaults(): array
    {
        return [
            'unidad_peso' => 'kg',
            'iva_global_enabled' => '1',
            'iva_global_rate' => '21',
            'empresa_nombre' => '',
            'empresa_direccion' => '',
            'empresa_logo' => '',
            'empresa_correo' => '',
            'empresa_celular' => '',
        ];
    }

    public static function values(): array
    {
        $stored = static::query()->pluck('value', 'key')->all();

        return array_replace(static::defaults(), $stored);
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? static::defaults()[$key] ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate([
            'key' => $key,
        ], [
            'value' => (string) $value,
        ]);
    }
}