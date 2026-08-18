<?php

namespace App\Http\Controllers;

use App\Models\Abono;
use App\Models\CajaMovimiento;
use App\Models\CajaSesion;
use App\Models\Cliente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim($request->get('q', ''));
        $filtroSaldo = $request->get('filtro_saldo', 'todos');

        // Métricas KPI
        $totalCartera = (float) Cliente::sum('saldo_deudor');
        $totalClientes = Cliente::count();
        $clientesConDeuda = Cliente::where('saldo_deudor', '>', 0)->count();
        $abonosMes = (float) Abono::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('monto');

        $query = Cliente::query();

        if ($q !== '') {
            $query->where(function ($k) use ($q) {
                $k->where('nombre', 'like', "%{$q}%")
                  ->orWhere('identificacion', 'like', "%{$q}%")
                  ->orWhere('telefono', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($filtroSaldo === 'con_deuda') {
            $query->where('saldo_deudor', '>', 0);
        } elseif ($filtroSaldo === 'al_dia') {
            $query->where('saldo_deudor', '<=', 0);
        }

        $clientes = $query->orderBy('nombre', 'asc')->paginate(15);

        return view('clientes.index', compact(
            'clientes',
            'q',
            'filtroSaldo',
            'totalCartera',
            'totalClientes',
            'clientesConDeuda',
            'abonosMes'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'identificacion' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'limite_credito' => 'nullable|numeric|min:0',
            'notas' => 'nullable|string|max:1000',
        ]);

        $data['limite_credito'] = $data['limite_credito'] ?? 0;
        $data['saldo_deudor'] = 0;
        $data['estado'] = 'activo';

        Cliente::create($data);

        return redirect()->route('clientes.index')->with('success', 'Cliente registrado correctamente.');
    }

    public function show(Cliente $cliente): View
    {
        $cliente->actualizarSaldoDeudor();
        $cliente->load([
            'ventas' => function ($q) {
                $q->orderBy('created_at', 'desc')->with('detalles.articulo');
            },
            'abonos' => function ($q) {
                $q->orderBy('created_at', 'desc')->with(['user', 'cajaSesion']);
            }
        ]);

        $totalAbonos = (float) $cliente->abonos()->sum('monto');
        $creditoSalesAsc = $cliente->ventas()
            ->where('tipo_venta', 'credito')
            ->orderBy('created_at', 'asc')
            ->get();

        $accumulatedAbonos = $totalAbonos;
        $estadoCreditoVentas = [];

        foreach ($creditoSalesAsc as $cs) {
            if ($cs->estado === 'devuelta') {
                $estadoCreditoVentas[$cs->id] = [
                    'estado' => 'devuelta',
                    'label' => 'Devuelta (Anulada)',
                    'color' => '#dc2626',
                    'bg' => 'rgba(220, 38, 38, 0.15)',
                    'pendiente' => 0,
                ];
                continue;
            }

            $saleTotal = (float) $cs->total;
            if ($accumulatedAbonos >= $saleTotal) {
                $estadoCreditoVentas[$cs->id] = [
                    'estado' => 'pagado',
                    'label' => 'Saldado / Pagado',
                    'color' => '#10b981',
                    'bg' => 'rgba(16, 185, 129, 0.15)',
                    'pendiente' => 0,
                ];
                $accumulatedAbonos -= $saleTotal;
            } elseif ($accumulatedAbonos > 0) {
                $pendiente = round($saleTotal - $accumulatedAbonos, 2);
                $estadoCreditoVentas[$cs->id] = [
                    'estado' => 'parcial',
                    'label' => 'Abonado (Pendiente: $' . number_format($pendiente, 2) . ')',
                    'color' => '#d97706',
                    'bg' => 'rgba(245, 158, 11, 0.15)',
                    'pendiente' => $pendiente,
                ];
                $accumulatedAbonos = 0;
            } else {
                $estadoCreditoVentas[$cs->id] = [
                    'estado' => 'pendiente',
                    'label' => 'Crédito Pendiente',
                    'color' => '#dc2626',
                    'bg' => 'rgba(239, 68, 68, 0.12)',
                    'pendiente' => $saleTotal,
                ];
            }
        }

        return view('clientes.show', compact('cliente', 'estadoCreditoVentas'));
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'identificacion' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string|max:500',
            'limite_credito' => 'nullable|numeric|min:0',
            'estado' => 'required|string|in:activo,inactivo',
            'notas' => 'nullable|string|max:1000',
        ]);

        $cliente->update($data);
        $cliente->actualizarSaldoDeudor();

        return redirect()->back()->with('success', 'Datos del cliente actualizados correctamente.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->actualizarSaldoDeudor();

        if ((float) $cliente->saldo_deudor > 0) {
            return redirect()->back()->withErrors([
                'cliente' => "No se puede eliminar el cliente '{$cliente->nombre}' porque tiene un saldo deudor pendiente de $" . number_format($cliente->saldo_deudor, 2)
            ]);
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado correctamente.');
    }

    public function registrarAbono(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|string|in:efectivo,tarjeta,transferencia',
            'notas' => 'nullable|string|max:255',
        ]);

        $cliente->actualizarSaldoDeudor();
        $saldoAnterior = (float) $cliente->saldo_deudor;
        $montoAbono = (float) $data['monto'];

        if ($montoAbono > $saldoAnterior && $saldoAnterior > 0) {
            // Permitir abonar máximo el saldo pendiente o ajustarlo
            $montoAbono = $saldoAnterior;
        }

        if ($saldoAnterior <= 0) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'El cliente no tiene saldo deudor pendiente.'], 422);
            }
            return redirect()->back()->withErrors(['abono' => 'El cliente no tiene saldo deudor pendiente.']);
        }

        $cajaActiva = CajaSesion::where('estado', 'abierta')->first();

        DB::beginTransaction();
        try {
            $saldoNuevo = max(0, round($saldoAnterior - $montoAbono, 2));

            $abono = Abono::create([
                'cliente_id' => $cliente->id,
                'user_id' => auth()->id(),
                'caja_sesion_id' => $cajaActiva?->id,
                'monto' => $montoAbono,
                'metodo_pago' => $data['metodo_pago'],
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'notas' => $data['notas'] ?: 'Abono a cuenta',
            ]);

            $cliente->saldo_deudor = $saldoNuevo;
            $cliente->save();

            // Si el abono es en efectivo y hay caja abierta, registrar ingreso en caja
            if ($data['metodo_pago'] === 'efectivo' && $cajaActiva) {
                CajaMovimiento::create([
                    'caja_sesion_id' => $cajaActiva->id,
                    'user_id' => auth()->id(),
                    'tipo' => 'entrada',
                    'monto' => $montoAbono,
                    'concepto' => "Abono de Cliente: {$cliente->nombre}",
                    'observaciones' => "Abono #{$abono->id} a cuenta a crédito. Saldo pendiente: $" . number_format($saldoNuevo, 2),
                ]);
                $cajaActiva->recargarTotales();
            }

            DB::commit();

            session()->flash('abono_id', $abono->id);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Abono registrado correctamente.',
                    'abono' => $abono->load(['cliente', 'user'])
                ]);
            }

            return redirect()->back()->with('success', 'Abono de $' . number_format($montoAbono, 2) . ' registrado con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error al registrar el abono.'], 500);
            }
            return redirect()->back()->withErrors(['abono' => 'Error al registrar el abono.']);
        }
    }

    public function getAbonoTicket($id): JsonResponse
    {
        $abono = Abono::with(['cliente', 'user'])->find($id);
        if (!$abono) {
            return response()->json(['success' => false, 'message' => 'Abono no encontrado.'], 444);
        }

        $settings = \App\Models\Setting::values();

        return response()->json([
            'success' => true,
            'abono' => $abono,
            'settings' => [
                'empresa_nombre' => $settings['empresa_nombre'] ?? 'IntelliCarnic',
                'empresa_ruc' => $settings['empresa_ruc'] ?? '000000000',
                'empresa_direccion' => $settings['empresa_direccion'] ?? 'Dirección de la empresa',
            ]
        ]);
    }

    public function buscar(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));
        if (!$q) {
            return response()->json(['clientes' => []]);
        }

        $clientes = Cliente::where('estado', 'activo')
            ->where(function ($k) use ($q) {
                $k->where('nombre', 'like', "%{$q}%")
                  ->orWhere('identificacion', 'like', "%{$q}%")
                  ->orWhere('telefono', 'like', "%{$q}%");
            })
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'clientes' => $clientes
        ]);
    }

    public function storeRapido(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'identificacion' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:50',
            'limite_credito' => 'nullable|numeric|min:0',
        ]);

        $data['limite_credito'] = $data['limite_credito'] ?? 0;
        $data['saldo_deudor'] = 0;
        $data['estado'] = 'activo';

        $cliente = Cliente::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado exitosamente.',
            'cliente' => $cliente
        ]);
    }
}
