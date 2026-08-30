# 🛡️ Manual Definitivo: Protección de Código, Ofuscación en GitHub Actions y Licenciamiento Perpetuo Anti-Copia (Laravel + Laragon)

Este manual contiene la arquitectura completa, explicación técnica y el código exacto de los 5 archivos para proteger cualquier sistema Laravel comercializado bajo el modelo de **Licencia Perpetua**, garantizando que el código fuente sea **100% ilegible** y que el sistema **no pueda ser copiado ni ejecutado en computadoras no autorizadas**.

---

## 🏛️ 1. ¿Cómo Funciona la Protección Total (Ofuscación + Bloqueo de Hardware)?

La seguridad del sistema se basa en la combinación de dos barreras que se complementan mutuamente:

```
┌────────────────────────────────────────────────────────────────────────┐
│                        BARRERA 1: OFUSCACIÓN                           │
│  GitHub Actions cifra 'app/' y 'routes/' en cada push a 'dev'.        │
│  Los controladores, modelos y el middleware son 100% ILEGIBLES.        │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ (Nadie puede alterar el código)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                   BARRERA 2: BLOQUEO POR HARDWARE                      │
│  El middleware 'CheckLicense.php' (que ya va cifrado) valida el        │
│  Hardware ID (serial del disco/placa) contra 'storage/app/license.key' │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                  ┌─────────────────┴─────────────────┐
                  ▼                                   ▼
        [MISMA COMPUTADORA]                 [COPIADO A OTRA PC]
     El Hardware ID coincide            El Hardware ID NO coincide
     ✅ SISTEMA INICIA AL INSTANTE       ❌ SISTEMA SE BLOQUEA EN ROJO
```

### ¿Por qué esta combinación es impenetrable para el cliente?
1. **Si intentan copiar la carpeta `C:\laragon\www\proyecto` a otra PC o USB:** En la nueva máquina, el número de serie de la placa madre y disco duro es diferente. El sistema detecta la discrepancia y **se bloquea automáticamente**.
2. **Si intentan borrar la validación de licencia en el código:** Como los archivos PHP están **ofuscados en código binario/hexadecimal enmarañado**, ningún técnico o cliente puede ubicar la línea de código para quitarla o desactivarla.

---

## ⚙️ 2. ¿Cómo Funciona la Ofuscación y qué Partes del Proyecto Cifra?

### 📂 Carpetas que se Cifran en el Workflow:
1. **`app/` completa:**
   * **Controladores (`app/Http/Controllers/`):** Toda la lógica de cobro, inventario, ventas, cálculos de caja, etc.
   * **Modelos (`app/Models/`):** Toda la estructura de datos, relaciones de base de datos y reglas de negocio.
   * **Middlewares y Servicios (`app/Http/Middleware/`, `app/Services/`):** Incluyendo el validador de licencia `CheckLicense.php` y `LicenseService.php`.
2. **`routes/` completa:**
   * Todas las rutas y endpoints del sistema (`web.php`, `api.php`).

### 🧬 Técnicas de Cifrado Aplicadas por Yakpro-PO:
* **Renombrado Hexadecimal:** Nombres de variables y funciones se convierten en cadenas como `$GLOBALS['\x61\x62\x63']` o `$o0O0_0O = ...`.
* **Cifrado de Cadenas y Consultas:** Textos, fórmulas y queries SQL se codifican en bloques Base64 y valores hexadecimales comprimidos.
* **Aplanamiento de Flujo de Control (*Control Flow Flattening*):** Desordena la estructura de `if/else` y bucles en complejas matrices matemáticas para impedir ingeniería inversa.
* **Eliminación Total de Comentarios:** Se borra cualquier comentario o estructura legible.

> 📄 **Resultado:** Laragon y PHP ejecutan el código a **velocidad nativa normal**, pero si alguien abre cualquier archivo `.php` en el Bloc de Notas o VS Code, solo verá miles de líneas de caracteres revueltos e indescifrables.

---

## ☁️ 3. Flujo de Trabajo en GitHub Actions y Carpeta `instalar/`

### A. La Primera Entrega al Cliente (Día 1):
1. En tu PC programas en código limpio y haces `git push origin dev`.
2. GitHub Actions ofusca el código en 5 segundos y lo coloca en la rama **`main`**.
3. En tu GitHub, descargas el ZIP de la rama **`main`** (*Code → Download ZIP*).
4. **Ese ZIP es el que le instalas al cliente**, el cual ya viene **100% ofuscado y protegido desde el primer día**.

### B. Actualizaciones Futuras:
1. Cada vez que hagas mejoras, solo haces `git push origin dev`.
2. GitHub Actions ofusca y actualiza la rama **`main`** en automático.
3. En la PC del cliente, al hacer doble clic en **`actualizar_sistema.bat`** (que ejecuta `git pull origin main`), el cliente descarga las mejoras ya cifradas y corre las migraciones sin exponer tu código.

