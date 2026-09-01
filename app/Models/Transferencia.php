<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transferencia extends Model
{
    use HasFactory;

    protected $table = 'transferencias';

    protected $fillable = [
        'folio',
        'sucursal_origen_id',
        'sucursal_destino_id',
        'user_id',
        'user_recibe_id',
        'estado',
        'tipo_sincronizacion',
        'total_peso',
        'total_unidades',
        'costo_total',
        'notas',
        'fecha_envio',
        'fecha_recepcion',
        'payload_json',
    ];

    protected function casts(): array
    {
        return [
            'total_peso' => 'decimal:3',
            'costo_total' => 'decimal:2',
            'total_unidades' => 'integer',
            'fecha_envio' => 'datetime',
            'fecha_recepcion' => 'datetime',
        ];
    }

    /**
     * Generar folio automático: TRN-YYYYMMDD-XXXX
     */
    public static function generarFolio(): string
    {
        $hoy = now()->format('Ymd');
        $prefijo = "TRN-{$hoy}-";

        $ultimo = static::where('folio', 'like', "{$prefijo}%")
            ->orderBy('folio', 'desc')
            ->value('folio');

        if ($ultimo) {
            $secuencia = (int) substr($ultimo, -4) + 1;
        } else {
            $secuencia = 1;
        }

        return $prefijo . str_pad($secuencia, 4, '0', STR_PAD_LEFT);
    }

    // ─── Relaciones ───

    public function sucursalOrigen()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_origen_id');
    }

    public function sucursalDestino()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_destino_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function usuarioRecibe()
    {
        return $this->belongsTo(User::class, 'user_recibe_id');
    }

    public function detalles()
    {
        return $this->hasMany(TransferenciaDetalle::class);
    }

    // ─── Helpers ───

    public function getEstadoBadgeAttribute(): string
    {
        return match ($this->estado) {
            'en_transito' => '<span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:12px;font-size:0.75rem;font-weight:600;">🚚 En Tránsito</span>',
            'recibida' => '<span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:12px;font-size:0.75rem;font-weight:600;">✅ Recibida</span>',
            'cancelada' => '<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:12px;font-size:0.75rem;font-weight:600;">❌ Cancelada</span>',
            default => $this->estado,
        };
    }

    /**
     * Construir el payload JSON completo para sincronización con la nube / archivo .TRN.
     */
    public function buildPayload(): array
    {
        $this->load(['detalles.compraDetalle', 'detalles.articulo.familia', 'sucursalOrigen', 'sucursalDestino', 'usuario']);

        return [
            'folio' => $this->folio,
            'sucursal_origen' => $this->sucursalOrigen->codigo,
            'sucursal_destino' => $this->sucursalDestino->codigo,
            'usuario_envio' => $this->usuario->name ?? 'Sistema',
            'fecha_envio' => $this->fecha_envio?->toIso8601String(),
            'notas' => $this->notas,
            'total_peso' => (float) $this->total_peso,
            'total_unidades' => (int) $this->total_unidades,
            'costo_total' => (float) $this->costo_total,
            'payload' => $this->detalles->map(function ($d) {
                $art = $d->articulo;
                return [
                    'articulo_id' => $d->articulo_id,
                    'codigo' => $d->codigo ?: ($art?->codigo ?? ''),
                    'codigo_cliente' => (string) ($art?->codigo_cliente ?? ''),
                    'item' => $art?->item ?? null,
                    'familia_nombre' => $art?->familia?->nombre ?? null,
                    'codigo_escaneado' => $d->compraDetalle?->codigo_escaneado ?: ($d->codigo ?: ''),
                    'descripcion' => $d->descripcion ?: ($art?->descripcion ?? ''),
                    'tipo_articulo' => $d->tipo_articulo ?: ($art?->tipo_articulo ?? 'pesable'),
                    'cantidad_enviada' => (float) $d->cantidad_enviada,
                    'unidad_medida' => $d->unidad_medida,
                    'costo_unitario' => (float) $d->costo_unitario,
                    'precio_compra' => (float) ($art?->precio_compra ?: $d->costo_unitario),
                    'precio_sin_iva' => (float) ($art?->precio_sin_iva ?: ($art?->pvp ?: $d->costo_unitario)),
                    'pvp' => (float) ($art?->pvp ?: ($art?->precio_sin_iva ?: round($d->costo_unitario * 1.3, 2))),
                    'precios_adicionales' => $art?->precios_adicionales ?? null,
                    'aplica_iva' => (bool) ($art?->aplica_iva ?? false),
                    'iva' => (float) ($art?->iva ?? 0),
                    'subtotal_costo' => (float) $d->subtotal_costo,
                    'lote' => $d->lote,
                    'numero_lote' => $d->numero_lote,
                    'fecha_vencimiento_lote' => $d->fecha_vencimiento_lote?->format('Y-m-d'),
                    'compra_detalle_id' => $d->compra_detalle_id,
                ];
            })->toArray(),
        ];
    }
}
