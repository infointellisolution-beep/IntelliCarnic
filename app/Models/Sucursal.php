<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $fillable = [
        'codigo',
        'nombre',
        'direccion',
        'telefono',
        'es_sucursal_actual',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_sucursal_actual' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * Obtener la sucursal marcada como "actual" en este equipo.
     */
    public static function actual(): ?self
    {
        return static::where('es_sucursal_actual', true)->first();
    }

    /**
     * Marcar esta sucursal como la actual (desmarcando las demás).
     */
    public function marcarComoActual(): void
    {
        static::query()->update(['es_sucursal_actual' => false]);
        $this->update(['es_sucursal_actual' => true]);
    }

    /**
     * Obtener todas las sucursales activas excepto la actual.
     */
    public static function destinos(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('activo', true)
            ->where('es_sucursal_actual', false)
            ->orderBy('nombre')
            ->get();
    }

    public function transferenciasEnviadas()
    {
        return $this->hasMany(Transferencia::class, 'sucursal_origen_id');
    }

    public function transferenciasRecibidas()
    {
        return $this->hasMany(Transferencia::class, 'sucursal_destino_id');
    }
}
