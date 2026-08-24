<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectHandheldDevice
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Permitir forzar modo escritorio con parámetro ?desktop=1
        if ($request->has('desktop')) {
            if ($request->query('desktop') === '1') {
                session(['force_desktop' => true]);
            } else {
                session()->forget('force_desktop');
            }
        }

        // Si el usuario no ha iniciado sesión, no redirigir a rutas protegidas de handheld
        if (!auth()->check()) {
            return $next($request);
        }

        // Si ya está en rutas móviles, de autenticación, api/ajax o utilidades, continuar normalmente
        if (
            $request->is('handheld*') ||
            $request->is('login*') ||
            $request->is('logout*') ||
            $request->is('dev*') ||
            $request->is('vender/ticket*') ||
            $request->is('clientes/abono/*/ticket') ||
            $request->is('caja/ticket-cierre*') ||
            $request->is('reportes/exportar*') ||
            $request->expectsJson() ||
            $request->ajax()
        ) {
            return $next($request);
        }

        $userAgent = $request->userAgent() ?? '';
        $isMobile = (bool) preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|zebra|tc51|tc56|tc57|tc52/i', $userAgent);

        if ($isMobile && auth()->check()) {
            if ($request->is('compras*')) {
                return redirect()->route('handheld.compras');
            }
            if ($request->is('vender*')) {
                return redirect()->route('handheld.tpv');
            }
            return redirect()->route('handheld.index');
        }

        return $next($request);
    }
}
