<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaSesion extends Model
{
    use HasFactory;

    protected $table = 'caja_sesiones';

    protected $fillable = [
        'user_id',
        'monto_inicial',
        'total_ventas_efectivo',
        'total_ventas_tarjeta',
        'total_ventas_transferencia',
        'total_entradas',
        'total_salidas',
        'total_descuentos',
        'total_devoluciones',
        'saldo_esperado',
        'saldo_real',
        'diferencia',
        'fecha_apertura',
        'fecha_cierre',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'monto_inicial' => 'decimal:2',
        'total_ventas_efectivo' => 'decimal:2',
        'total_ventas_tarjeta' => 'decimal:2',
        'total_ventas_transferencia' => 'decimal:2',
        'total_entradas' => 'decimal:2',
        'total_salidas' => 'decimal:2',
        'total_descuentos' => 'decimal:2',
        'total_devoluciones' => 'decimal:2',
        'saldo_esperado' => 'decimal:2',
        'saldo_real' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(CajaMovimiento::class, 'caja_sesion_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'caja_sesion_id');
    }

    public function devoluciones(): HasMany
    {
        return $this->hasMany(Devolucion::class, 'caja_sesion_id');
    }

    /**
     * Calcula el saldo esperado en efectivo en tiempo real
     */
    public function calcularSaldoEsperado(): float
    {
        return (float) ($this->monto_inicial + $this->total_ventas_efectivo + $this->total_entradas - $this->total_salidas);
    }

    /**
     * Recalcula y actualiza los acumulados de la sesión activa
     */
    public function recargarTotales(): void
    {
        $efectivo = (float) $this->ventas()->where('metodo_pago', 'efectivo')->sum('total');
        $tarjeta = (float) $this->ventas()->where('metodo_pago', 'tarjeta')->sum('total');
        $transferencia = (float) $this->ventas()->where('metodo_pago', 'transferencia')->sum('total');

        $entradas = (float) $this->movimientos()->where('tipo', 'entrada')->sum('monto');
        $salidas = (float) $this->movimientos()->where('tipo', 'salida')->sum('monto');
        $descuentos = (float) $this->ventas()->sum('descuento');
        $devoluciones = (float) Devolucion::where('caja_sesion_id', $this->id)->sum('total_reembolsado');

        $this->total_ventas_efectivo = $efectivo;
        $this->total_ventas_tarjeta = $tarjeta;
        $this->total_ventas_transferencia = $transferencia;
        $this->total_entradas = $entradas;
        $this->total_salidas = $salidas;
        $this->total_descuentos = $descuentos;
        $this->total_devoluciones = $devoluciones;
        $this->saldo_esperado = $this->monto_inicial + $efectivo + $entradas - $salidas;
        $this->save();
    }
}
