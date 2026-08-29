<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = Auth::user();

        if (!$user) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'No autenticado.'], 401);
            }
            return redirect()->route('login');
        }

        // Si el usuario es administrador, permitir acceso absoluto
        if ($user->isAdministrator()) {
            return $next($request);
        }

        // Verificar si el usuario tiene alguno de los permisos requeridos
        if ($user->hasAnyPermission($permissions)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'error' => 'No tienes los permisos necesarios para realizar esta acción.',
            ], 403);
        }

        return redirect()
            ->route('dashboard')
            ->withErrors(['permission' => 'Acceso denegado: No tienes autorización para acceder a este módulo.']);
    }
}