---

## 💻 4. Código Completo de los 5 Archivos para el Bloqueo por Hardware

A continuación tienes el código exacto de los 5 archivos listos para copiar en cualquier proyecto Laravel:

---

### 📄 ARCHIVO 1 [NUEVO]: `app/Services/LicenseService.php`
*Crea este archivo en `app/Services/LicenseService.php` (si la carpeta `Services` no existe, créala).*

```php
<?php

namespace App\Services;

class LicenseService
{
    // Clave secreta para firmar licencias (Cámbiala por una única para tu empresa)
    private string $secretKey = 'MI_CLAVE_MAESTRA_SECRETA_SISTEMA_2026';

    /**
     * Obtiene el Hardware ID único del equipo (Número de serie del disco y BIOS).
     */
    public function getHardwareId(): string
    {
        $serial = '';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // 1. Obtener serial del Disco Duro principal en Windows
            $output = [];
            @exec('wmic diskdrive get serialnumber', $output);
            if (!empty($output)) {
                $lines = array_filter(array_map('trim', $output));
                array_shift($lines); // Eliminar encabezado
                $serial = implode('-', $lines);
            }

            // 2. Fallback: Serial de la BIOS / Motherboard
            if (empty($serial)) {
                $outputBios = [];
                @exec('wmic bios get serialnumber', $outputBios);
                $linesBios = array_filter(array_map('trim', $outputBios));
                array_shift($linesBios);
                $serial = implode('-', $linesBios);
            }
        }

        // Fallback genérico si no se pudo leer wmic
        if (empty($serial)) {
            $serial = php_uname('n') . '-' . php_uname('m');
        }

        return strtoupper(hash('sha256', $serial . $this->secretKey));
    }

    /**
     * Genera una Licencia Perpetua firmada criptográficamente para un cliente.
     */
    public function generatePerpetualLicense(string $hardwareId, string $clientName): string
    {
        $payload = [
            'hwid' => $hardwareId,
            'client' => $clientName,
            'type' => 'PERPETUAL',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $json = json_encode($payload);
        $signature = hash_hmac('sha256', $json, $this->secretKey);

        return base64_encode(json_encode([
            'data' => $payload,
            'sig' => $signature,
        ]));
    }

    /**
     * Valida si el archivo storage/app/license.key es auténtico y pertenece a esta PC.
     */
    public function validateLicense(): array
    {
        $licensePath = storage_path('app/license.key');

        if (!file_exists($licensePath)) {
            return [
                'valid' => false,
                'message' => 'No se encontró el archivo de activación (license.key).'
            ];
        }

        $content = trim(file_get_contents($licensePath));
        $decoded = json_decode(base64_decode($content), true);

        if (!$decoded || !isset($decoded['data']) || !isset($decoded['sig'])) {
            return [
                'valid' => false,
                'message' => 'El archivo de licencia está dañado o tiene un formato inválido.'
            ];
        }

        $payload = $decoded['data'];
        $signature = $decoded['sig'];

        // 1. Verificar firma HMAC SHA-256 (Evita que alteren el archivo a mano)
        $expectedSig = hash_hmac('sha256', json_encode($payload), $this->secretKey);
        if (!hash_equals($expectedSig, $signature)) {
            return [
                'valid' => false,
                'message' => 'La firma digital de la licencia no es válida o fue alterada.'
            ];
        }

        // 2. Validar que el Hardware ID coincida exactamente con esta máquina
        $currentHwid = $this->getHardwareId();
        if ($payload['hwid'] !== $currentHwid) {
            return [
                'valid' => false,
                'message' => 'Esta licencia fue autorizada para otra computadora. No se permite la clonación del sistema.'
            ];
        }

        return ['valid' => true, 'payload' => $payload];
    }
}
```

---

### 📄 ARCHIVO 2 [NUEVO]: `app/Http/Middleware/CheckLicense.php`
*Crea este archivo en `app/Http/Middleware/CheckLicense.php`.*

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseService;

class CheckLicense
{
    public function handle(Request $request, Closure $next)
    {
        // Permitir rutas de consulta de Hardware ID y diagnóstico sin bloqueo
        if ($request->is('licencia/*')) {
            return $next($request);
        }

        $service = new LicenseService();
        $check = $service->validateLicense();

        // Si la licencia no es válida para esta máquina, mostrar pantalla de bloqueo
        if (!$check['valid']) {
            return response()->view('errors.licencia_invalida', [
                'errorMensaje' => $check['message'],
                'hardwareId' => $service->getHardwareId(),
            ], 403);
        }

        return $next($request);
    }
}
```

---

### 📄 ARCHIVO 3 [MODIFICAR]: Registro en `bootstrap/app.php` *(Laravel 11)*
*Abre `bootstrap/app.php` y añade `CheckLicense` al grupo `web`:*

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Registrar el validador de licencia para todas las peticiones web
        $middleware->web(append: [
            \App\Http\Middleware\CheckLicense::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

*(En **Laravel 10**, se agrega en `app/Http/Kernel.php` dentro del arreglo `$middlewareGroups['web']`).*

---

### 📄 ARCHIVO 4 [MODIFICAR]: `routes/web.php`
*Agrega al final de `routes/web.php` la ruta para consultar el Hardware ID:*

```php
use App\Services\LicenseService;

