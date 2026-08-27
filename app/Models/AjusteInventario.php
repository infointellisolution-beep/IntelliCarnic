<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AjusteInventario extends Model
{
    use HasFactory;

    protected $table = 'ajustes_inventario';

    protected $fillable = [
        'articulo_id',
        'compra_detalle_id',
        'lote',
        'serie',
        'user_id',
        'tipo_ajuste',
        'stock_anterior',
        'cantidad_ajustada',
        'stock_nuevo',
        'diferencia_stock',
        'origen',
        'modo_inventario',
        'motivo',
    ];

    protected $casts = [
        'stock_anterior' => 'decimal:3',
        'cantidad_ajustada' => 'decimal:3',
        'stock_nuevo' => 'decimal:3',
        'diferencia_stock' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulo::class);
    }

    public function compraDetalle(): BelongsTo
    {
        return $this->belongsTo(CompraDetalle::class, 'compra_detalle_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
