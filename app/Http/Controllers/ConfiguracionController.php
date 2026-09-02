<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    private const PROTECTED_ADMIN_EMAIL = 'admin@gmail.com';

    public function index(): View
    {
        $backupService = new \App\Services\DatabaseBackupService();
        // Verificar si corresponde generar respaldo automático según la programación
        try {
            $backupService->checkAndRunAutomaticBackup('scheduled');
        } catch (\Throwable $e) {}

        $users = User::query()->with('sucursal')->orderBy('name', 'asc')->get();

        // Información de Git para la vista
        $git = $this->getGitBinary();
        $gitRepoUrl = 'No detectado / No vinculado';
        $gitBranch = 'main';
        $gitLastCommit = '';

        if ($git) {
            $rawRepo = trim($this->safeShellExec($git . ' remote get-url origin 2>&1') ?? '');
            if (!empty($rawRepo) && !str_contains(strtolower($rawRepo), 'fatal') && !str_contains(strtolower($rawRepo), 'error') && !str_contains(strtolower($rawRepo), 'no se reconoce')) {
                $gitRepoUrl = $rawRepo;
            }
            $rawBranch = trim($this->safeShellExec($git . ' branch --show-current 2>&1') ?? '');
            if (!empty($rawBranch) && !str_contains(strtolower($rawBranch), 'fatal') && !str_contains(strtolower($rawBranch), 'no se reconoce')) {
                $gitBranch = $rawBranch;
            }
            $rawCommit = trim($this->safeShellExec($git . ' log -1 --pretty=format:"%h|%s|%ar" 2>&1') ?? '');
            if (!empty($rawCommit) && !str_contains(strtolower($rawCommit), 'fatal') && !str_contains(strtolower($rawCommit), 'no se reconoce')) {
                $gitLastCommit = $rawCommit;
            }
        }

        if ($gitRepoUrl === 'No detectado / No vinculado') {
            $gitRepoUrl = Setting::getValue('github_url', 'No detectado / No vinculado');
        }

        $lanUrl = 'http://' . (request()->server('SERVER_ADDR') ?? '127.0.0.1') . '/' . basename(base_path()) . '/public';

        return view('configuracion.index', [
            'settings'       => Setting::values(),
            'users'          => $users,
            'sucursales'     => \App\Models\Sucursal::orderBy('nombre', 'asc')->get(),
            'sucursalActual' => \App\Models\Sucursal::actual(),
            'backups'        => $backupService->listBackups(),
            'dbStats'        => $backupService->getDatabaseStats(),
            'reminderStatus' => $backupService->getBackupReminderStatus(),
            'allModules'     => User::getAllModulesAndPermissions(),
            'gitRepoUrl'     => $gitRepoUrl,
            'gitBranch'      => $gitBranch ?: 'main',
            'gitLastCommit'  => $gitLastCommit,
            'lanUrl'         => $lanUrl,
        ]);
    }


    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'modo_inventario' => ['required', Rule::in(['dinamico', 'simple'])],
            'unidad_peso' => ['required', Rule::in(['kg', 'lb'])],
            'usar_impuestos' => ['nullable', 'boolean'],
            'iva_global_enabled' => ['nullable', 'boolean'],
            'iva_global_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'timezone' => ['required', 'string', 'max:100'],
        ]);

        Setting::setValue('modo_inventario', $data['modo_inventario']);
        Setting::setValue('unidad_peso', $data['unidad_peso']);
        Setting::setValue('usar_impuestos', $request->boolean('usar_impuestos') ? '1' : '0');
        Setting::setValue('iva_global_enabled', $request->boolean('iva_global_enabled') ? '1' : '0');
        Setting::setValue('iva_global_rate', $data['iva_global_rate']);
        Setting::setValue('timezone', $data['timezone']);

        return redirect()
            ->route('configuracion.index')
            ->with('status', 'Configuración general guardada correctamente.');
    }

    public function updateEmpresa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'empresa_nombre' => ['nullable', 'string', 'max:255'],
            'empresa_direccion' => ['nullable', 'string', 'max:500'],
            'empresa_correo' => ['nullable', 'email', 'max:255'],
            'empresa_celular' => ['nullable', 'string', 'max:50'],
        ]);

        Setting::setValue('empresa_nombre', $data['empresa_nombre'] ?? '');
        Setting::setValue('empresa_direccion', $data['empresa_direccion'] ?? '');
        Setting::setValue('empresa_correo', $data['empresa_correo'] ?? '');
        Setting::setValue('empresa_celular', $data['empresa_celular'] ?? '');

        if ($request->boolean('empresa_logo_remove')) {
            $oldLogo = Setting::getValue('empresa_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::setValue('empresa_logo', '');
        }

        if ($request->hasFile('empresa_logo')) {
            $request->validate([
                'empresa_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            ]);

            $oldLogo = Setting::getValue('empresa_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('empresa_logo')->store('empresa', 'public');
            Setting::setValue('empresa_logo', $path);
        }

        return redirect()
            ->route('configuracion.index', ['tab' => 'empresa'])
            ->with('status', 'Información de la empresa guardada correctamente.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:administrador,encargado,vendedor'],
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $sucursalActual = \App\Models\Sucursal::actual();
        $sucursalId = !empty($data['sucursal_id']) ? $data['sucursal_id'] : ($sucursalActual ? $sucursalActual->id : null);
        $defaultPermissions = User::getDefaultPermissionsForRole($data['role']);

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'sucursal_id' => $sucursalId,
            'permissions' => $defaultPermissions,
            'is_active' => true,
            'password' => Hash::make($data['password']),
        ]);

        $sucursalNombre = $sucursalActual ? " en {$sucursalActual->nombre}" : '';
        return redirect()
            ->route('configuracion.index', ['tab' => 'users'])
            ->with('status', "Usuario '{$data['name']}' registrado exitosamente con rol de " . ucfirst($data['role']) . "{$sucursalNombre}.");
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:administrador,encargado,vendedor'],
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->sucursal_id = $data['sucursal_id'] ?? null;

        // El usuario admin principal protegido siempre mantiene rol administrador y activo
        if ($this->isProtectedAdmin($user)) {
            $user->role = User::ROLE_ADMIN;
            $user->is_active = true;
        } else {
            $prevRole = $user->role;
            $user->role = $data['role'];
            $user->is_active = $request->boolean('is_active', true);

            // Si cambió de rol y no tenía permisos personalizados, actualizar plantilla de permisos
            if ($prevRole !== $data['role'] && empty($user->permissions)) {
                $user->permissions = User::getDefaultPermissionsForRole($data['role']);
            }
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()
            ->route('configuracion.index', ['tab' => 'users'])
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function updateUserPermissions(Request $request, User $user)
    {
        if ($user->isAdministrator()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'El rol Administrador tiene acceso total a todos los módulos y funciones por defecto.',
                ]);
            }
            return redirect()
                ->route('configuracion.index', ['tab' => 'users'])
                ->with('status', 'El Administrador tiene acceso total por defecto.');
        }

        $permissions = $request->input('permissions', []);
        if (!is_array($permissions)) {
            $permissions = [];
        }

        $user->permissions = array_values(array_unique($permissions));
        $user->save();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Permisos de '{$user->name}' actualizados correctamente (" . count($user->permissions) . " permisos activos).",
                'permissions' => $user->permissions,
            ]);
        }

        return redirect()
            ->route('configuracion.index', ['tab' => 'users'])
            ->with('status', "Permisos de '{$user->name}' actualizados correctamente.");
    }

    public function destroyUser(User $user): RedirectResponse
    {
        if ($this->isProtectedAdmin($user)) {
            return redirect()
                ->route('configuracion.index', ['tab' => 'users'])
                ->withErrors(['user' => 'El usuario administrador no se puede eliminar.']);
        }

        if (Auth::id() === $user->id) {
            return redirect()
                ->route('configuracion.index', ['tab' => 'users'])
                ->withErrors(['user' => 'No puedes eliminar tu propio usuario mientras tienes la sesión activa.']);
        }

        User::destroy($user->getKey());

        return redirect()
            ->route('configuracion.index', ['tab' => 'users'])
            ->with('status', 'Usuario eliminado correctamente.');
    }

    public function downloadUpdater()
    {
        $batContent = "@echo off\r\n"
            . "echo Buscando nuevas actualizaciones en GitHub...\r\n"
            . "cd C:\\laragon\\www\\intelliCarnic\r\n\r\n"
            . ":: 1. Descarga los archivos nuevos de codigo\r\n"
            . "git pull origin main\r\n\r\n"
            . "echo.\r\n"
            . "echo Actualizando la base de datos...\r\n"
            . ":: 2. Ejecuta las migraciones sin preguntarle al usuario (--force es clave)\r\n"
            . "php artisan migrate --force\r\n\r\n"
            . "echo.\r\n"
            . "echo Limpiando memoria cache...\r\n"
            . ":: 3. Limpia el cache de Laravel para que los cambios se vean de inmediato\r\n"
            . "php artisan optimize:clear\r\n\r\n"
            . "echo.\r\n"
            . "echo El sistema ha sido actualizado con exito! Ya puedes cerrar esta ventana.\r\n"
            . "pause\r\n";

        return response($batContent)
            ->header('Content-Type', 'application/bat')
            ->header('Content-Disposition', 'attachment; filename="Actualizar_IntelliCarnic.bat"');
    }

    public function updateGithub(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'github_url' => ['required', 'url'],
        ]);

        $url = trim($data['github_url']);
        Setting::setValue('github_url', $url);

        // Buscar el ejecutable de Git en el sistema o dentro de Laragon
        $git = $this->getGitBinary();

        if ($git) {
            $this->safeShellExec($git . ' init 2>&1');
            $this->safeShellExec($git . ' remote remove origin 2>&1');
            $this->safeShellExec($git . ' remote add origin ' . escapeshellarg($url) . ' 2>&1');
            $msg = 'Repositorio de GitHub vinculado y configurado correctamente.';
        } else {
            $msg = 'Se guardó la URL de GitHub. Nota: Git no está instalado o no se detectó en Laragon/Windows.';
        }

        return redirect()
            ->route('configuracion.index', ['tab' => 'sistema'])
            ->with('status', $msg);
    }

    public function actualizarSistema(): RedirectResponse
    {
        $git = $this->getGitBinary();

        if (!$git) {
            return redirect()
                ->to(route('configuracion.index', ['tab' => 'sistema'], false))
                ->withErrors(['actualizar' => 'No se detectó el ejecutable de Git en la PC servidor.']);
        }

        try {
            @putenv('GIT_TERMINAL_PROMPT=0');
            @putenv('GIT_ASKPASS=');
            $pullOutput = $this->safeShellExec($git . ' -c core.askPass= pull origin main 2>&1');

            // Migraciones — silenciar si SQLite no está disponible en CLI
            $migrateMsg = '';
            try {
                \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                $migrateMsg = ' Migraciones aplicadas.';
            } catch (\Throwable $e) {
                // PDO SQLite no disponible en CLI; ignorar — la BD no necesita cambios
                \Illuminate\Support\Facades\Log::warning('actualizarSistema: migrate omitido — ' . $e->getMessage());
            }

            \Illuminate\Support\Facades\Artisan::call('config:cache');
            \Illuminate\Support\Facades\Artisan::call('view:cache');
            \Illuminate\Support\Facades\Artisan::call('route:clear');

            // Guardar información del último commit en settings
            $lastCommit = trim($this->safeShellExec($git . ' log -1 --pretty=format:"%h|%s|%ar" 2>&1') ?? '');
            if ($lastCommit && !str_contains(strtolower($lastCommit), 'fatal')) {
                Setting::setValue('last_update_commit', $lastCommit);
                Setting::setValue('last_update_at', now()->toDateTimeString());
                Setting::setValue('last_update_method', 'web');
            }

            $msg = '¡Sistema actualizado y optimizado con éxito!' . $migrateMsg;
            if ($pullOutput) {
                $firstLine = explode("\n", trim($pullOutput))[0];
                $msg .= ' Git: ' . $firstLine;
            }
            return redirect()
                ->to(route('configuracion.index', ['tab' => 'sistema'], false))
                ->with('status', $msg);
        } catch (\Throwable $e) {
            return redirect()
                ->to(route('configuracion.index', ['tab' => 'sistema'], false))
                ->withErrors(['actualizar' => 'Error durante la actualización: ' . $e->getMessage()]);
        }
    }


    private function getGitBinary(): ?string
    {
        $test = $this->safeShellExec('git --version 2>&1');
        if ($test && str_contains(strtolower($test), 'git version')) {
            return 'git';
        }

        $paths = [
            'C:\laragon\bin\git\cmd\git.exe',
            'C:\laragon\bin\git\bin\git.exe',
            'C:\Program Files\Git\cmd\git.exe',
            'C:\Program Files\Git\bin\git.exe',
            'C:\Program Files (x86)\Git\cmd\git.exe',
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                return '"' . $path . '"';
            }
        }

        return null;
    }

    private function safeShellExec(string $command): ?string
    {
        if (!function_exists('shell_exec')) {
            return null;
        }

        $disabled = ini_get('disable_functions');
        if ($disabled) {
            $disabledArr = array_map('trim', explode(',', strtolower($disabled)));
            if (in_array('shell_exec', $disabledArr)) {
                return null;
            }
        }

        try {
            $result = @shell_exec($command);
            return $result !== false ? $result : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function isProtectedAdmin(User $user): bool
    {
        return strtolower($user->email) === self::PROTECTED_ADMIN_EMAIL;
    }

    // ─────────────────────────────────────────────────────────
    //  BASE DE DATOS (Respaldos y Restauración Segura)
    // ─────────────────────────────────────────────────────────

    /**
     * Generar un nuevo respaldo de la base de datos.
     */
    public function backupGenerar(Request $request)
    {
        $backupService = new \App\Services\DatabaseBackupService();
        $result = $backupService->createBackup();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()
                ->route('configuracion.index', ['tab' => 'base_datos'])
                ->with('status', "Copia de seguridad {$result['filename']} ({$result['size']}) creada exitosamente.");
        }

        return redirect()
            ->route('configuracion.index', ['tab' => 'base_datos'])
            ->withErrors(['backup' => 'No se pudo generar la copia de seguridad.']);
    }

    /**
     * Subir y almacenar un archivo de respaldo .sql en el servidor.
     */
    public function backupSubir(Request $request)
    {
        $request->validate([
            'archivo_sql' => ['required', 'file', 'max:51200'], // hasta 50MB
        ]);

        $file = $request->file('archivo_sql');
        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext !== 'sql') {
            return redirect()
                ->route('configuracion.index', ['tab' => 'base_datos'])
                ->withErrors(['upload' => 'El archivo debe tener extensión .sql']);
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $cleanName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $originalName);
        $filename = "import_{$cleanName}_" . date('Y-m-d_H-i-s') . ".sql";

        $file->move(storage_path('app/backups'), $filename);

        return redirect()
            ->route('configuracion.index', ['tab' => 'base_datos'])
            ->with('status', "Archivo de respaldo '{$filename}' subido y guardado exitosamente en la biblioteca de copias.");
    }

    /**
     * Descargar un archivo de respaldo específico.
     */
    public function backupDescargar(string $filename)
    {
        $backupService = new \App\Services\DatabaseBackupService();
        $path = $backupService->getBackupPath($filename);

        if (!$path) {
            abort(404, 'El archivo de respaldo solicitado no existe.');
        }

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * Eliminar un archivo de respaldo del servidor.
     */
    public function backupEliminar(Request $request, string $filename)
    {
        $backupService = new \App\Services\DatabaseBackupService();
        $deleted = $backupService->deleteBackup($filename);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => $deleted,
                'message' => $deleted ? "Respaldo {$filename} eliminado." : 'No se pudo eliminar el respaldo.',
            ]);
        }

        return redirect()
            ->route('configuracion.index', ['tab' => 'base_datos'])
            ->with('status', "Respaldo {$filename} eliminado.");
    }

    /**
     * Restaurar la base de datos desde un archivo SQL subido o existente con candado de seguridad.
     */
    public function databaseRestaurar(Request $request)
    {
        $request->validate([
            'confirmacion' => ['required', 'string'],
            'tipo_origen' => ['required', 'in:subir,existente'],
            'archivo_sql' => ['required_if:tipo_origen,subir', 'nullable', 'file', 'max:51200'], // hasta 50MB
            'backup_existente' => ['required_if:tipo_origen,existente', 'nullable', 'string'],
        ]);

        // Validación estricta de seguridad: Palabra de confirmación
        if (trim(strtoupper($request->input('confirmacion'))) !== 'RESTAURAR') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Código de confirmación incorrecto. Debe escribir exactamente la palabra RESTAURAR.',
                ], 422);
            }
            return redirect()
                ->route('configuracion.index', ['tab' => 'base_datos'])
                ->withErrors(['confirmacion' => 'Debe escribir exactamente RESTAURAR para autorizar la operación.']);
        }

        $backupService = new \App\Services\DatabaseBackupService();

        if ($request->input('tipo_origen') === 'subir') {
            if (!$request->hasFile('archivo_sql')) {
                return response()->json(['success' => false, 'error' => 'No se seleccionó ningún archivo SQL.'], 422);
            }
            $result = $backupService->restoreFromUploadedFile($request->file('archivo_sql'));
        } else {
            $filename = $request->input('backup_existente');
            $path = $backupService->getBackupPath($filename);
            if (!$path) {
                return response()->json(['success' => false, 'error' => 'El archivo de respaldo seleccionado no existe.'], 404);
            }
            $content = file_get_contents($path);
            $result = $backupService->restoreFromSql($content);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()
                ->route('configuracion.index', ['tab' => 'base_datos'])
                ->with('status', $result['message']);
        }

        return redirect()
            ->route('configuracion.index', ['tab' => 'base_datos'])
            ->withErrors(['restore' => $result['error'] ?? 'Error al restaurar la base de datos.']);
    }

    /**
     * Resetear la base de datos a 0 (Limpiar catálogo y operaciones) con candado de seguridad 'RESETEAR'.
     */
    public function databaseResetear(Request $request)
    {
        $request->validate([
            'confirmacion' => ['required', 'string'],
        ]);

        // Validación estricta de seguridad: Palabra de confirmación
        if (trim(strtoupper($request->input('confirmacion'))) !== 'RESETEAR') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Código de confirmación incorrecto. Debe escribir exactamente la palabra RESETEAR.',
                ], 422);
            }
            return redirect()
                ->route('configuracion.index', ['tab' => 'base_datos'])
                ->withErrors(['confirmacion' => 'Debe escribir exactamente RESETEAR para autorizar el reseteo.']);
        }

        $backupService = new \App\Services\DatabaseBackupService();
        $result = $backupService->resetDatabaseToZero();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()
                ->route('configuracion.index', ['tab' => 'base_datos'])
                ->with('status', $result['message']);
        }

        return redirect()
            ->route('configuracion.index', ['tab' => 'base_datos'])
            ->withErrors(['reset' => $result['error'] ?? 'Error al resetear la base de datos.']);
    }

    /**
     * Guardar configuración de programación y recordatorios de respaldos automáticos.
     */
    public function updateBackupAutoConfig(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'backup_auto_enabled' => ['nullable', 'boolean'],
            'backup_frecuencia' => ['required', 'in:semanal,quincenal,mensual,cierre_caja,diario,cada_3_dias'],
            'backup_recordatorio_dias' => ['required', 'integer', 'min:1', 'max:60'],
            'backup_retencion_dias' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        Setting::setValue('backup_auto_enabled', $request->boolean('backup_auto_enabled') ? '1' : '0');
        Setting::setValue('backup_frecuencia', $data['backup_frecuencia']);
        Setting::setValue('backup_recordatorio_dias', $data['backup_recordatorio_dias']);
        Setting::setValue('backup_retencion_dias', $data['backup_retencion_dias']);

        return redirect()
            ->route('configuracion.index', ['tab' => 'base_datos'])
            ->with('status', 'Configuración de respaldos automáticos y recordatorios guardada correctamente.');
    }
}