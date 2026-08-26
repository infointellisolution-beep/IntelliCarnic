<?php

namespace App\Http\Controllers;

use App\Models\CajaMovimiento;
use App\Models\CajaSesion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CajaController extends Controller
{
    /**
     * Muestra la vista principal del módulo de caja
     */
    public function index(): View
    {
        $cajaActiva = CajaSesion::query()
            ->where('estado', 'abierta')
            ->orderBy('id', 'desc')
            ->first();

        if ($cajaActiva) {
            $cajaActiva->recargarTotales();
            $cajaActiva->load(['movimientos.user', 'user', 'ventas', 'devoluciones.detalles', 'devoluciones.user']);
        }

        $historialCierres = CajaSesion::query()
            ->where('estado', 'cerrada')
            ->with('user')
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('caja.index', compact('cajaActiva', 'historialCierres'));
    }

    /**
     * Registra la Apertura de Caja con el Monto Inicial / Fondo
     */
    public function aperturar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'monto_inicial' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $caja = null;
        $error = null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, &$caja, &$error) {
            $cajaExistente = CajaSesion::query()
                ->where('estado', 'abierta')
                ->lockForUpdate()
                ->first();

            if ($cajaExistente) {
                $error = 'Ya existe una caja abierta activa en el sistema.';
                return;
            }

            $caja = CajaSesion::create([
                'user_id' => Auth::id(),
                'monto_inicial' => $data['monto_inicial'],
                'total_ventas_efectivo' => 0,
                'total_ventas_tarjeta' => 0,
                'total_ventas_transferencia' => 0,
                'total_entradas' => 0,
                'total_salidas' => 0,
                'saldo_esperado' => $data['monto_inicial'],
                'fecha_apertura' => now(),
                'estado' => 'abierta',
                'observaciones' => $data['observaciones'] ?? null,
            ]);
        });

        if ($error) {
            return redirect()
                ->route('caja.index')
                ->withErrors(['caja' => $error]);
        }

        return redirect()
            ->route('caja.index')
            ->with('status', '¡Caja aperturada exitosamente con un fondo inicial de $' . number_format($caja->monto_inicial, 2) . '!');
    }

    /**
     * Registra un movimiento de dinero manual (Entrada o Salida / Pago a Proveedor / Retiro)
     */
    public function storeMovimiento(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:entrada,salida'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'concepto' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $error = null;
        $tipoTexto = $data['tipo'] === 'entrada' ? 'Entrada de dinero' : 'Salida de dinero';

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, &$error) {
            $cajaActiva = CajaSesion::query()
                ->where('estado', 'abierta')
                ->lockForUpdate()
                ->first();

            if (! $cajaActiva) {
                $error = 'Debe aperturar una caja antes de registrar movimientos de dinero.';
                return;
            }

            CajaMovimiento::create([
                'caja_sesion_id' => $cajaActiva->id,
                'user_id' => Auth::id(),
                'tipo' => $data['tipo'],
                'monto' => $data['monto'],
                'concepto' => $data['concepto'],
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            $cajaActiva->recargarTotales();
        });

        if ($error) {
            return redirect()
                ->route('caja.index')
                ->withErrors(['caja' => $error]);
        }

        return redirect()
            ->route('caja.index')
            ->with('status', '¡' . $tipoTexto . ' de $' . number_format($data['monto'], 2) . ' registrada correctamente!');
    }

    /**
     * Realiza el Arqueo y Cierre de Caja
     */
    public function cerrar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'saldo_real' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        $cajaId = null;
        $statusMsg = null;
        $error = null;

        \Illuminate\Support\Facades\DB::transaction(function () use ($data, &$cajaId, &$statusMsg, &$error) {
            $cajaActiva = CajaSesion::query()
                ->where('estado', 'abierta')
                ->lockForUpdate()
                ->first();

            if (! $cajaActiva) {
                $error = 'No hay ninguna caja abierta activa para cerrar.';
                return;
            }

            $cajaActiva->recargarTotales();

            $saldoEsperado = (float) $cajaActiva->saldo_esperado;
            $saldoReal = (float) $data['saldo_real'];
            $diferencia = $saldoReal - $saldoEsperado;

            $cajaActiva->update([
                'saldo_real' => $saldoReal,
                'diferencia' => $diferencia,
                'fecha_cierre' => now(),
                'estado' => 'cerrada',
                'observaciones' => trim(($cajaActiva->observaciones ? $cajaActiva->observaciones . ' | ' : '') . ($data['observaciones'] ?? '')),
            ]);

            $cajaId = $cajaActiva->id;
            $statusMsg = '¡Caja cerrada correctamente! Saldo esperado: $' . number_format($saldoEsperado, 2) . ' | Conteo físico: $' . number_format($saldoReal, 2);
            if (abs($diferencia) < 0.01) {
                $statusMsg .= ' (Caja Cuadrada Perfecta)';
            } elseif ($diferencia > 0) {
                $statusMsg .= ' (Sobrante: +$' . number_format($diferencia, 2) . ')';
            } else {
                $statusMsg .= ' (Faltante: -$' . number_format(abs($diferencia), 2) . ')';
            }
        });

        if ($error) {
            return redirect()
                ->route('caja.index')
                ->withErrors(['caja' => $error]);
        }

        return redirect()
            ->route('caja.index')
            ->with('status', $statusMsg)
            ->with('cierre_id', $cajaId);
    }

    /**
     * Vista de Ticket Térmico de Corte de Caja Z
     */
    public function ticketCierre(CajaSesion $cajaSesion): View
    {
        $cajaSesion->load(['user', 'movimientos.user', 'ventas']);
        return view('caja.ticket_cierre', compact('cajaSesion'));
    }
}
