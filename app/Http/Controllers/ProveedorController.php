<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\CompraDetalle;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim($request->get('q', ''));
        $filtroEstado = $request->get('filtro_estado', 'todos');

        // Tarjetas KPI
        $totalProveedores = Proveedor::count();
        $proveedoresActivos = Proveedor::where('estado', 'activo')->count();
        
        $inicioMes = now()->startOfMonth()->toDateString() . ' 00:00:00';
        $finMes = now()->endOfMonth()->toDateString() . ' 23:59:59';
        $comprasMesMonto = (float) Compra::whereBetween('fecha_compra', [$inicioMes, $finMes])->sum('total');

        $topProveedorRecord = Compra::select('proveedor_id', DB::raw('SUM(total) as total_invertido'))
            ->whereNotNull('proveedor_id')
            ->groupBy('proveedor_id')
            ->orderByDesc('total_invertido')
            ->with('proveedor')
            ->first();

        // Consulta Paginada
        $query = Proveedor::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('contacto_nombre', 'like', "%{$q}%")
                    ->orWhere('identificacion', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%")
                    ->orWhere('correo', 'like', "%{$q}%");
            });
        }

        if ($filtroEstado !== 'todos') {
            $query->where('estado', $filtroEstado);
        }

        $proveedores = $query->withCount('compras')
            ->withSum('compras', 'total')
            ->orderBy('nombre', 'asc')
            ->paginate(12);

        return view('proveedores.index', compact(
            'proveedores',
            'q',
            'filtroEstado',
            'totalProveedores',
            'proveedoresActivos',
            'comprasMesMonto',
            'topProveedorRecord'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto_nombre' => 'nullable|string|max:255',
            'identificacion' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:50',
            'correo' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'estado' => 'required|string|in:activo,inactivo',
            'notas' => 'nullable|string|max:1000',
        ]);

        Proveedor::create($data);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor registrado correctamente.');
    }

    public function show(Proveedor $proveedor): View
    {
        $proveedor->load([
            'compras' => function ($q) {
                $q->orderBy('fecha_compra', 'desc')->with(['detalles.articulo', 'user']);
            }
        ]);

        $totalInvertido = (float) $proveedor->compras->sum('total');
        $totalComprasCount = $proveedor->compras->count();
        $promedioCompra = $totalComprasCount > 0 ? $totalInvertido / $totalComprasCount : 0;
        $ultimaCompra = $proveedor->compras->first();

        // Artículos adquiridos de este proveedor
        $articulosAdquiridos = CompraDetalle::whereHas('compra', function ($q) use ($proveedor) {
            $q->where('proveedor_id', $proveedor->id);
        })
            ->select('articulo_id', DB::raw('SUM(cantidad_peso) as total_peso'), DB::raw('SUM(subtotal) as total_monto'))
            ->with('articulo')
            ->groupBy('articulo_id')
            ->orderByDesc('total_monto')
            ->get();

        return view('proveedores.show', compact(
            'proveedor',
            'totalInvertido',
            'totalComprasCount',
            'promedioCompra',
            'ultimaCompra',
            'articulosAdquiridos'
        ));
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto_nombre' => 'nullable|string|max:255',
            'identificacion' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:50',
            'correo' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'estado' => 'required|string|in:activo,inactivo',
            'notas' => 'nullable|string|max:1000',
        ]);

        $proveedor->update($data);

        return redirect()->back()->with('success', 'Datos del proveedor actualizados correctamente.');
    }

    public function destroy(Proveedor $proveedor): RedirectResponse
    {
        if ($proveedor->compras()->count() > 0) {
            return redirect()->back()->withErrors([
                'proveedor' => 'No se puede eliminar a ' . $proveedor->nombre . ' porque tiene facturas de compra registradas. Puedes cambiar su estado a Inactivo.'
            ]);
        }

        $proveedor->delete();

        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado correctamente.');
    }
}
