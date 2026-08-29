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
        // Permitir forzar modo escritorio con parámetro ?desktop=1 o desactivarlo con ?desktop=0
        if ($request->has('desktop')) {
            if ($request->query('desktop') === '1') {
                session(['force_desktop' => true]);
                session()->forget('force_handheld');
            } else {
                session()->forget('force_desktop');
            }
        }

        // Permitir forzar modo handheld con parámetro ?handheld=1 o desactivarlo con ?handheld=0
        if ($request->has('handheld')) {
            if ($request->query('handheld') === '1') {
                session(['force_handheld' => true]);
                session()->forget('force_desktop');
            } else {
                session()->forget('force_handheld');
            }
        }

        // Si el usuario no ha iniciado sesión, no redirigir a rutas protegidas de handheld
        if (!auth()->check()) {
            return $next($request);
        }

        // Si ya está en rutas de handheld, login, logout, api o tickets, continuar
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

        // Si el usuario forzó modo escritorio en esta sesión, permitir acceso normal de escritorio
        if (session('force_desktop', false)) {
            return $next($request);
        }

        $userAgent = $request->userAgent() ?? '';
        
        // Detección de dispositivos móviles (teléfonos, terminales Zebra, Honeywell, etc.)
        $isMobile = (bool) preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino|zebra|tc51|tc56|tc57|tc52|symbol|motorola|honeywell/i', $userAgent);

        if (($isMobile || session('force_handheld', false)) && auth()->check()) {
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
