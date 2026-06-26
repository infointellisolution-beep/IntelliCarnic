<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Familia extends Model
{
    use HasFactory;

    protected $table = 'familias';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function articulos()
    {
        return $this->hasMany(Articulo::class);
    }
}