// Ruta pública para ver el Hardware ID al instalar en una nueva PC
Route::get('/licencia/hwid', function () {
    $service = new LicenseService();
    return response()->json([
        'hardware_id' => $service->getHardwareId(),
        'sistema' => config('app.name'),
        'fecha' => date('Y-m-d H:i:s'),
    ]);
});
```

---

### 📄 ARCHIVO 5 [NUEVO]: `resources/views/errors/licencia_invalida.blade.php`
*Crea este archivo en `resources/views/errors/licencia_invalida.blade.php`.*

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activación de Licencia Requerida</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 20px; max-width: 540px; width: 100%; padding: 2.5rem; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6); }
        .badge { background: #ef4444; color: #fff; padding: 0.35rem 0.85rem; border-radius: 9999px; font-weight: 700; font-size: 0.8rem; letter-spacing: 0.05em; display: inline-block; margin-bottom: 1.25rem; }
        .title { font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; color: #fff; }
        .desc { color: #94a3b8; font-size: 0.95rem; line-height: 1.5; margin-bottom: 1.5rem; }
        .hwid-label { font-size: 0.85rem; font-weight: 600; color: #cbd5e1; margin-bottom: 0.5rem; text-align: left; }
        .hwid-box { background: #0f172a; border: 1px solid #475569; padding: 1rem; border-radius: 10px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.95rem; color: #38bdf8; word-break: break-all; margin-bottom: 1.5rem; user-select: all; cursor: pointer; }
        .hwid-box:hover { border-color: #38bdf8; }
        .instructions { font-size: 0.85rem; color: #64748b; line-height: 1.4; }
        .btn-reload { margin-top: 1.5rem; background: #0284c7; color: #fff; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: background 0.2s; }
        .btn-reload:hover { background: #0369a1; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">SISTEMA BLOQUEADO</span>
        <h1 class="title">Licencia Requerida</h1>
        <p class="desc">{{ $errorMensaje }}</p>
        
        <div class="hwid-label">ID Único de esta Computadora (Hardware ID):</div>
        <div class="hwid-box" title="Haz clic para seleccionar todo" onclick="window.getSelection().selectAllChildren(this)">{{ $hardwareId }}</div>
        
        <p class="instructions">
            Copia el código superior y envíaselo a tu proveedor para activar tu <b>Licencia Perpetua</b>.<br>
            Una vez colocado el archivo <code>storage/app/license.key</code>, recarga el sistema.
        </p>

        <button class="btn-reload" onclick="window.location.reload()">🔄 Verificar Licencia</button>
    </div>
</body>
</html>
```

---

## 🚀 5. Proceso Completo: Paso a Paso para Nuevos Proyectos

### Paso 1: Configurar los 5 archivos
En tu código limpio agregas los 5 archivos listados arriba.

### Paso 2: Subir a GitHub
Haces tu `git push origin dev`. GitHub Actions ofusca todo en automático y lo publica en `main`.

### Paso 3: Instalar al Cliente
Descargas el ZIP de la rama `main` de GitHub y lo descomprimes en `C:\laragon\www\tuProyecto` en la PC del cliente.

### Paso 4: Emitir la Licencia Perpetua
1. Abres el navegador en la PC del cliente: `http://tuproyecto.test`.
2. Verás la pantalla roja con su **Hardware ID** (ej: `A8F93B...`).
3. En tu PC de desarrollo, abres `php artisan tinker` y ejecutas:
   ```php
   $service = new App\Services\LicenseService();
   echo $service->generatePerpetualLicense('A8F93B...', 'Nombre del Cliente');
   ```
4. Copias el texto resultante, creas un archivo de texto llamado **`license.key`** y lo pegas en `storage/app/license.key` dentro de la PC del cliente.
5. Haces clic en **"Verificar Licencia"** y el sistema queda activado para siempre en esa computadora.

### Paso 5: Actualizaciones Futuras
Cuando hagas cambios futuros y subas un push a `dev`, el cliente solo hace doble clic en **`actualizar_sistema.bat`** para recibir las mejoras ya ofuscadas sin que jamás tenga acceso a tu código original.
