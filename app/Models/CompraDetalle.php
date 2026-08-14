<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'compra_id',
        'articulo_id',
        'codigo_escaneado',
        'lote',
        'serie',
        'fecha_vencimiento',
        'cantidad_peso',
        'costo_unitario',
        'subtotal',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'cantidad_peso' => 'decimal:3',
        'costo_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }
}
