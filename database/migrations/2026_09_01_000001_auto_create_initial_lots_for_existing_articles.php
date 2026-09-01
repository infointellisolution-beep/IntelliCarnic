<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Articulo;
use App\Models\Compra;
use App\Models\CompraDetalle;

return new class extends Migration
{
    public function up(): void
    {
        // Registrar lotes iniciales para todos los articulos pesables que tengan stock > 0 pero no tengan registros en compra_detalles
        foreach (Articulo::where('tipo_articulo', '!=', 'unidad')->where('stock', '>', 0)->get() as $a) {
            $sumLotes = (float) $a->compraDetalles()->sum('cantidad_peso');
            $diff = round((float)$a->stock - $sumLotes, 3);
            if ($diff > 0.001) {
                $compra = Compra::firstOrCreate(
                    ['numero_factura' => 'INI-' . $a->codigo],
                    [
                        'proveedor_id' => null,
                        'proveedor_nombre' => 'Inventario Inicial',
                        'fecha_compra' => $a->created_at ?: now(),
                        'subtotal' => round($diff * (float)$a->precio_compra, 2),
                        'iva' => 0,
                        'total' => round($diff * (float)$a->precio_compra, 2),
                        'observaciones' => 'Lote inicial registrado para balancear stock',
                        'user_id' => null,
                    ]
                );

                CompraDetalle::create([
                    'compra_id' => $compra->id,
                    'articulo_id' => $a->id,
                    'codigo_escaneado' => $a->codigo,
                    'lote' => 'INICIAL',
                    'serie' => '1',
                    'fecha_vencimiento' => now()->addMonths(6)->toDateString(),
                    'cantidad_peso' => $diff,
                    'costo_unitario' => (float)$a->precio_compra,
                    'subtotal' => round($diff * (float)$a->precio_compra, 2),
                ]);
            }
        }
    }

    public function down(): void
    {
        // No destructivo
    }
};
