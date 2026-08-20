<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $compraIds = DB::table('compras')
            ->where('numero_factura', 'like', 'INV-INICIAL-%')
            ->orWhere('proveedor_nombre', 'Inventario Inicial')
            ->pluck('id');

        if ($compraIds->count() > 0) {
            DB::table('compra_detalles')->whereIn('compra_id', $compraIds)->delete();
            DB::table('compras')->whereIn('id', $compraIds)->delete();
        }
    }

    public function down(): void
    {
        // No revert needed
    }
};
