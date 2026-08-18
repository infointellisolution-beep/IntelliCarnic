<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $guarded = [];

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cajaSesion()
    {
        return $this->belongsTo(CajaSesion::class, 'caja_sesion_id');
    }

    public function devoluciones()
    {
        return $this->hasMany(Devolucion::class);
    }
}
