<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'identificacion',
        'telefono',
        'email',
        'direccion',
        'limite_credito',
        'saldo_deudor',
        'estado',
        'notas',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'saldo_deudor' => 'decimal:2',
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class);
    }

    /**
     * Recalcula y actualiza el saldo deudor del cliente
     */
    public function actualizarSaldoDeudor(): float
    {
        $totalCredito = (float) $this->ventas()
            ->where('tipo_venta', 'credito')
            ->where('estado', '!=', 'devuelta')
            ->sum('total');

        $totalAbonos = (float) $this->abonos()->sum('monto');

        $this->saldo_deudor = max(0, round($totalCredito - $totalAbonos, 2));
        $this->save();

        return $this->saldo_deudor;
    }
}
