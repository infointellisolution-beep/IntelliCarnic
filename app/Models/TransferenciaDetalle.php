<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferenciaDetalle extends Model
{
    use HasFactory;

    protected $table = 'transferencia_detalles';

    protected $fillable = [
        'transferencia_id',
        'articulo_id',
        'codigo',
        'descripcion',
        'tipo_articulo',
        'cantidad_enviada',
        'cantidad_recibida',
        'unidad_medida',
        'costo_unitario',
        'subtotal_costo',
        'lote',
        'numero_lote',
        'fecha_vencimiento_lote',
        'compra_detalle_id',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_enviada' => 'decimal:3',
            'cantidad_recibida' => 'decimal:3',
            'costo_unitario' => 'decimal:2',
            'subtotal_costo' => 'decimal:2',
            'fecha_vencimiento_lote' => 'date',
        ];
    }

    public function transferencia()
    {
        return $this->belongsTo(Transferencia::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function compraDetalle()
    {
        return $this->belongsTo(CompraDetalle::class);
    }
}
