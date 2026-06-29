<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $settings = \App\Models\Setting::values();
        $today = now()->startOfDay();

        $ventasHoy = \App\Models\Venta::where('created_at', '>=', $today)->count();
        $cajaHoy = \App\Models\Venta::where('created_at', '>=', $today)->sum('total');
        
        $pesoVendido = \App\Models\VentaDetalle::whereHas('venta', function ($q) use ($today) {
            $q->where('created_at', '>=', $today);
        })->sum('cantidad');

        // Para la gráfica: últimos 7 días
        $graficaVentas = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->startOfDay();
            $suma = \App\Models\Venta::where('created_at', '>=', $fecha)
                ->where('created_at', '<', $fecha->copy()->endOfDay())
                ->sum('total');
            $graficaVentas[] = [
                'fecha' => $fecha->format('d/m'),
                'total' => $suma
            ];
        }

        return view('dashboard.index', compact('settings', 'ventasHoy', 'cajaHoy', 'pesoVendido', 'graficaVentas'));
    }
}
