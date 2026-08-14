<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Articulo extends Model
{
    use HasFactory;

    protected $table = 'articulos';

    protected $fillable = [
        'codigo',
        'codigo_cliente',
        'item',
        'familia_id',
        'descripcion',
        'aplica_iva',
        'precio_sin_iva',
        'iva',
        'pvp',
        'stock',
        'stock_minimo',
        'estado',
        'imagen',
    ];

    protected function casts(): array
    {
        return [
            'precio_sin_iva' => 'decimal:2',
            'iva' => 'decimal:2',
            'pvp' => 'decimal:2',
            'stock' => 'decimal:3',
            'stock_minimo' => 'decimal:3',
            'aplica_iva' => 'boolean',
        ];
    }

    public function familia()
    {
        return $this->belongsTo(Familia::class);
    }

    public function compraDetalles()
    {
        return $this->hasMany(CompraDetalle::class)->where('cantidad_peso', '>', 0)->orderBy('fecha_vencimiento', 'asc');
    }
}