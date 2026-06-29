<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model
{
    protected $guarded = [];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}
