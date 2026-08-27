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
        'tipo_articulo',
        'precio_compra',
        'aplica_iva',
        'precio_sin_iva',
        'iva',
        'pvp',
        'precios_adicionales',
        'stock',
        'stock_minimo',
        'estado',
        'imagen',
    ];

    protected function casts(): array
    {
        return [
            'precio_compra' => 'decimal:2',
            'precio_sin_iva' => 'decimal:2',
            'iva' => 'decimal:2',
            'pvp' => 'decimal:2',
            'stock' => 'decimal:3',
            'stock_minimo' => 'decimal:3',
            'aplica_iva' => 'boolean',
            'precios_adicionales' => 'array',
        ];
    }

    public function isPesable(): bool
    {
        return ($this->tipo_articulo ?? 'pesable') !== 'unidad';
    }

    public function isUnidad(): bool
    {
        return ($this->tipo_articulo ?? 'pesable') === 'unidad';
    }

    public function getUnidadSimboloAttribute(): string
    {
        if ($this->isUnidad()) {
            return 'UND';
        }
        $settings = Setting::values();
        return strtoupper($settings['unidad_peso'] ?? 'LB');
    }

    public function getFormattedStockAttribute(): string
    {
        if ($this->isUnidad()) {
            return number_format((float) $this->stock, 0) . ' UND';
        }
        return number_format((float) $this->stock, 3) . ' ' . $this->unidad_simbolo;
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