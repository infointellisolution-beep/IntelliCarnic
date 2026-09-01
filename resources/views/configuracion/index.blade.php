@extends('layouts.app')

@section('title', 'Configuración')

@section('header-actions')
    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
        @csrf
        <button type="submit" class="btn-modern btn-secondary" style="width: auto; display: inline-flex; align-items: center; justify-content: center;">
            <i class="fa-solid fa-right-from-bracket"></i> Salir
        </button>
    </form>
@endsection

@section('content')
@php
    $activeTab = request()->query('tab', 'general');
@endphp

<section class="page-hero">
    <div class="hero-top">
        <div>
            <div class="hero-kicker"><i class="fa-solid fa-gear"></i> Configuración del negocio</div>
            <p class="hero-copy">Ajusta la unidad de peso, el comportamiento del IVA y administra usuarios con credenciales reales.</p>
        </div>
    </div>
</section>

@if(session('status'))
    <div class="card" style="margin-bottom: 1rem; background: #ecfdf5; border-color: #bbf7d0; color: #166534;">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="card" style="margin-bottom: 1rem; border-color: rgba(239, 68, 68, 0.35); background: #fef2f2; color: #7f1d1d;">
        <strong>Revisa los datos.</strong>
        <ul style="margin: 0.5rem 0 0; padding-left: 1.2rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="config-tabs">
    <a href="{{ route('configuracion.index', ['tab' => 'general']) }}" class="config-tab {{ $activeTab === 'general' ? 'is-active' : '' }}">General</a>
    <a href="{{ route('configuracion.index', ['tab' => 'empresa']) }}" class="config-tab {{ $activeTab === 'empresa' ? 'is-active' : '' }}">Mi Empresa</a>
    <a href="{{ route('configuracion.index', ['tab' => 'users']) }}" class="config-tab {{ $activeTab === 'users' ? 'is-active' : '' }}">Usuarios</a>
    <a href="{{ route('configuracion.index', ['tab' => 'sucursales']) }}" class="config-tab {{ $activeTab === 'sucursales' ? 'is-active' : '' }}"><i class="fa-solid fa-building"></i> Sucursales y Nube</a>
    <a href="{{ route('configuracion.index', ['tab' => 'sistema']) }}" class="config-tab {{ $activeTab === 'sistema' ? 'is-active' : '' }}">Sistema y Red</a>
    <a href="{{ route('configuracion.index', ['tab' => 'base_datos']) }}" class="config-tab {{ $activeTab === 'base_datos' ? 'is-active' : '' }}"><i class="fa-solid fa-database"></i> Base de Datos</a>
</div>

@if($activeTab === 'general')
    <div class="config-grid">
        <form class="card" action="{{ route('configuracion.general.update') }}" method="POST">
            @csrf
            <div class="hero-kicker"><i class="fa-solid fa-sliders"></i> General</div>
            <h2 class="hero-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Parámetros del negocio</h2>

            <div class="form-grid" style="margin-top: 1.25rem;">
                <div class="input-group" style="grid-column: span 2;">
                    <label style="font-weight: 700; color: var(--primary);"><i class="fa-solid fa-boxes-packing"></i> Modo de Control de Inventario</label>
                    <select class="input-modern" name="modo_inventario" required style="font-weight: 600;">
                        <option value="dinamico" @selected(($settings['modo_inventario'] ?? 'dinamico') === 'dinamico')>
                            📦 Modo Dinámico (Por Lotes, Series y Vencimientos - FEFO / PEPS)
                        </option>
                        <option value="simple" @selected(($settings['modo_inventario'] ?? 'dinamico') === 'simple')>
                            ⚡ Modo Simple (Stock General Acumulado - Sin Desglose de Lotes)
                        </option>
                    </select>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem;">
                        <strong>Dinámico:</strong> Guarda cada compra por lotes/series y los desgloza en el producto.<br>
                        <strong>Simple:</strong> Suma directamente todas las compras al stock general del producto sin pedir ni desglosar lotes en pantalla.
                    </div>
                </div>

                <div class="input-group">
                    <label>Unidad de peso</label>
                    <select class="input-modern" name="unidad_peso" required>
                        <option value="kg" @selected(($settings['unidad_peso'] ?? 'kg') === 'kg')>Kilogramos</option>
                        <option value="lb" @selected(($settings['unidad_peso'] ?? 'kg') === 'lb')>Libras</option>
                    </select>
                </div>
                <div class="input-group" style="display: flex; align-items: center; gap: 0.65rem; padding-top: 1.8rem;">
                    <input type="checkbox" id="usar_impuestos" name="usar_impuestos" value="1" @checked((int) ($settings['usar_impuestos'] ?? 1) === 1)>
                    <label for="usar_impuestos" style="margin: 0; color: var(--text-main);">Usar impuestos (IVA) en el sistema</label>
                </div>
                <div class="input-group" style="display: flex; align-items: center; gap: 0.65rem; padding-top: 1.8rem;">
                    <input type="checkbox" id="iva_global_enabled" name="iva_global_enabled" value="1" @checked((int) ($settings['iva_global_enabled'] ?? 1) === 1)>
                    <label for="iva_global_enabled" style="margin: 0; color: var(--text-main);">Aplicar IVA global al catálogo</label>
                </div>
                <div class="input-group">
                    <label>IVA global (%)</label>
                    <input type="number" step="0.01" min="0" max="100" class="input-modern" name="iva_global_rate" value="{{ $settings['iva_global_rate'] ?? 21 }}" required>
                </div>
                <div class="input-group">
                    <label><i class="fa-solid fa-clock"></i> Zona Horaria del Sistema</label>
                    <select class="input-modern" name="timezone" required>
                        @php
                            $tzCurrent = $settings['timezone'] ?? 'America/Managua';
                            $timezones = [
                                'America/Managua' => 'Nicaragua / Centroamérica (GMT-6)',
                                'America/El_Salvador' => 'El Salvador (GMT-6)',
                                'America/Guatemala' => 'Guatemala (GMT-6)',
                                'America/Tegucigalpa' => 'Honduras (GMT-6)',
                                'America/Costa_Rica' => 'Costa Rica (GMT-6)',
                                'America/Mexico_City' => 'México / CDMX (GMT-6)',
                                'America/Panama' => 'Panamá (GMT-5)',
                                'America/Bogota' => 'Colombia (GMT-5)',
                                'America/Lima' => 'Perú (GMT-5)',
                                'America/Santiago' => 'Chile (GMT-3/4)',
                                'America/Buenos_Aires' => 'Argentina (GMT-3)',
                                'America/New_York' => 'EE.UU. Este / EST (GMT-5)',
                                'UTC' => 'UTC / Tiempo Universal',
                            ];
                        @endphp
                        @foreach($timezones as $tzKey => $tzLabel)
                            <option value="{{ $tzKey }}" @selected($tzCurrent === $tzKey)>{{ $tzLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                <button type="submit" class="btn-modern btn-accent" style="width: auto;">Guardar configuración</button>
            </div>
        </form>
    </div>
@elseif($activeTab === 'empresa')
    <div class="config-grid">
        <form class="card" action="{{ route('configuracion.empresa.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="hero-kicker"><i class="fa-solid fa-building"></i> Mi Empresa</div>
            <h2 class="hero-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Información de la empresa</h2>

            <div class="form-grid" style="margin-top: 1.25rem;">
                <div class="input-group">
                    <label>Nombre de la empresa</label>
                    <input type="text" class="input-modern" name="empresa_nombre" value="{{ $settings['empresa_nombre'] ?? '' }}" maxlength="255">
                </div>
                <div class="input-group">
                    <label>Dirección</label>
                    <textarea class="input-modern" name="empresa_direccion" rows="2" maxlength="500">{{ $settings['empresa_direccion'] ?? '' }}</textarea>
                </div>
                <div class="input-group">
                    <label>Correo electrónico</label>
                    <input type="email" class="input-modern" name="empresa_correo" value="{{ $settings['empresa_correo'] ?? '' }}" maxlength="255">
                </div>
                <div class="input-group">
                    <label>Celular</label>
                    <input type="text" class="input-modern" name="empresa_celular" value="{{ $settings['empresa_celular'] ?? '' }}" maxlength="50">
                </div>
                <div class="input-group">
                    <label>Logotipo</label>
                    <input type="file" class="input-modern" name="empresa_logo" accept="image/jpeg,image/png,image/webp" style="padding: 0.5rem;">
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">PNG, JPG o WebP. Máximo 2 MB.</div>
                    @if(! empty($settings['empresa_logo']))
                        <div style="margin-top: 0.75rem;">
                            <img src="{{ asset('storage/'.$settings['empresa_logo']) }}" alt="Logotipo" class="empresa-logo-preview">
                            <label style="display: inline-flex; align-items: center; gap: 0.4rem; margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-muted);">
                                <input type="checkbox" name="empresa_logo_remove" value="1"> Eliminar logotipo actual
                            </label>
                        </div>
                    @endif
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                <button type="submit" class="btn-modern btn-accent" style="width: auto;">Guardar información</button>
            </div>
        </form>
    </div>
@elseif($activeTab === 'sistema')
    @php
        $safeExec = function($cmd) {
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
                $res = @shell_exec($cmd);
                return $res !== false ? $res : null;
            } catch (\Throwable $e) {
                return null;
            }
        };

        $localIP = '127.0.0.1';
        $output = $safeExec('ipconfig');
        if ($output) {
            preg_match_all('/IPv4.*?:\s*([\d\.]+)/i', $output, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $ip) {
                    // Ignorar 127.0.0.1 y IPs terminadas en .1 (puertas de enlace / adaptadores virtuales)
                    if ($ip !== '127.0.0.1' && !str_ends_with($ip, '.1')) {
                        $localIP = $ip;
                        break;
                    }
                }
                if ($localIP === '127.0.0.1') {
                    foreach ($matches[1] as $ip) {
                        if ($ip !== '127.0.0.1') {
                            $localIP = $ip;
                        }
                    }
                }
            }
        }

        if ($localIP === '127.0.0.1' && function_exists('getHostName') && function_exists('getHostByName')) {
            try {
                $localIP = getHostByName(getHostName());
            } catch (\Throwable $e) {}
        }

        // Detectar Repositorio GitHub vinculado
        $gitCmd = null;
        $testCmd = $safeExec('git --version 2>&1');
        if ($testCmd && str_contains(strtolower($testCmd), 'git version')) {
            $gitCmd = 'git';
        } else {
            $possiblePaths = [
                'C:\laragon\bin\git\cmd\git.exe',
                'C:\laragon\bin\git\bin\git.exe',
                'C:\Program Files\Git\cmd\git.exe',
                'C:\Program Files\Git\bin\git.exe',
                'C:\Program Files (x86)\Git\cmd\git.exe',
            ];
            foreach ($possiblePaths as $p) {
                if (file_exists($p)) {
                    $gitCmd = '"' . $p . '"';
                    break;
                }
            }
        }

        $gitRepoUrl = 'No detectado / No vinculado';
        $gitBranch = 'main';
        if ($gitCmd) {
            try {
                $remote = $safeExec($gitCmd . ' remote get-url origin 2>&1');
                if ($remote && !str_contains($remote, 'fatal') && !str_contains(strtolower($remote), 'no se reconoce')) {
                    $gitRepoUrl = trim($remote);
                }
                $branch = $safeExec($gitCmd . ' branch --show-current 2>&1');
                if ($branch && !str_contains($branch, 'fatal') && !str_contains(strtolower($branch), 'no se reconoce')) {
                    $gitBranch = trim($branch);
                }
            } catch (\Throwable $e) {}
        }
        
        if ($gitRepoUrl === 'No detectado / No vinculado' && !empty($settings['github_url'])) {
            $gitRepoUrl = $settings['github_url'];
        }

        $lanUrl = 'http://' . $localIP . '/intelliCarnic/public';
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($lanUrl);
    @endphp
    <div class="config-grid">
        <div class="card">
            <div class="hero-kicker"><i class="fa-solid fa-network-wired"></i> Red Local (LAN)</div>
            <h2 class="hero-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Acceso desde otros dispositivos</h2>
            <p style="color: var(--text-muted); margin-top: 0.5rem; font-size: 0.95rem;">
                Escanea este código QR directamente desde el dispositivo Zebra TC51 o desde el navegador de cualquier teléfono conectado al mismo Wi-Fi para entrar al sistema sin necesidad de escribir la dirección manualmente.
            </p>
            
            <div style="background: var(--surface-bg); border: 1px solid var(--border-color); padding: 1.5rem; border-radius: var(--radius-md); margin-top: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 2rem;">
                
                <img src="{{ $qrUrl }}" alt="Código QR de Acceso" style="border-radius: 8px; border: 4px solid white; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                
                <div style="text-align: left;">
                    <div style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 1px; margin-bottom: 0.25rem;">También puedes teclear esto:</div>
                    <a href="{{ $lanUrl }}" target="_blank" style="font-size: 1.6rem; font-weight: 700; color: var(--accent); text-decoration: none; font-family: monospace;">
                        {{ $lanUrl }}
                    </a>
                </div>

            </div>
        </div>

        <div class="card">
            <div class="hero-kicker"><i class="fa-brands fa-github"></i> Mantenimiento y Actualizaciones</div>
            <h2 class="hero-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Conexión a GitHub</h2>
            <p style="color: var(--text-muted); margin-top: 0.5rem; font-size: 0.95rem;">
                Ingresa la dirección URL de tu repositorio público o privado en GitHub para vincular este sistema local y permitir actualizaciones con 1 solo clic.
            </p>

            <form action="{{ route('configuracion.sistema.github') }}" method="POST" style="margin-top: 1rem;">
                @csrf
                <div class="input-group">
                    <label>URL del Repositorio en GitHub</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="url" class="input-modern" name="github_url" value="{{ $gitRepoUrl !== 'No detectado / No vinculado' ? $gitRepoUrl : ($settings['github_url'] ?? '') }}" placeholder="https://github.com/usuario/repositorio.git" required>
                        <button type="submit" class="btn-modern btn-secondary" style="width: auto; whitespace: nowrap;">Vincular</button>
                    </div>
                </div>
            </form>

            <div style="background: var(--surface-bg); border: 1px solid var(--border-color); padding: 1rem 1.25rem; border-radius: var(--radius-md); margin-top: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Estado de vinculación:</span>
                    <span class="badge {{ $gitRepoUrl !== 'No detectado / No vinculado' ? 'badge-success' : 'badge-warning' }}">
                        {{ $gitRepoUrl !== 'No detectado / No vinculado' ? 'Vinculado' : 'Pendiente' }}
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">Rama activa:</span>
                    <span style="font-weight: 600; font-family: monospace; font-size: 0.9rem; color: var(--accent);">{{ $gitBranch }}</span>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <form action="{{ route('configuracion.sistema.actualizar') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-modern btn-primary" style="width: auto; display: inline-flex; align-items: center; gap: 0.5rem;" onclick="return confirm('¿Deseas buscar e instalar la última versión de IntelliCarnic desde GitHub ahora?')">
                        <i class="fa-solid fa-arrows-rotate"></i> Buscar e Instalar Actualización Ahora
                    </button>
                </form>

                <a href="{{ route('configuracion.sistema.downloadUpdater') }}" class="btn-modern btn-secondary" style="width: auto; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-download"></i> Descargar Script (.bat)
                </a>
            </div>
        </div>
    </div>
@elseif($activeTab === 'users')
    @php
        $totalUsers = $users->count();
        $adminCount = $users->filter(fn($u) => $u->isAdministrator())->count();
        $encargadoCount = $users->filter(fn($u) => $u->role === 'encargado')->count();
        $vendedorCount = $users->filter(fn($u) => $u->role === 'vendedor' || empty($u->role))->count();
    @endphp

    {{-- Métricas de Usuarios y Roles --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; background: #ffffff;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total Usuarios</div>
                <div style="font-size: 1.3rem; font-weight: 800; color: var(--text-main);">{{ $totalUsers }} cuentas</div>
            </div>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; background: #ffffff;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #f3e8ff; color: #7e22ce; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-crown"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Administradores</div>
                <div style="font-size: 1.3rem; font-weight: 800; color: #7e22ce;">{{ $adminCount }} (Acceso Total)</div>
            </div>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; background: #ffffff;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Encargados</div>
                <div style="font-size: 1.3rem; font-weight: 800; color: #0284c7;">{{ $encargadoCount }} cuentas</div>
            </div>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; background: #ffffff;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-user-tag"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Vendedores</div>
                <div style="font-size: 1.3rem; font-weight: 800; color: #16a34a;">{{ $vendedorCount }} cuentas</div>
            </div>
        </div>
    </div>

    <div class="config-grid config-grid-users" style="gap: 1.5rem;">
        {{-- Card: Formulario de Creación de Usuario --}}
        <form class="card" action="{{ route('configuracion.users.store') }}" method="POST">
            @csrf
            <div class="hero-kicker"><i class="fa-solid fa-user-plus"></i> Gestión de Cuentas</div>
            <h2 class="hero-title" style="font-size: 1.35rem; margin-top: 0.25rem;">Crear Nuevo Usuario</h2>

            <div class="form-grid" style="margin-top: 1.25rem;">
                <div class="input-group">
                    <label style="font-weight: 700; font-size: 0.88rem;">Nombre Completo</label>
                    <input type="text" class="input-modern" name="name" placeholder="Ej: Juan Pérez" required>
                </div>
                <div class="input-group">
                    <label style="font-weight: 700; font-size: 0.88rem;">Correo Electrónico</label>
                    <input type="email" class="input-modern" name="email" placeholder="juan@gmail.com" required>
                </div>
                <div class="input-group">
                    <label style="font-weight: 700; font-size: 0.88rem;"><i class="fa-solid fa-shield-halved" style="color: var(--primary);"></i> Rol de Usuario</label>
                    <select name="role" class="input-modern" required style="font-weight: 600;">
                        <option value="vendedor">🏷️ Vendedor / Cajero (Operativo)</option>
                        <option value="encargado">👔 Encargado / Supervisor</option>
                        <option value="administrador">👑 Administrador (Acceso Total)</option>
                    </select>
                </div>
                <div class="input-group">
                    <label style="font-weight: 700; font-size: 0.88rem;"><i class="fa-solid fa-building" style="color: var(--accent);"></i> Sucursal de Creación</label>
                    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.65rem 0.85rem; font-size: 0.88rem; font-weight: 700; color: var(--text-main); display: flex; align-items: center; justify-content: space-between; gap: 0.5rem;">
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-location-dot" style="color: #0284c7;"></i>
                            {{ $sucursalActual ? $sucursalActual->nombre : 'Sucursal Matriz Centro' }}
                        </span>
                        <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 800;">
                            {{ $sucursalActual ? $sucursalActual->codigo : 'LOCAL' }}
                        </span>
                    </div>
                    <input type="hidden" name="sucursal_id" value="{{ $sucursalActual ? $sucursalActual->id : '' }}">
                    <small style="color: var(--text-muted); font-size: 0.75rem; margin-top: 2px;">El usuario se registrará exclusivamente para operar en esta sucursal.</small>
                </div>
                <div class="input-group">
                    <label style="font-weight: 700; font-size: 0.88rem;">Contraseña</label>
                    <input type="password" class="input-modern" name="password" placeholder="Mínimo 8 caracteres" required>
                </div>
                <div class="input-group">
                    <label style="font-weight: 700; font-size: 0.88rem;">Confirmar Contraseña</label>
                    <input type="password" class="input-modern" name="password_confirmation" placeholder="Repite la contraseña" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 1.25rem;">
                <button type="submit" class="btn-modern btn-accent" style="width: auto;">
                    <i class="fa-solid fa-user-plus"></i> Guardar Nuevo Usuario
                </button>
            </div>
        </form>

        {{-- Card: Lista de Usuarios Registrados --}}
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <div class="hero-kicker"><i class="fa-solid fa-users"></i> Directorio</div>
                    <h2 class="hero-title" style="font-size: 1.35rem; margin-top: 0.25rem;">Usuarios y Permisos Activos</h2>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                @forelse($users as $user)
                    @php
                        $isProtectedAdmin = strtolower($user->email) === 'admin@gmail.com';
                        $badge = $user->getRoleBadgeStyle();
                        $effectivePermsCount = count($user->getEffectivePermissions());
                        $allPermsCount = 0;
                        foreach($allModules as $mod) { $allPermsCount += count($mod['permissions']); }
                    @endphp
                    <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.1rem; background: var(--surface-bg); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; transition: transform 0.15s ease, box-shadow 0.15s ease;">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            {{-- Avatar --}}
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; border: 2px solid {{ $badge['border'] }}; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.15rem;">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <strong style="font-size: 1.05rem; color: var(--text-main);">{{ $user->name }}</strong>
                                    <span style="background: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; border: 1px solid {{ $badge['border'] }}; padding: 2px 8px; border-radius: 12px; font-size: 0.76rem; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="{{ $badge['icon'] }}"></i> {{ $user->getRoleName() }}
                                    </span>
                                    @if(!$user->is_active)
                                        <span style="background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 12px; font-size: 0.72rem; font-weight: 700;">Inactivo</span>
                                    @endif
                                </div>
                                <div style="font-size: 0.83rem; color: var(--text-muted); margin-top: 0.2rem;">
                                    <i class="fa-solid fa-envelope" style="margin-right: 4px;"></i> {{ $user->email }}
                                    @if($user->sucursal)
                                        <span style="margin-left: 8px; color: var(--accent); font-weight: 600;">
                                            <i class="fa-solid fa-building"></i> {{ $user->sucursal->nombre }} ({{ $user->sucursal->codigo }})
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size: 0.78rem; color: #64748b; margin-top: 0.25rem;">
                                    <i class="fa-solid fa-shield-halved" style="color: var(--primary);"></i>
                                    @if($user->isAdministrator())
                                        <strong>Acceso Total ({{ $allPermsCount }}/{{ $allPermsCount }} permisos)</strong>
                                    @else
                                        <span>{{ $effectivePermsCount }} de {{ $allPermsCount }} permisos habilitados</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Botones de Acción --}}
                        <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <button type="button" onclick="abrirModalDetalleUsuario({{ json_encode($user) }}, {{ $user->sucursal ? json_encode($user->sucursal) : 'null' }}, {{ json_encode($user->getEffectivePermissions()) }}, {{ $user->isAdministrator() ? 'true' : 'false' }})" class="btn-modern btn-secondary" style="font-size: 0.82rem; padding: 6px 12px; color: #0284c7; border-color: #bae6fd;" title="Ver información completa y perfil del usuario">
                                <i class="fa-solid fa-eye"></i> Detalle
                            </button>

                            @if(!$user->isAdministrator())
                                <button type="button" onclick="abrirModalPermisos({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->role }}', {{ json_encode($user->getEffectivePermissions()) }}, false)" class="btn-modern btn-secondary" style="font-size: 0.82rem; padding: 6px 12px; color: #2563eb; border-color: #93c5fd;" title="Gestionar permisos por módulo">
                                    <i class="fa-solid fa-shield-halved"></i> Permisos
                                </button>
                            @else
                                <span style="font-size: 0.78rem; font-weight: 700; color: #7e22ce; background: #f3e8ff; border: 1px solid #d8b4fe; padding: 5px 10px; border-radius: 8px; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-crown"></i> Acceso Total
                                </span>
                            @endif

                            <button type="button" onclick="abrirModalEditarUsuario({{ json_encode($user) }})" class="btn-modern btn-secondary" style="font-size: 0.82rem; padding: 6px 12px;" title="Editar datos del usuario">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </button>

                            @if(! $isProtectedAdmin)
                                <form action="{{ route('configuracion.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Eliminar permanentemente la cuenta de {{ $user->name }}?');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-modern" style="font-size: 0.82rem; padding: 6px 10px; background: #fee2e2; color: #991b1b;" title="Eliminar usuario">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="stock-empty">No hay usuarios registrados.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- MODAL DE MATRIZ DE PERMISOS (Estilo Panel Modular con Búsqueda y Selección) --}}
    <div id="modalMatrizPermisos" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 95%; max-width: 1280px; max-height: 92vh; margin: 1rem; display: flex; flex-direction: column; padding: 0; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
            
            {{-- Encabezado del Modal --}}
            <div style="padding: 1.25rem 1.5rem; background: #0f172a; color: #ffffff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid #334155;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: #1e293b; color: #38bdf8; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <h3 style="margin: 0; font-size: 1.3rem; color: #ffffff;" id="permisosModalUserName">Gestión de Permisos</h3>
                            <span id="permisosModalUserRoleBadge" style="background: #334155; color: #94a3b8; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700;"></span>
                        </div>
                        <span style="font-size: 0.82rem; color: #94a3b8;">Asigna y personaliza las funciones a las que este usuario tiene acceso por módulo.</span>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <button type="button" onclick="guardarPermisosModal()" id="btnGuardarPermisosTop" class="btn-modern btn-accent" style="width: auto; background: #0284c7; border-color: #0284c7; font-weight: 700; padding: 8px 18px;">
                        <i class="fa-solid fa-floppy-disk"></i> ACTUALIZAR PERMISOS
                    </button>
                    <button type="button" onclick="cerrarModalPermisos()" style="background: none; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer; padding: 4px 8px; line-height: 1;">&times;</button>
                </div>
            </div>

            {{-- Barra de Herramientas y Plantillas Rápidas --}}
            <div style="background: #f8fafc; padding: 0.75rem 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                    <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">Plantillas Rápidas:</span>
                    <button type="button" onclick="aplicarPlantillaPermisos('vendedor')" class="btn-modern" style="font-size: 0.76rem; padding: 5px 12px; background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; font-weight: 700;">
                        <i class="fa-solid fa-user-tag"></i> Perfil Vendedor
                    </button>
                    <button type="button" onclick="aplicarPlantillaPermisos('encargado')" class="btn-modern" style="font-size: 0.76rem; padding: 5px 12px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-weight: 700;">
                        <i class="fa-solid fa-user-tie"></i> Perfil Encargado
                    </button>
                    <button type="button" onclick="marcarTodosLosPermisos(true)" class="btn-modern" style="font-size: 0.76rem; padding: 5px 12px; background: #ffffff; color: var(--text-main); border: 1px solid var(--border-color); font-weight: 600;">
                        <i class="fa-solid fa-square-check" style="color: var(--primary);"></i> Marcar Todo
                    </button>
                    <button type="button" onclick="marcarTodosLosPermisos(false)" class="btn-modern" style="font-size: 0.76rem; padding: 5px 12px; background: #ffffff; color: var(--text-main); border: 1px solid var(--border-color); font-weight: 600;">
                        <i class="fa-solid fa-square" style="color: var(--text-muted);"></i> Desmarcar Todo
                    </button>
                </div>
                <div id="permisosContadorSeleccionados" style="font-size: 0.85rem; color: #0284c7; font-weight: 800;">
                    0 permisos seleccionados
                </div>
            </div>

            {{-- Aviso si es Administrador --}}
            <div id="permisosAdminNotice" style="display: none; background: #fef3c7; color: #92400e; padding: 0.85rem 1.5rem; font-size: 0.88rem; border-bottom: 1px solid #fde68a;">
                <i class="fa-solid fa-crown" style="margin-right: 6px;"></i>
                <strong>Usuario Administrador:</strong> Este usuario posee acceso total a todos los módulos y opciones por defecto. No requiere configuración manual de permisos.
            </div>

            {{-- CUADRÍCULA MODULAR DE PERMISOS (Cuerpo Blanco / Claro) --}}
            <div style="flex: 1; overflow-y: auto; padding: 1.25rem 1.5rem; background: #f1f5f9;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;" id="gridModulosPermisos">
                    @foreach($allModules as $modKey => $modulo)
                    <div class="modulo-permiso-card" data-module="{{ $modKey }}" style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column;">
                        {{-- Cabecera del Módulo --}}
                        <div style="background: #f8fafc; padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                            <label style="color: var(--text-main); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; display: flex; align-items: center; gap: 0.5rem; cursor: pointer; margin: 0;">
                                <input type="checkbox" class="modulo-master-check" data-module="{{ $modKey }}" onchange="toggleModuloPermisos('{{ $modKey }}', this.checked)" style="accent-color: #0284c7; width: 16px; height: 16px;">
                                <span><i class="{{ $modulo['icon'] }}" style="color: #0284c7; margin-right: 2px;"></i> {{ $modulo['name'] }}</span>
                            </label>
                            <span class="modulo-count-badge" id="count_{{ $modKey }}" style="background: #e2e8f0; color: #334155; font-size: 0.72rem; padding: 1px 6px; border-radius: 8px; font-weight: 700;">
                                0/{{ count($modulo['permissions']) }}
                            </span>
                        </div>

                        {{-- Input Buscador en vivo para esta columna --}}
                        <div style="padding: 0.5rem 0.75rem; background: #ffffff; border-bottom: 1px solid #f1f5f9;">
                            <div style="position: relative;">
                                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 8px; top: 8px; color: #94a3b8; font-size: 0.75rem;"></i>
                                <input type="text" placeholder="BUSCAR" oninput="filtrarPermisosColumna('{{ $modKey }}', this.value)" style="width: 100%; background: #f8fafc; border: 1px solid var(--border-color); border-radius: 4px; padding: 4px 8px 4px 26px; color: var(--text-main); font-size: 0.78rem; outline: none;">
                            </div>
                        </div>

                        {{-- Lista de Permisos del Módulo --}}
                        <div class="permisos-lista-scroll" id="lista_{{ $modKey }}" style="padding: 0.75rem 1rem; display: flex; flex-direction: column; gap: 0.6rem; max-height: 280px; overflow-y: auto; background: #ffffff;">
                            @foreach($modulo['permissions'] as $permKey => $permName)
                            <label class="permiso-item-row" data-perm-name="{{ strtolower($permName) }}" data-perm-key="{{ $permKey }}" style="display: flex; align-items: flex-start; gap: 0.5rem; color: #334155; font-size: 0.82rem; cursor: pointer; user-select: none; line-height: 1.35; margin: 0; padding: 2px 0;">
                                <input type="checkbox" name="permissions[]" value="{{ $permKey }}" class="permiso-checkbox perm-mod-{{ $modKey }}" onchange="actualizarContadoresPermisos()" style="accent-color: #0284c7; width: 15px; height: 15px; margin-top: 2px;">
                                <span>{{ $permName }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Pie del Modal --}}
            <div style="padding: 1rem 1.5rem; background: #ffffff; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div style="font-size: 0.82rem; color: var(--text-muted);">
                    Los cambios surtirán efecto de inmediato en las próximas acciones y navegación del usuario.
                </div>
                <div style="display: flex; gap: 0.75rem;">
                    <button type="button" onclick="cerrarModalPermisos()" class="btn-modern btn-secondary" style="width: auto;">
                        Cancelar
                    </button>
                    <button type="button" onclick="guardarPermisosModal()" id="btnGuardarPermisosBottom" class="btn-modern btn-accent" style="width: auto; background: #0284c7; border-color: #0284c7; font-weight: 700;">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Permisos
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE DETALLE COMPLETO DEL USUARIO --}}
    <div id="modalDetalleUsuario" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 760px; max-height: 90vh; margin: 1rem; display: flex; flex-direction: column; padding: 0; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
            
            {{-- Encabezado del Detalle --}}
            <div style="padding: 1.25rem 1.5rem; background: #0f172a; color: #ffffff; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div id="detalleUserAvatar" style="width: 48px; height: 48px; border-radius: 50%; background: #1e293b; color: #38bdf8; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; font-weight: 800; border: 2px solid #38bdf8;">
                        US
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <h3 id="detalleUserName" style="margin: 0; font-size: 1.3rem; color: #ffffff;">Usuario</h3>
                            <span id="detalleUserRoleBadge" style="padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700;"></span>
                        </div>
                        <span id="detalleUserEmail" style="font-size: 0.85rem; color: #94a3b8;">email@example.com</span>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalDetalleUsuario()" style="background: none; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer; padding: 4px 8px; line-height: 1;">&times;</button>
            </div>

            {{-- Cuerpo del Detalle --}}
            <div style="flex: 1; overflow-y: auto; padding: 1.5rem; background: var(--surface-bg); display: flex; flex-direction: column; gap: 1.25rem;">
                
                {{-- Tarjeta de Información General --}}
                <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                    <div style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.85rem; display: flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-id-card" style="color: var(--primary);"></i> Información de la Cuenta
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); display: block;">Sucursal Asignada:</span>
                            <strong id="detalleUserSucursal" style="font-size: 0.95rem; color: var(--text-main);">Principal</strong>
                        </div>
                        <div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); display: block;">Estado de la Cuenta:</span>
                            <span id="detalleUserEstado" style="font-size: 0.9rem; font-weight: 700;">Activo</span>
                        </div>
                        <div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); display: block;">Fecha de Registro:</span>
                            <strong id="detalleUserCreatedAt" style="font-size: 0.9rem; color: var(--text-main);">-</strong>
                        </div>
                        <div>
                            <span style="font-size: 0.78rem; color: var(--text-muted); display: block;">Nivel de Acceso:</span>
                            <strong id="detalleUserAccessLevel" style="font-size: 0.9rem; color: var(--text-main);">-</strong>
                        </div>
                    </div>
                </div>

                {{-- Tarjeta de Resumen de Permisos --}}
                <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
                        <div style="font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: var(--text-muted); display: flex; align-items: center; gap: 6px;">
                            <i class="fa-solid fa-shield-halved" style="color: var(--accent);"></i> Permisos y Módulos Habilitados
                        </div>
                        <span id="detallePermsCountBadge" style="font-size: 0.78rem; font-weight: 700; background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 10px;">
                            0 permisos activos
                        </span>
                    </div>

                    <div id="detalleAdminNotice" style="display: none; background: #fef3c7; border: 1px solid #fde68a; border-radius: var(--radius-md); padding: 1rem; color: #92400e; font-size: 0.9rem;">
                        <i class="fa-solid fa-crown" style="font-size: 1.2rem; margin-right: 6px;"></i>
                        <strong>Control Total Permanente:</strong> Al ser Administrador, este usuario tiene acceso ilimitado a todas las funciones, ventas, compras, caja, transferencias, respaldos y configuraciones sin restricciones.
                    </div>

                    <div id="detallePermisosModulosList" style="display: flex; flex-direction: column; gap: 0.75rem;">
                        {{-- Se llena dinámicamente con JS --}}
                    </div>
                </div>
            </div>

            {{-- Pie del Detalle --}}
            <div style="padding: 1rem 1.5rem; background: #ffffff; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <button type="button" onclick="cerrarModalDetalleUsuario()" class="btn-modern btn-secondary" style="width: auto;">
                    Cerrar
                </button>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" id="btnDetalleAccionPermisos" onclick="irAPermisosDesdeDetalle()" class="btn-modern btn-secondary" style="width: auto; color: #2563eb; border-color: #93c5fd;">
                        <i class="fa-solid fa-shield-halved"></i> Configurar Permisos
                    </button>
                    <button type="button" id="btnDetalleAccionEditar" onclick="irAEditarDesdeDetalle()" class="btn-modern btn-accent" style="width: auto;">
                        <i class="fa-solid fa-pen-to-square"></i> Editar Usuario
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL DE EDICIÓN DE USUARIO --}}
    <div id="modalEditarUsuario" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 550px; margin: 1rem; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                <div>
                    <div class="hero-kicker"><i class="fa-solid fa-user-pen"></i> Editar Cuenta</div>
                    <h3 style="margin: 0.2rem 0 0 0; font-size: 1.3rem;">Editar Usuario</h3>
                </div>
                <button type="button" onclick="cerrarModalEditarUsuario()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: var(--text-muted);">&times;</button>
            </div>

            <form id="formEditarUsuario" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="form-grid" style="gap: 1rem;">
                    <div class="input-group">
                        <label style="font-weight: 700; font-size: 0.85rem;">Nombre</label>
                        <input type="text" class="input-modern" name="name" id="editUserName" required>
                    </div>
                    <div class="input-group">
                        <label style="font-weight: 700; font-size: 0.85rem;">Email</label>
                        <input type="email" class="input-modern" name="email" id="editUserEmail" required>
                    </div>
                    <div class="input-group">
                        <label style="font-weight: 700; font-size: 0.85rem;">Rol</label>
                        <select name="role" id="editUserRole" class="input-modern" required>
                            <option value="vendedor">🏷️ Vendedor / Cajero</option>
                            <option value="encargado">👔 Encargado / Supervisor</option>
                            <option value="administrador">👑 Administrador</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label style="font-weight: 700; font-size: 0.85rem;">Sucursal</label>
                        <select name="sucursal_id" id="editUserSucursal" class="input-modern">
                            <option value="">-- Sin sucursal fija --</option>
                            @foreach($sucursales as $suc)
                                <option value="{{ $suc->id }}">{{ $suc->nombre }} ({{ $suc->codigo }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="input-group">
                        <label style="font-weight: 700; font-size: 0.85rem;">Nueva Contraseña (Opcional)</label>
                        <input type="password" class="input-modern" name="password" placeholder="Dejar vacío para conservar actual">
                    </div>
                    <div class="input-group">
                        <label style="font-weight: 700; font-size: 0.85rem;">Confirmar Nueva Contraseña</label>
                        <input type="password" class="input-modern" name="password_confirmation" placeholder="Repetir nueva contraseña">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_active" id="editUserActive" value="1" checked style="width: 17px; height: 17px; accent-color: var(--primary);">
                            <span style="font-size: 0.85rem; font-weight: 700;">Usuario Activo (Permite iniciar sesión)</span>
                        </label>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                    <button type="button" onclick="cerrarModalEditarUsuario()" class="btn-modern btn-secondary" style="width: auto;">Cancelar</button>
                    <button type="submit" class="btn-modern btn-accent" style="width: auto;">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
@elseif($activeTab === 'sucursales')
    <div class="config-grid">
        {{-- Card 1: Método de Transferencia entre Sucursales (Cloud vs Archivo .TRN) --}}
        @php
            $modoTransferencias = $settings['modo_transferencias'] ?? 'cloud';
        @endphp
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <div class="hero-kicker"><i class="fa-solid fa-arrows-split-up-and-left"></i> Método de Transferencias</div>
                    <h2 class="hero-title" style="font-size: 1.35rem; margin-top: 0.25rem;">Arquitectura de Transferencia entre Sucursales</h2>
                </div>
            </div>

            <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.25rem;">
                Selecciona cómo interactúan tus sucursales para transferir mercancía, productos e inventario.
            </p>

            {{-- Selector de Modo: Nube vs Archivo --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                {{-- Opción 1: Nube / Hosting --}}
                <label class="modo-transferencia-card" id="cardModoCloud" style="border: 2px solid {{ $modoTransferencias === 'cloud' ? 'var(--accent)' : 'var(--border)' }}; background: {{ $modoTransferencias === 'cloud' ? 'rgba(2, 132, 199, 0.04)' : 'var(--bg-card)' }}; border-radius: var(--radius-md); padding: 1.25rem; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="modo_transferencias" value="cloud" {{ $modoTransferencias === 'cloud' ? 'checked' : '' }} onchange="cambiarModoTransferencia('cloud')" style="accent-color: var(--accent); width: 1.1rem; height: 1.1rem;">
                            <span style="font-weight: 700; font-size: 1rem; color: var(--text-main);"><i class="fa-solid fa-cloud" style="color: #0284c7; margin-right: 4px;"></i> Enlace en la Nube / Hosting</span>
                        </div>
                        <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 9999px;">Hostinger / Cloud Sync</span>
                    </div>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0; line-height: 1.4;">
                        Transferencias automáticas por internet. Cada tienda envía y recibe mercancía a través del Buzón Central en la nube en tiempo real.
                    </p>
                </label>

                {{-- Opción 2: Archivo Físico (.TRN / Offline) --}}
                <label class="modo-transferencia-card" id="cardModoArchivo" style="border: 2px solid {{ $modoTransferencias === 'archivo' ? 'var(--accent)' : 'var(--border)' }}; background: {{ $modoTransferencias === 'archivo' ? 'rgba(2, 132, 199, 0.04)' : 'var(--bg-card)' }}; border-radius: var(--radius-md); padding: 1.25rem; cursor: pointer; transition: all 0.2s; display: flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="radio" name="modo_transferencias" value="archivo" {{ $modoTransferencias === 'archivo' ? 'checked' : '' }} onchange="cambiarModoTransferencia('archivo')" style="accent-color: var(--accent); width: 1.1rem; height: 1.1rem;">
                            <span style="font-weight: 700; font-size: 1rem; color: var(--text-main);"><i class="fa-solid fa-file-export" style="color: #10b981; margin-right: 4px;"></i> Archivo Físico (.TRN / Offline)</span>
                        </div>
                        <span class="badge" style="background: #dcfce7; color: #166534; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 9999px;">100% Offline / USB</span>
                    </div>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0; line-height: 1.4;">
                        No requiere hosting ni internet. Al procesar una transferencia se descarga un archivo <code>.trn</code> que se pasa por memoria USB o WhatsApp a la otra sucursal.
                    </p>
                </label>
            </div>

            {{-- Bloque de Parámetros de Hostinger (Modo Cloud) --}}
            <div id="contenedorCamposCloud" style="display: {{ $modoTransferencias === 'cloud' ? 'block' : 'none' }}; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                    <h3 style="font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-server" style="color: #0284c7;"></i> Conexión con Servidor Central en Hostinger
                    </h3>
                    <button type="button" onclick="testConexionCloud()" class="btn-modern btn-secondary" id="btnTestCloud" style="width: auto; font-size: 0.82rem; padding: 5px 12px;">
                        <i class="fa-solid fa-satellite-dish"></i> Probar Conexión
                    </button>
                </div>

                <div id="estadoCloud" style="display: none; margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.85rem;"></div>

                <div class="form-grid">
                    <div class="input-group">
                        <label>URL Endpoint de Hostinger</label>
                        <input type="url" id="inputCloudEndpoint" class="input-modern"
                               placeholder="https://intellicarnicsync.intellisolution.net"
                               value="{{ $settings['cloud_sync_endpoint'] ?? 'https://intellicarnicsync.intellisolution.net' }}">
                    </div>
                    <div class="input-group">
                        <label>API Token de Seguridad</label>
                        <div style="position: relative; display: flex; align-items: center;">
                            <input type="password" id="inputCloudToken" class="input-modern" style="padding-right: 42px;"
                                   placeholder="Clave secreta"
                                   value="{{ $settings['cloud_sync_token'] ?? 'IntelliCarnic_Sync_2026_Key' }}">
                            <button type="button" onclick="togglePasswordVisibility('inputCloudToken', this)" style="position: absolute; right: 10px; background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 4px; font-size: 0.95rem;" title="Mostrar / Ocultar Token">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bloque Informativo de Flujo por Archivo (Modo Archivo) --}}
            <div id="contenedorGuiaArchivo" style="display: {{ $modoTransferencias === 'archivo' ? 'block' : 'none' }}; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 1.25rem;">
                    <h4 style="margin: 0 0 0.75rem 0; color: #1e293b; font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fa-solid fa-circle-info" style="color: #10b981;"></i> Flujo de Operación por Archivo (.TRN)
                    </h4>
                    <ol style="margin: 0; padding-left: 1.25rem; font-size: 0.85rem; color: #475569; display: flex; flex-direction: column; gap: 0.4rem;">
                        <li><strong>Sucursal Origen (Emisor):</strong> Crea la transferencia en el módulo y el sistema descargará automáticamente el archivo <code>TRN-XXXXXXXX-XXXX.trn</code>.</li>
                        <li><strong>Transporte de Archivo:</strong> Se traslada el archivo <code>.trn</code> a la otra tienda mediante memoria USB, red local compartida o por WhatsApp/correo.</li>
                        <li><strong>Sucursal Destino (Receptor):</strong> Entra a <em>Transferencias → Recepciones</em>, presiona <strong>"Importar Archivo .TRN"</strong>, selecciona el archivo y la mercancía ingresará al inventario local al instante.</li>
                    </ol>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 1.25rem;">
                <button type="button" onclick="guardarConfigCloud()" class="btn-modern btn-accent" style="width: auto;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Método de Transferencias
                </button>
            </div>
        </div>

        {{-- Card 2: Catálogo de Sucursales --}}
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <div class="hero-kicker"><i class="fa-solid fa-store"></i> Red de Sucursales</div>
                    <h2 class="hero-title" style="font-size: 1.35rem; margin-top: 0.25rem;">Tiendas de la Carnicería</h2>
                </div>
                <button type="button" onclick="abrirModalNuevaSucursal()" class="btn-modern btn-accent" style="width: auto;">
                    <i class="fa-solid fa-plus"></i> Nueva Sucursal
                </button>
            </div>

            <div style="overflow-x: auto; margin-top: 0.75rem;">
                <table class="data-table" id="tablaSucursales">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre de la Tienda</th>
                            <th>Dirección / Teléfono</th>
                            <th>Identidad de este Equipo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sucursales as $suc)
                        <tr>
                            <td><strong style="font-family: monospace; font-size: 0.95rem;">{{ $suc->codigo }}</strong></td>
                            <td>
                                <strong>{{ $suc->nombre }}</strong>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">
                                {{ $suc->direccion ?? 'Sin dirección' }}
                                @if($suc->telefono)
                                    <br><i class="fa-solid fa-phone" style="font-size: 0.75rem;"></i> {{ $suc->telefono }}
                                @endif
                            </td>
                            <td>
                                @if($suc->es_sucursal_actual)
                                    <span style="background: var(--accent); color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-check"></i> ESTE EQUIPO (ACTUAL)
                                    </span>
                                @else
                                    <button type="button" onclick="marcarSucursalActual({{ $suc->id }})" class="btn-modern btn-secondary" style="font-size: 0.75rem; padding: 3px 8px;" title="Marcar este equipo como esta sucursal">
                                        <i class="fa-solid fa-location-crosshairs"></i> Asignar a este equipo
                                    </button>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.35rem;">
                                    <button type="button" onclick="editarSucursal({{ json_encode($suc) }})" class="btn-modern btn-secondary" style="font-size: 0.75rem; padding: 4px 8px;" title="Editar">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    @if(!$suc->es_sucursal_actual)
                                        <button type="button" onclick="eliminarSucursal({{ $suc->id }})" class="btn-modern" style="font-size: 0.75rem; padding: 4px 8px; background: #fee2e2; color: #991b1b;" title="Eliminar">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                No hay sucursales registradas. Presiona <strong>"Nueva Sucursal"</strong> para agregar las tiendas de tu negocio.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal: Crear / Editar Sucursal --}}
    <div id="modalSucursal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div class="card" style="width: 100%; max-width: 500px; margin: 1rem;">
            <h3 style="margin-top: 0;" id="modalSucursalTitulo"><i class="fa-solid fa-building-circle-check" style="color: var(--accent);"></i> Nueva Sucursal</h3>
            <input type="hidden" id="sucursalEditId" value="">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Código Único *</label>
                    <input type="text" id="sucCodigo" class="input-modern" placeholder="Ej: SUC-01" maxlength="20">
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Identificador corto único para transferencias (ej: SUC-01, SUC-02).</span>
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Nombre de la Sucursal *</label>
                    <input type="text" id="sucNombre" class="input-modern" placeholder="Ej: Sucursal Matriz Centro">
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Dirección</label>
                    <input type="text" id="sucDireccion" class="input-modern" placeholder="Ej: Frente al parque central...">
                </div>
                <div>
                    <label style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); display: block; margin-bottom: 0.35rem;">Teléfono de Contacto</label>
                    <input type="text" id="sucTelefono" class="input-modern" placeholder="Ej: 8888-0000">
                </div>
                <div>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="sucEsActual">
                        <span style="font-size: 0.85rem; font-weight: 600;">Asignar como la sucursal activa de ESTE equipo local</span>
                    </label>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" onclick="document.getElementById('modalSucursal').style.display='none'" class="btn-modern btn-secondary" style="width: auto;">Cancelar</button>
                <button type="button" onclick="guardarSucursalModal()" class="btn-modern btn-accent" style="width: auto;"><i class="fa-solid fa-floppy-disk"></i> Guardar Sucursal</button>
            </div>
        </div>
    </div>
@elseif($activeTab === 'base_datos')
    {{-- Métricas de la Base de Datos --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; background: #ffffff;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-server"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Motor Activo</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: var(--text-main);">{{ $dbStats['driver'] ?? 'SQLITE' }}</div>
            </div>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; background: #ffffff;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-table-list"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Tablas Activas</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: #16a34a;">{{ $dbStats['total_tables'] ?? 0 }} tablas</div>
            </div>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; background: #ffffff;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #faf5ff; color: #9333ea; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Registros Totales</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: #9333ea;">{{ number_format($dbStats['total_rows'] ?? 0) }} filas</div>
            </div>
        </div>

        <div class="card" style="padding: 1.25rem; display: flex; align-items: center; gap: 1rem; background: #ffffff;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-box-archive"></i>
            </div>
            <div>
                <div style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Respaldos Guardados</div>
                <div style="font-size: 1.2rem; font-weight: 800; color: #ea580c;">{{ $dbStats['backups_count'] ?? 0 }} copias</div>
            </div>
        </div>
    </div>

    {{-- Alerta de Recordatorio si han transcurrido más días de los configurados --}}
    @if(isset($reminderStatus) && $reminderStatus['is_due'])
    <div class="card" style="margin-bottom: 1.5rem; background: #fffbeb; border: 1px solid #fde68a; border-left: 5px solid #f59e0b; padding: 1.25rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; gap: 1rem; align-items: center;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <strong style="color: #92400e; font-size: 1rem; display: block;">
                        ⏰ Recordatorio de Respaldo Pendiente
                    </strong>
                    <p style="margin: 0; font-size: 0.85rem; color: #b45309;">
                        Han transcurrido <strong>{{ $reminderStatus['days_elapsed'] }} días</strong> desde la última copia de seguridad (Último: {{ $reminderStatus['last_backup_date'] }}).
                        Te recomendamos generar o programar un respaldo para mantener protegida tu información.
                    </p>
                </div>
            </div>
            <form action="{{ route('configuracion.database.backup') }}" method="POST" style="margin: 0;">
                @csrf
                <button type="submit" class="btn-modern btn-accent" style="width: auto; background: #d97706; border-color: #d97706;">
                    <i class="fa-solid fa-hard-drive"></i> Respaldar Ahora
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Guía Informativa: ¿Qué se respalda y qué se borra al restablecer? --}}
    <div class="card" style="margin-bottom: 1.5rem; background: #f8fafc; border: 1px solid #e2e8f0;">
        <details style="cursor: pointer;">
            <summary style="font-weight: 700; color: var(--primary); font-size: 0.95rem; display: flex; align-items: center; justify-content: space-between; user-select: none;">
                <span><i class="fa-solid fa-circle-question" style="color: var(--primary); margin-right: 6px;"></i> ¿Qué información incluye un Respaldo y qué sucede exactamente al Restablecer?</span>
                <span style="font-size: 0.8rem; background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 10px;">Clic para ver detalle</span>
            </summary>
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: 0.88rem; line-height: 1.6; color: var(--text-main);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem;">
                    <div style="background: #ffffff; padding: 1rem; border-radius: var(--radius-md); border-left: 4px solid #10b981;">
                        <strong style="color: #065f46; font-size: 0.95rem; display: block; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-cloud-arrow-down" style="color: #10b981;"></i> ¿Qué incluye el Respaldo Completo (.SQL)?
                        </strong>
                        <p style="margin: 0 0 0.5rem 0; font-size: 0.83rem; color: var(--text-muted);">
                            Extrae el <strong>100% de la base de datos</strong> en un script estructurado que contiene:
                        </p>
                        <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.82rem; color: #334155;">
                            <li><strong>Inventario y Catálogo:</strong> Artículos, códigos de barra/proveedor/cliente, lotes activos, series, fechas de vencimiento y existencias.</li>
                            <li><strong>Ventas y Facturación:</strong> Tickets de venta, detalles por peso/unidad, formas de pago, clientes asociados e IVA.</li>
                            <li><strong>Compras y Proveedores:</strong> Facturas de compra registradas, costos y proveedores.</li>
                            <li><strong>Caja y Créditos:</strong> Sesiones y cortes de caja, movimientos de efectivo, créditos de clientes y abonos.</li>
                            <li><strong>Transferencias:</strong> Historial de transferencias enviadas y recibidas entre sucursales.</li>
                            <li><strong>Configuraciones y Usuarios:</strong> Cuentas de usuario, credenciales, sucursales y parámetros del sistema.</li>
                        </ul>
                    </div>

                    <div style="background: #ffffff; padding: 1rem; border-radius: var(--radius-md); border-left: 4px solid #dc2626;">
                        <strong style="color: #991b1b; font-size: 0.95rem; display: block; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-rotate-left" style="color: #dc2626;"></i> ¿Qué información se sustituye al Restablecer?
                        </strong>
                        <p style="margin: 0 0 0.5rem 0; font-size: 0.83rem; color: var(--text-muted);">
                            Al restaurar, la base de datos <strong>vuelve exactamente al estado</strong> en que se generó ese archivo de respaldo:
                        </p>
                        <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.82rem; color: #334155;">
                            <li>Se sustituyen todas las tablas por los datos contenidos en el archivo <code>.sql</code>.</li>
                            <li>Cualquier venta, producto o compra ingresada <em>después</em> de la fecha del respaldo será reemplazada por los datos del archivo.</li>
                            <li><strong>🛡️ Medida de Protección Preventiva:</strong> El sistema genera automáticamente un respaldo de emergencia previo (<code>pre_restore_backup_*.sql</code>) antes de aplicar cualquier cambio, para que puedas recuperar tu estado anterior si fue un error.</li>
                            <li><strong>No se borran:</strong> Los archivos de instalación, fotos o imágenes del servidor, ni el software local.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </details>
    </div>

    <div class="config-grid" style="grid-template-columns: 1fr; gap: 1.5rem;">
        {{-- Card 1: Copias de Seguridad (Backup) y Subir Archivo --}}
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <div class="hero-kicker"><i class="fa-solid fa-cloud-arrow-down"></i> Respaldo de Base de Datos</div>
                    <h2 class="hero-title" style="font-size: 1.35rem; margin-top: 0.25rem;">Copias de Seguridad Integrales (.SQL)</h2>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" onclick="document.getElementById('seccionCargarRespaldo').style.display = document.getElementById('seccionCargarRespaldo').style.display === 'none' ? 'block' : 'none'" class="btn-modern btn-secondary" style="width: auto;">
                        <i class="fa-solid fa-upload"></i> Cargar Respaldo (.sql)
                    </button>
                    <form action="{{ route('configuracion.database.backup') }}" method="POST" style="margin: 0;" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=\'fa-solid fa-spinner fa-spin\'></i> Generando...';">
                        @csrf
                        <button type="submit" class="btn-modern btn-accent" style="width: auto;">
                            <i class="fa-solid fa-hard-drive"></i> Generar Respaldo Ahora (.sql)
                        </button>
                    </form>
                </div>
            </div>

            <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.25rem;">
                Genera un archivo SQL completo que incluye toda la información: Catálogo de productos, lotes y existencias, historial de ventas, compras, cortes de caja, clientes, proveedores, transferencias y parámetros del negocio.
            </p>

            {{-- Sección Desplegable para Cargar Respaldo Externo a la Biblioteca --}}
            <div id="seccionCargarRespaldo" style="display: none; background: #f0fdf4; border: 1px dashed #86efac; border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
                <form action="{{ route('configuracion.database.upload') }}" method="POST" enctype="multipart/form-data" style="margin: 0;">
                    @csrf
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <strong style="color: #166534; font-size: 0.95rem;">
                            <i class="fa-solid fa-file-import"></i> Subir Archivo de Respaldo a la Biblioteca del Servidor
                        </strong>
                        <button type="button" onclick="document.getElementById('seccionCargarRespaldo').style.display='none'" style="background: none; border: none; color: #166534; cursor: pointer; font-size: 1rem;">&times;</button>
                    </div>
                    <p style="font-size: 0.82rem; color: #14532d; margin-bottom: 1rem;">
                        Selecciona un archivo <code>.sql</code> desde tu equipo, memoria USB o correo. Se guardará en la lista de respaldos locales para que puedas descargarlo o restaurarlo cuando lo requieras.
                    </p>
                    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                        <input type="file" name="archivo_sql" accept=".sql" class="input-modern" required style="flex: 1; min-width: 250px; background: #ffffff;">
                        <button type="submit" class="btn-modern btn-accent" style="width: auto; background: #16a34a; border-color: #16a34a;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Guardar en Biblioteca
                        </button>
                    </div>
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table class="data-table" id="tablaRespaldos">
                    <thead>
                        <tr>
                            <th>Archivo de Respaldo</th>
                            <th>Fecha y Hora</th>
                            <th>Tamaño</th>
                            <th>Tipo</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($backups as $b)
                        <tr>
                            <td>
                                <strong style="font-family: monospace; font-size: 0.9rem; color: var(--text-main);">
                                    <i class="fa-solid fa-file-code" style="color: var(--primary); margin-right: 4px;"></i> {{ $b['filename'] }}
                                </strong>
                            </td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);">{{ $b['created_at'] }}</td>
                            <td><span style="font-weight: 700; font-size: 0.85rem;">{{ $b['size'] }}</span></td>
                            <td>
                                @if(($b['tipo'] ?? '') === 'preventivo' || $b['is_emergency'])
                                    <span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-shield-halved"></i> Preventivo
                                    </span>
                                @elseif(($b['tipo'] ?? '') === 'automatico' || ($b['is_auto'] ?? false))
                                    <span style="background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-robot"></i> Automático
                                    </span>
                                @else
                                    <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 700;">
                                        <i class="fa-solid fa-user-shield"></i> Manual
                                    </span>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                <div style="display: inline-flex; gap: 0.5rem; justify-content: center;">
                                    <a href="{{ route('configuracion.database.download', $b['filename']) }}" class="btn-modern btn-secondary" style="font-size: 0.8rem; padding: 5px 10px; text-decoration: none;" title="Descargar archivo a mi computadora">
                                        <i class="fa-solid fa-download"></i> Descargar
                                    </a>
                                    <button type="button" onclick="abrirModalRestaurar('existente', '{{ $b['filename'] }}')" class="btn-modern btn-secondary" style="font-size: 0.8rem; padding: 5px 10px; color: #ea580c; border-color: #fdba74;" title="Restaurar base de datos a este punto">
                                        <i class="fa-solid fa-rotate-left"></i> Restaurar
                                    </button>
                                    <button type="button" onclick="eliminarRespaldo('{{ $b['filename'] }}')" class="btn-modern" style="font-size: 0.8rem; padding: 5px 10px; background: #fee2e2; color: #991b1b;" title="Eliminar copia">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                <i class="fa-solid fa-database" style="font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: 0.5rem;"></i>
                                No hay copias de seguridad guardadas en el almacenamiento local.<br>
                                Presiona <strong>"Generar Respaldo Ahora (.sql)"</strong> o <strong>"Cargar Respaldo"</strong> para agregar tu primera copia de seguridad.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Card: Programación de Respaldos Automáticos y Recordatorios --}}
        <div class="card" style="border-left: 4px solid var(--accent);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <div class="hero-kicker" style="color: var(--accent);"><i class="fa-solid fa-clock"></i> Automatización y Recordatorios</div>
                    <h2 class="hero-title" style="font-size: 1.35rem; margin-top: 0.25rem;">Programación de Respaldos Periódicos</h2>
                </div>
            </div>

            <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.25rem;">
                Configura la frecuencia con la que el sistema generará copias de seguridad de forma autónoma y define alertas para que nunca olvides respaldar tu información.
            </p>

            <form action="{{ route('configuracion.database.configAuto') }}" method="POST">
                @csrf
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.5rem;">
                    {{-- Switch Activar Auto Backup --}}
                    <div style="background: var(--surface-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                        <label style="font-weight: 700; color: var(--text-main); font-size: 0.95rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="backup_auto_enabled" value="1" {{ ($settings['backup_auto_enabled'] ?? '1') == '1' ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--primary);">
                            <span>Activar Respaldos Automáticos</span>
                        </label>
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0.5rem 0 0 0;">
                            Genera copias con la etiqueta <code>🤖 Automático</code> de forma transparente en segundo plano.
                        </p>
                    </div>

                    {{-- Frecuencia --}}
                    <div style="background: var(--surface-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                        <label style="font-weight: 700; color: var(--text-main); font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-repeat" style="color: var(--primary);"></i> Frecuencia de Ejecución
                        </label>
                        @php $frec = $settings['backup_frecuencia'] ?? 'semanal'; @endphp
                        <select name="backup_frecuencia" class="input-modern" style="font-size: 0.85rem;">
                            <option value="semanal" {{ $frec === 'semanal' ? 'selected' : '' }}>🗓️ Semanal (Cada 7 días)</option>
                            <option value="quincenal" {{ $frec === 'quincenal' ? 'selected' : '' }}>📅 Quincenal (Cada 15 días)</option>
                            <option value="mensual" {{ $frec === 'mensual' ? 'selected' : '' }}>📆 Mensual (Cada 30 días)</option>
                            <option value="cierre_caja" {{ $frec === 'cierre_caja' ? 'selected' : '' }}>💰 Al realizar cada Cierre de Caja (Corte Z)</option>
                        </select>
                    </div>

                    {{-- Recordatorio de Días sin Respaldo --}}
                    <div style="background: var(--surface-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                        <label style="font-weight: 700; color: var(--text-main); font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-bell" style="color: #f59e0b;"></i> Recordatorio Visual de Alerta
                        </label>
                        @php $recDias = $settings['backup_recordatorio_dias'] ?? 7; @endphp
                        <select name="backup_recordatorio_dias" class="input-modern" style="font-size: 0.85rem;">
                            <option value="3" {{ $recDias == 3 ? 'selected' : '' }}>Avisar si pasan más de 3 días sin respaldo</option>
                            <option value="7" {{ $recDias == 7 ? 'selected' : '' }}>Avisar si pasan más de 7 días sin respaldo</option>
                            <option value="15" {{ $recDias == 15 ? 'selected' : '' }}>Avisar si pasan más de 15 días sin respaldo</option>
                            <option value="30" {{ $recDias == 30 ? 'selected' : '' }}>Avisar si pasan más de 30 días sin respaldo</option>
                        </select>
                    </div>

                    {{-- Retención / Cuota de almacenamiento --}}
                    <div style="background: var(--surface-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                        <label style="font-weight: 700; color: var(--text-main); font-size: 0.9rem; display: block; margin-bottom: 0.25rem;">
                            <i class="fa-solid fa-box-archive" style="color: var(--accent);"></i> Retención de Respaldos Automáticos
                        </label>
                        <p style="font-size: 0.76rem; color: var(--text-muted); margin: 0 0 0.5rem 0;">
                            Límite de copias a conservar. Al superarse, se borran las más antiguas para no llenar tu disco.
                        </p>
                        @php $ret = $settings['backup_retencion_dias'] ?? 10; @endphp
                        <select name="backup_retencion_dias" class="input-modern" style="font-size: 0.85rem;">
                            <option value="5" {{ $ret == 5 ? 'selected' : '' }}>Conservar los últimos 5 respaldos automáticos</option>
                            <option value="10" {{ $ret == 10 ? 'selected' : '' }}>Conservar los últimos 10 respaldos automáticos</option>
                            <option value="15" {{ $ret == 15 ? 'selected' : '' }}>Conservar los últimos 15 respaldos automáticos</option>
                            <option value="30" {{ $ret == 30 ? 'selected' : '' }}>Conservar los últimos 30 respaldos automáticos</option>
                        </select>
                    </div>
                </div>

                {{-- Pie informativo y botón de guardar --}}
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem;">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        <i class="fa-solid fa-circle-info" style="color: var(--primary);"></i>
                        Último respaldo automático: <strong>{{ $settings['backup_ultimo_auto'] ?? 'Ninguno registrado aún' }}</strong>
                    </div>
                    <button type="submit" class="btn-modern btn-accent" style="width: auto;">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Programación y Recordatorios
                    </button>
                </div>
            </form>
        </div>

        {{-- Card 2: Restablecimiento y Recuperación (Zona de Seguridad Crítica) --}}
        <div class="card" style="border-left: 4px solid #dc2626;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <div class="hero-kicker" style="color: #dc2626;"><i class="fa-solid fa-triangle-exclamation"></i> Zona de Recuperación y Peligro</div>
                    <h2 class="hero-title" style="font-size: 1.35rem; margin-top: 0.25rem;">Restablecimiento de la Base de Datos</h2>
                </div>
            </div>

            {{-- Alerta de Seguridad --}}
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <i class="fa-solid fa-shield-halved" style="font-size: 1.5rem; color: #dc2626; margin-top: 2px;"></i>
                    <div>
                        <strong style="color: #991b1b; font-size: 0.95rem; display: block; margin-bottom: 0.25rem;">
                            Protocolo de Seguridad y Advertencias de Restauración
                        </strong>
                        <ul style="margin: 0; padding-left: 1.2rem; font-size: 0.85rem; color: #7f1d1d; line-height: 1.5;">
                            <li>Esta operación <strong>reemplazará toda la información actual</strong> de la base de datos por la contenida en el archivo de respaldo seleccionado.</li>
                            <li>Como medida de seguridad preventiva, el sistema creará automáticamente un respaldo de emergencia previo antes de aplicar los cambios.</li>
                            <li>Asegúrate de que ningún otro usuario esté realizando ventas o movimientos durante el proceso.</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Selector de Origen de Restauración --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem;">
                {{-- Opción 1: Subir archivo SQL desde el equipo --}}
                <div style="background: var(--surface-bg); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                    <div style="font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem; font-size: 0.95rem;">
                        <i class="fa-solid fa-upload" style="color: var(--primary);"></i> Opción 1: Subir y Restaurar Directamente
                    </div>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Selecciona un archivo <code>.sql</code> de respaldo desde tu computadora o memoria USB para restaurar.
                    </p>
                    <input type="file" id="inputArchivoSqlRestore" accept=".sql" class="input-modern" style="padding: 0.5rem; font-size: 0.85rem;">
                    <button type="button" onclick="iniciarProcesoRestauracion('subir')" class="btn-modern" style="width: 100%; margin-top: 1rem; background: #dc2626; color: #fff; font-weight: 700;">
                        <i class="fa-solid fa-rotate-left"></i> Restaurar desde Archivo Subido
                    </button>
                </div>

                {{-- Opción 2: Empezar desde 0 (Reseteo de Fábrica) --}}
                <div style="background: var(--surface-bg); border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 1.25rem;">
                    <div style="font-weight: 700; color: #991b1b; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        <i class="fa-solid fa-broom" style="color: #dc2626;"></i> Opción 2: Empezar desde 0 (Reseteo de Fábrica)
                    </div>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 1rem;">
                        Borra todas las operaciones y catálogo (ventas, compras, artículos, transferencias, caja) para dejar el sistema limpio desde cero, conservando tu usuario administrador y configuraciones.
                    </p>
                    <div style="padding-top: 0.25rem;">
                        <button type="button" onclick="abrirModalResetTotal()" class="btn-modern" style="width: 100%; margin-top: 1rem; background: #991b1b; color: #fff; font-weight: 700;">
                            <i class="fa-solid fa-trash-can"></i> Limpiar Todo y Empezar desde 0
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Confirmación de Reseteo a 0 (Limpieza de Fábrica) --}}
    <div id="modalConfirmarResetTotal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.8); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; margin: 1rem; border: 2px solid #991b1b; box-shadow: 0 25px 50px -12px rgba(153, 27, 27, 0.4);">
            <div style="display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid #fee2e2; padding-bottom: 1rem; margin-bottom: 1rem;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: #fee2e2; color: #991b1b; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                    <i class="fa-solid fa-skull-crossbones"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: #991b1b; font-size: 1.25rem;">¡Advertencia de Reseteo a 0!</h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Limpieza Total para Iniciar Operaciones Limpias</span>
                </div>
            </div>

            <div style="color: var(--text-main); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.25rem;">
                <p style="margin-top: 0;">
                    Estás a punto de <strong style="color: #991b1b;">borrar toda la información operativa</strong> (ventas, compras, catálogo de artículos, transferencias, movimientos de caja y créditos).
                </p>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 0.85rem; font-size: 0.82rem; color: #991b1b; margin-bottom: 0.75rem;">
                    <strong>🛡️ Respaldo Preventivo Automático:</strong> El sistema guardará una copia de seguridad de emergencia previa (<code>pre_reset_backup_*.sql</code>) por si deseas volver al estado actual en el futuro.
                </div>
                <div style="font-size: 0.82rem; color: #475569;">
                    ✓ Se conservará tu usuario administrador (<code>admin@gmail.com</code>) y la configuración de sucursales e impuestos.
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="font-size: 0.85rem; font-weight: 700; color: #991b1b; display: block; margin-bottom: 0.4rem;">
                    Escribe la palabra <span style="background: #fee2e2; padding: 1px 6px; border-radius: 4px; font-family: monospace; letter-spacing: 1px;">RESETEAR</span> para desbloquear la confirmación:
                </label>
                <input type="text" id="inputPalabraResetear" class="input-modern" placeholder="Escribe RESETEAR aquí..." style="border-color: #fca5a5; font-weight: 700; text-align: center; letter-spacing: 1px;" oninput="validarPalabraResetear()">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" onclick="cerrarModalResetTotal()" class="btn-modern btn-secondary" style="width: auto;">
                    Cancelar
                </button>
                <button type="button" id="btnEjecutarResetTotal" onclick="ejecutarResetTotal()" class="btn-modern" style="width: auto; background: #991b1b; color: #fff; font-weight: 700; opacity: 0.5;" disabled>
                    <i class="fa-solid fa-trash-can"></i> Confirmar Reseteo a 0
                </button>
            </div>
        </div>
    </div>

    {{-- Modal de Confirmación de Seguridad Crítica para Restauración --}}
    <div id="modalConfirmarRestauracion" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.75); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div class="card" style="width: 100%; max-width: 520px; margin: 1rem; border: 2px solid #dc2626; box-shadow: 0 25px 50px -12px rgba(220, 38, 38, 0.25);">
            <div style="display: flex; align-items: center; gap: 0.75rem; border-bottom: 1px solid #fee2e2; padding-bottom: 1rem; margin-bottom: 1rem;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 style="margin: 0; color: #991b1b; font-size: 1.25rem;">Confirmación de Seguridad Crítica</h3>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Restablecimiento Integral de la Base de Datos</span>
                </div>
            </div>

            <div style="color: var(--text-main); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.25rem;">
                <p style="margin-top: 0;">
                    Estás a punto de restaurar la base de datos desde:
                    <strong id="modalRestoreOrigenTexto" style="color: #dc2626; font-family: monospace; display: block; margin-top: 0.25rem; word-break: break-all;"></strong>
                </p>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-md); padding: 0.85rem; font-size: 0.82rem; color: #991b1b;">
                    <strong><i class="fa-solid fa-circle-exclamation"></i> ¡Atención!</strong> Toda la información actual será sustituida. Esta acción no se puede deshacer una vez confirmada.
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="font-size: 0.85rem; font-weight: 700; color: #991b1b; display: block; margin-bottom: 0.4rem;">
                    Escribe la palabra <span style="background: #fee2e2; padding: 1px 6px; border-radius: 4px; font-family: monospace; letter-spacing: 1px;">RESTAURAR</span> para desbloquear la confirmación:
                </label>
                <input type="text" id="inputPalabraRestaurar" class="input-modern" placeholder="Escribe RESTAURAR aquí..." style="border-color: #fca5a5; font-weight: 700; text-align: center; letter-spacing: 1px;" oninput="validarPalabraRestaurar()">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" onclick="cerrarModalRestaurar()" class="btn-modern btn-secondary" style="width: auto;">
                    Cancelar
                </button>
                <button type="button" id="btnEjecutarRestauracion" onclick="ejecutarRestauracion()" class="btn-modern" style="width: auto; background: #dc2626; color: #fff; font-weight: 700; opacity: 0.5;" disabled>
                    <i class="fa-solid fa-skull-crossbones"></i> Confirmar Restauración Total
                </button>
            </div>
        </div>
    </div>
@endif

<script src="{{ asset('js/configuracion.js') }}"></script>

@if($activeTab === 'sucursales')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function abrirModalNuevaSucursal() {
        document.getElementById('sucursalEditId').value = '';
        document.getElementById('sucCodigo').value = '';
        document.getElementById('sucNombre').value = '';
        document.getElementById('sucDireccion').value = '';
        document.getElementById('sucTelefono').value = '';
        document.getElementById('sucEsActual').checked = false;
        document.getElementById('modalSucursalTitulo').innerHTML = '<i class="fa-solid fa-building-circle-check" style="color: var(--accent);"></i> Nueva Sucursal';
        document.getElementById('modalSucursal').style.display = 'flex';
    }

    function editarSucursal(suc) {
        document.getElementById('sucursalEditId').value = suc.id;
        document.getElementById('sucCodigo').value = suc.codigo;
        document.getElementById('sucNombre').value = suc.nombre;
        document.getElementById('sucDireccion').value = suc.direccion || '';
        document.getElementById('sucTelefono').value = suc.telefono || '';
        document.getElementById('sucEsActual').checked = !!suc.es_sucursal_actual;
        document.getElementById('modalSucursalTitulo').innerHTML = '<i class="fa-solid fa-pen-to-square" style="color: var(--accent);"></i> Editar Sucursal';
        document.getElementById('modalSucursal').style.display = 'flex';
    }

    function guardarSucursalModal() {
        const id = document.getElementById('sucursalEditId').value;
        const payload = {
            codigo: document.getElementById('sucCodigo').value.trim(),
            nombre: document.getElementById('sucNombre').value.trim(),
            direccion: document.getElementById('sucDireccion').value.trim(),
            telefono: document.getElementById('sucTelefono').value.trim(),
            es_sucursal_actual: document.getElementById('sucEsActual').checked ? 1 : 0,
        };

        if (!payload.codigo || !payload.nombre) {
            alert('Código y Nombre son obligatorios.');
            return;
        }

        const url = id ? `/sucursales/${id}` : '{{ route("sucursales.store") }}';
        const method = id ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + (data.error || JSON.stringify(data.errors || 'Error')));
            }
        })
        .catch(err => alert('Error: ' + err.message));
    }

    function marcarSucursalActual(id) {
        if (!confirm('¿Marcar este equipo como esta sucursal?')) return;

        fetch(`/sucursales/${id}/marcar-actual`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { alert(data.message); location.reload(); }
            else alert('Error: ' + data.error);
        })
        .catch(err => alert('Error: ' + err.message));
    }

    function eliminarSucursal(id) {
        if (!confirm('¿Está seguro de eliminar esta sucursal?')) return;

        fetch(`/sucursales/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { alert(data.message); location.reload(); }
            else alert('Error: ' + data.error);
        })
        .catch(err => alert('Error: ' + err.message));
    }

    function cambiarModoTransferencia(modo) {
        const cardCloud = document.getElementById('cardModoCloud');
        const cardArchivo = document.getElementById('cardModoArchivo');
        const contCloud = document.getElementById('contenedorCamposCloud');
        const contArchivo = document.getElementById('contenedorGuiaArchivo');

        if (modo === 'cloud') {
            cardCloud.style.borderColor = 'var(--accent)';
            cardCloud.style.background = 'rgba(2, 132, 199, 0.04)';
            cardArchivo.style.borderColor = 'var(--border)';
            cardArchivo.style.background = 'var(--bg-card)';
            contCloud.style.display = 'block';
            contArchivo.style.display = 'none';
        } else {
            cardArchivo.style.borderColor = 'var(--accent)';
            cardArchivo.style.background = 'rgba(2, 132, 199, 0.04)';
            cardCloud.style.borderColor = 'var(--border)';
            cardCloud.style.background = 'var(--bg-card)';
            contCloud.style.display = 'none';
            contArchivo.style.display = 'block';
        }
    }

    function guardarConfigCloud() {
        const modo = document.querySelector('input[name="modo_transferencias"]:checked')?.value || 'cloud';
        const endpoint = document.getElementById('inputCloudEndpoint')?.value.trim();
        const token = document.getElementById('inputCloudToken')?.value.trim();

        fetch('{{ route("transferencias.api.guardar-config-cloud") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ modo_transferencias: modo, cloud_sync_endpoint: endpoint, cloud_sync_token: token }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Configuración de transferencias guardada exitosamente.');
                location.reload();
            } else {
                alert('Error al guardar configuración: ' + (data.error || 'Error'));
            }
        })
        .catch(err => alert('Error: ' + err.message));
    }

    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        const icon = btn.querySelector('i');
        if (icon) {
            icon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        }
    }

    function testConexionCloud() {
        const btn = document.getElementById('btnTestCloud');
        const div = document.getElementById('estadoCloud');
        const endpoint = document.getElementById('inputCloudEndpoint')?.value.trim() || '';
        const token = document.getElementById('inputCloudToken')?.value.trim() || '';

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Probando...';

        const params = new URLSearchParams();
        if (endpoint) params.append('endpoint', endpoint);
        if (token) params.append('token', token);

        const url = '{{ route("transferencias.api.test-conexion") }}' + (params.toString() ? '?' + params.toString() : '');

        fetch(url, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-satellite-dish"></i> Probar Conexión';
            div.style.display = 'block';
            if (data.success) {
                div.style.background = '#dcfce7';
                div.style.color = '#166534';
                div.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${data.message} — Estado: <strong>${data.server_status}</strong> — Hora Hostinger: ${data.timestamp}`;
            } else {
                div.style.background = '#fee2e2';
                div.style.color = '#991b1b';
                div.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${data.error}`;
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-satellite-dish"></i> Probar Conexión';
            div.style.display = 'block';
            div.style.background = '#fee2e2';
            div.style.color = '#991b1b';
            div.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Error de red: ' + err.message;
        });
    }
</script>
@endif

@if($activeTab === 'users')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let currentUserIdPermisos = null;
    let currentUserIsAdmin = false;
    const defaultVendedorPerms = @json(\App\Models\User::getDefaultPermissionsForRole('vendedor'));
    const defaultEncargadoPerms = @json(\App\Models\User::getDefaultPermissionsForRole('encargado'));

    function abrirModalPermisos(userId, userName, userRole, permissions, isAdmin) {
        currentUserIdPermisos = userId;
        currentUserIsAdmin = isAdmin;

        document.getElementById('permisosModalUserName').textContent = 'Permisos de ' + userName;
        const roleBadge = document.getElementById('permisosModalUserRoleBadge');
        roleBadge.textContent = userRole.toUpperCase();

        const adminNotice = document.getElementById('permisosAdminNotice');
        const grid = document.getElementById('gridModulosPermisos');

        // Desmarcar todo inicialmente
        document.querySelectorAll('.permiso-checkbox').forEach(cb => cb.checked = false);

        if (isAdmin) {
            adminNotice.style.display = 'block';
            document.querySelectorAll('.permiso-checkbox').forEach(cb => {
                cb.checked = true;
                cb.disabled = true;
            });
            document.querySelectorAll('.modulo-master-check').forEach(cb => {
                cb.checked = true;
                cb.disabled = true;
            });
        } else {
            adminNotice.style.display = 'none';
            document.querySelectorAll('.permiso-checkbox').forEach(cb => cb.disabled = false);
            document.querySelectorAll('.modulo-master-check').forEach(cb => cb.disabled = false);

            if (Array.isArray(permissions)) {
                permissions.forEach(p => {
                    const cb = document.querySelector(`.permiso-checkbox[value="${p}"]`);
                    if (cb) cb.checked = true;
                });
            }
        }

        actualizarContadoresPermisos();
        document.getElementById('modalMatrizPermisos').style.display = 'flex';
    }

    function cerrarModalPermisos() {
        document.getElementById('modalMatrizPermisos').style.display = 'none';
    }

    function toggleModuloPermisos(modKey, isChecked) {
        if (currentUserIsAdmin) return;
        document.querySelectorAll(`.perm-mod-${modKey}`).forEach(cb => {
            // Solo marcar si el elemento no está oculto por filtro
            const row = cb.closest('.permiso-item-row');
            if (row && row.style.display !== 'none') {
                cb.checked = isChecked;
            } else if (!row) {
                cb.checked = isChecked;
            }
        });
        actualizarContadoresPermisos();
    }

    function filtrarPermisosColumna(modKey, query) {
        const q = query.trim().toLowerCase();
        const rows = document.querySelectorAll(`#lista_${modKey} .permiso-item-row`);
        rows.forEach(row => {
            const name = row.getAttribute('data-perm-name') || '';
            const key = row.getAttribute('data-perm-key') || '';
            if (name.includes(q) || key.includes(q)) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function aplicarPlantillaPermisos(tipo) {
        if (currentUserIsAdmin) return;
        const targetPerms = (tipo === 'encargado') ? defaultEncargadoPerms : defaultVendedorPerms;
        document.querySelectorAll('.permiso-checkbox').forEach(cb => {
            cb.checked = targetPerms.includes(cb.value);
        });
        actualizarContadoresPermisos();
    }

    function marcarTodosLosPermisos(checkAll) {
        if (currentUserIsAdmin) return;
        document.querySelectorAll('.permiso-checkbox').forEach(cb => {
            cb.checked = checkAll;
        });
        actualizarContadoresPermisos();
    }

    function actualizarContadoresPermisos() {
        let totalCheckedGlobal = 0;
        document.querySelectorAll('.modulo-permiso-card').forEach(card => {
            const modKey = card.getAttribute('data-module');
            const checkboxes = card.querySelectorAll('.permiso-checkbox');
            const totalMod = checkboxes.length;
            let checkedMod = 0;

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    checkedMod++;
                    totalCheckedGlobal++;
                }
            });

            const countBadge = document.getElementById(`count_${modKey}`);
            if (countBadge) {
                countBadge.textContent = `${checkedMod}/${totalMod}`;
            }

            const masterCheck = card.querySelector('.modulo-master-check');
            if (masterCheck) {
                masterCheck.checked = (checkedMod === totalMod && totalMod > 0);
                masterCheck.indeterminate = (checkedMod > 0 && checkedMod < totalMod);
            }
        });

        const totalEl = document.getElementById('permisosContadorSeleccionados');
        if (totalEl) {
            totalEl.textContent = `${totalCheckedGlobal} permisos seleccionados`;
        }
    }

    function guardarPermisosModal() {
        if (!currentUserIdPermisos) return;

        if (currentUserIsAdmin) {
            alert('El Administrador posee todos los permisos automáticamente.');
            cerrarModalPermisos();
            return;
        }

        const selected = [];
        document.querySelectorAll('.permiso-checkbox:checked').forEach(cb => {
            selected.push(cb.value);
        });

        const btnTop = document.getElementById('btnGuardarPermisosTop');
        const btnBottom = document.getElementById('btnGuardarPermisosBottom');
        btnTop.disabled = true;
        btnBottom.disabled = true;
        btnTop.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...';

        fetch(`/configuracion/usuarios/${currentUserIdPermisos}/permisos`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                permissions: selected,
            }),
        })
        .then(r => r.json())
        .then(data => {
            btnTop.disabled = false;
            btnBottom.disabled = false;
            btnTop.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> ACTUALIZAR PERMISOS';
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ Error al guardar permisos: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(err => {
            btnTop.disabled = false;
            btnBottom.disabled = false;
            btnTop.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> ACTUALIZAR PERMISOS';
            alert('❌ Error de comunicación: ' + err.message);
        });
    }

    function abrirModalEditarUsuario(user) {
        document.getElementById('formEditarUsuario').action = `/configuracion/usuarios/${user.id}`;
        document.getElementById('editUserName').value = user.name || '';
        document.getElementById('editUserEmail').value = user.email || '';
        document.getElementById('editUserRole').value = user.role || 'vendedor';
        document.getElementById('editUserSucursal').value = user.sucursal_id || '';
        document.getElementById('editUserActive').checked = (user.is_active !== false && user.is_active !== 0);

        // Si es admin protegido, deshabilitar cambio de rol y desactivación
        const isProtectedAdmin = (user.email && user.email.toLowerCase() === 'admin@gmail.com');
        document.getElementById('editUserRole').disabled = isProtectedAdmin;
        document.getElementById('editUserActive').disabled = isProtectedAdmin;

        document.getElementById('modalEditarUsuario').style.display = 'flex';
    }

    function cerrarModalEditarUsuario() {
        document.getElementById('modalEditarUsuario').style.display = 'none';
    }

    // ─── Modal de Detalle de Usuario ───
    let selectedUserForDetail = null;
    let selectedUserSucursal = null;
    let selectedUserPerms = [];
    let selectedUserIsAdmin = false;
    const allSystemModules = @json($allModules);

    function abrirModalDetalleUsuario(user, sucursal, permissions, isAdmin) {
        selectedUserForDetail = user;
        selectedUserSucursal = sucursal;
        selectedUserPerms = Array.isArray(permissions) ? permissions : [];
        selectedUserIsAdmin = isAdmin;

        // Nombre, Avatar, Email
        document.getElementById('detalleUserName').textContent = user.name || 'Usuario';
        document.getElementById('detalleUserEmail').textContent = user.email || '';
        document.getElementById('detalleUserAvatar').textContent = (user.name || 'US').substring(0, 2).toUpperCase();

        // Badge Rol
        const badgeEl = document.getElementById('detalleUserRoleBadge');
        const role = user.role || 'vendedor';
        let roleLabel = 'Vendedor';
        let roleBg = '#f0fdf4';
        let roleColor = '#15803d';

        if (isAdmin || role === 'administrador') {
            roleLabel = '👑 Administrador';
            roleBg = '#f3e8ff';
            roleColor = '#7e22ce';
        } else if (role === 'encargado') {
            roleLabel = '👔 Encargado';
            roleBg = '#eff6ff';
            roleColor = '#1d4ed8';
        } else {
            roleLabel = '🏷️ Vendedor';
        }
        badgeEl.textContent = roleLabel;
        badgeEl.style.background = roleBg;
        badgeEl.style.color = roleColor;

        // Sucursal
        const sucursalEl = document.getElementById('detalleUserSucursal');
        if (sucursal) {
            sucursalEl.innerHTML = `<i class="fa-solid fa-building" style="color: #0284c7; margin-right: 4px;"></i> ${sucursal.nombre} <span style="background: #e0f2fe; color: #0369a1; padding: 1px 6px; border-radius: 6px; font-size: 0.72rem; font-weight: 800; margin-left: 4px;">${sucursal.codigo}</span>`;
        } else {
            sucursalEl.innerHTML = `<span style="color: var(--text-muted);">Sin sucursal fija</span>`;
        }

        // Estado
        const estadoEl = document.getElementById('detalleUserEstado');
        if (user.is_active !== false && user.is_active !== 0) {
            estadoEl.innerHTML = `<span style="color: #16a34a; background: #dcfce7; padding: 2px 8px; border-radius: 8px;"><i class="fa-solid fa-circle-check"></i> Activo</span>`;
        } else {
            estadoEl.innerHTML = `<span style="color: #dc2626; background: #fee2e2; padding: 2px 8px; border-radius: 8px;"><i class="fa-solid fa-circle-xmark"></i> Inactivo</span>`;
        }

        // Fecha de Registro
        const fechaEl = document.getElementById('detalleUserCreatedAt');
        if (user.created_at) {
            const d = new Date(user.created_at);
            fechaEl.textContent = isNaN(d.getTime()) ? user.created_at : d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        } else {
            fechaEl.textContent = 'No registrada';
        }

        // Nivel de acceso
        const nivelEl = document.getElementById('detalleUserAccessLevel');
        if (isAdmin) {
            nivelEl.innerHTML = `<span style="color: #7e22ce; font-weight: 800;">Acceso Total Irrestricto</span>`;
        } else if (role === 'encargado') {
            nivelEl.innerHTML = `<span style="color: #1d4ed8; font-weight: 700;">Supervisor / Gestión de Tienda</span>`;
        } else {
            nivelEl.innerHTML = `<span style="color: #15803d; font-weight: 700;">Operativo / Caja y Ventas</span>`;
        }

        // Resumen de Permisos
        const adminNotice = document.getElementById('detalleAdminNotice');
        const listContainer = document.getElementById('detallePermisosModulosList');
        const badgePermsCount = document.getElementById('detallePermsCountBadge');
        const btnPermisosAction = document.getElementById('btnDetalleAccionPermisos');

        listContainer.innerHTML = '';

        if (isAdmin) {
            adminNotice.style.display = 'block';
            badgePermsCount.textContent = 'Acceso al 100% de funciones';
            badgePermsCount.style.background = '#f3e8ff';
            badgePermsCount.style.color = '#7e22ce';
            btnPermisosAction.style.display = 'none';
        } else {
            adminNotice.style.display = 'none';
            btnPermisosAction.style.display = 'inline-flex';
            badgePermsCount.style.background = '#e0f2fe';
            badgePermsCount.style.color = '#0369a1';
            badgePermsCount.textContent = `${selectedUserPerms.length} permisos habilitados`;

            // Construir desglose modular
            for (const [modKey, modData] of Object.entries(allSystemModules)) {
                const activeInMod = [];
                for (const [pKey, pLabel] of Object.entries(modData.permissions)) {
                    if (selectedUserPerms.includes(pKey)) {
                        activeInMod.push(pLabel);
                    }
                }

                const modCard = document.createElement('div');
                modCard.style.cssText = 'border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem 1rem; background: #f8fafc;';

                if (activeInMod.length > 0) {
                    modCard.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="font-size: 0.85rem; color: var(--text-main);"><i class="${modData.icon}" style="color: var(--primary); margin-right: 4px;"></i> ${modData.name}</strong>
                            <span style="background: #dcfce7; color: #166534; font-size: 0.72rem; font-weight: 700; padding: 1px 6px; border-radius: 6px;">${activeInMod.length} activos</span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                            ${activeInMod.map(item => `<span style="background: #ffffff; border: 1px solid #cbd5e1; color: #354562; font-size: 0.73rem; padding: 2px 7px; border-radius: 6px;"><i class="fa-solid fa-check" style="color: #16a34a; font-size: 0.65rem; margin-right: 3px;"></i>${item}</span>`).join('')}
                        </div>
                    `;
                } else {
                    modCard.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 0.85rem; color: #94a3b8;"><i class="${modData.icon}" style="margin-right: 4px;"></i> ${modData.name}</span>
                            <span style="background: #fee2e2; color: #991b1b; font-size: 0.72rem; font-weight: 700; padding: 1px 6px; border-radius: 6px;">Sin acceso</span>
                        </div>
                    `;
                }
                listContainer.appendChild(modCard);
            }
        }

        document.getElementById('modalDetalleUsuario').style.display = 'flex';
    }

    function cerrarModalDetalleUsuario() {
        document.getElementById('modalDetalleUsuario').style.display = 'none';
    }

    function irAPermisosDesdeDetalle() {
        if (!selectedUserForDetail) return;
        cerrarModalDetalleUsuario();
        abrirModalPermisos(
            selectedUserForDetail.id,
            selectedUserForDetail.name,
            selectedUserForDetail.role || 'vendedor',
            selectedUserPerms,
            selectedUserIsAdmin
        );
    }

    function irAEditarDesdeDetalle() {
        if (!selectedUserForDetail) return;
        cerrarModalDetalleUsuario();
        abrirModalEditarUsuario(selectedUserForDetail);
    }
</script>
@endif

@if($activeTab === 'base_datos')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let restoreTipo = null;
    let restoreFilename = null;
    let restoreFileObj = null;

    function abrirModalRestaurar(tipo, filename = null) {
        restoreTipo = tipo;
        restoreFilename = filename;
        restoreFileObj = null;

        const origenEl = document.getElementById('modalRestoreOrigenTexto');
        if (tipo === 'existente') {
            origenEl.textContent = `Archivo en servidor: ${filename}`;
        } else {
            const fileInput = document.getElementById('inputArchivoSqlRestore');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Por favor selecciona un archivo .sql primero.');
                return;
            }
            restoreFileObj = fileInput.files[0];
            origenEl.textContent = `Archivo subido: ${restoreFileObj.name} (${(restoreFileObj.size / 1024).toFixed(2)} KB)`;
        }

        const inputPalabra = document.getElementById('inputPalabraRestaurar');
        inputPalabra.value = '';
        const btn = document.getElementById('btnEjecutarRestauracion');
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.innerHTML = '<i class="fa-solid fa-skull-crossbones"></i> Confirmar Restauración Total';

        document.getElementById('modalConfirmarRestauracion').style.display = 'flex';
        setTimeout(() => inputPalabra.focus(), 100);
    }

    function cerrarModalRestaurar() {
        document.getElementById('modalConfirmarRestauracion').style.display = 'none';
    }

    function iniciarProcesoRestauracion(origen) {
        if (origen === 'subir') {
            const fileInput = document.getElementById('inputArchivoSqlRestore');
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Selecciona un archivo .sql desde tu computadora.');
                fileInput.click();
                return;
            }
            abrirModalRestaurar('subir');
        } else {
            const selectEl = document.getElementById('selectBackupServidor');
            if (!selectEl.value) {
                alert('Selecciona una copia de seguridad de la lista.');
                selectEl.focus();
                return;
            }
            abrirModalRestaurar('existente', selectEl.value);
        }
    }

    function validarPalabraRestaurar() {
        const val = document.getElementById('inputPalabraRestaurar').value.trim().toUpperCase();
        const btn = document.getElementById('btnEjecutarRestauracion');
        if (val === 'RESTAURAR') {
            btn.disabled = false;
            btn.style.opacity = '1';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        }
    }

    function ejecutarRestauracion() {
        const inputPalabra = document.getElementById('inputPalabraRestaurar');
        if (inputPalabra.value.trim().toUpperCase() !== 'RESTAURAR') {
            alert('Debes escribir exactamente la palabra RESTAURAR.');
            return;
        }

        const btn = document.getElementById('btnEjecutarRestauracion');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Restaurando Base de Datos... No cierre la ventana';

        const formData = new FormData();
        formData.append('confirmacion', 'RESTAURAR');
        formData.append('tipo_origen', restoreTipo);

        if (restoreTipo === 'subir') {
            formData.append('archivo_sql', restoreFileObj);
        } else {
            formData.append('backup_existente', restoreFilename);
        }

        fetch('{{ route("configuracion.database.restore") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ Error al restaurar: ' + (data.error || 'No se pudo completar la restauración.'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-skull-crossbones"></i> Confirmar Restauración Total';
            }
        })
        .catch(err => {
            alert('❌ Error de red / servidor: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-skull-crossbones"></i> Confirmar Restauración Total';
        });
    }

    function eliminarRespaldo(filename) {
        if (!confirm(`¿Eliminar permanentemente la copia de seguridad "${filename}"?`)) return;

        fetch(`/configuracion/base-datos/eliminar/${encodeURIComponent(filename)}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => alert('Error: ' + err.message));
    }

    // ─── Modal de Reseteo a 0 (Limpieza Total de Fábrica) ───
    function abrirModalResetTotal() {
        const inputPalabra = document.getElementById('inputPalabraResetear');
        inputPalabra.value = '';
        const btn = document.getElementById('btnEjecutarResetTotal');
        btn.disabled = true;
        btn.style.opacity = '0.5';
        btn.innerHTML = '<i class="fa-solid fa-trash-can"></i> Confirmar Reseteo a 0';

        document.getElementById('modalConfirmarResetTotal').style.display = 'flex';
        setTimeout(() => inputPalabra.focus(), 100);
    }

    function cerrarModalResetTotal() {
        document.getElementById('modalConfirmarResetTotal').style.display = 'none';
    }

    function validarPalabraResetear() {
        const val = document.getElementById('inputPalabraResetear').value.trim().toUpperCase();
        const btn = document.getElementById('btnEjecutarResetTotal');
        if (val === 'RESETEAR') {
            btn.disabled = false;
            btn.style.opacity = '1';
        } else {
            btn.disabled = true;
            btn.style.opacity = '0.5';
        }
    }

    function ejecutarResetTotal() {
        const inputPalabra = document.getElementById('inputPalabraResetear');
        if (inputPalabra.value.trim().toUpperCase() !== 'RESETEAR') {
            alert('Debes escribir exactamente la palabra RESETEAR.');
            return;
        }

        const btn = document.getElementById('btnEjecutarResetTotal');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Reseteando base de datos a 0... No cierre la ventana';

        fetch('{{ route("configuracion.database.reset") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                confirmacion: 'RESETEAR'
            }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ Error al resetear: ' + (data.error || 'No se pudo completar el reseteo.'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-trash-can"></i> Confirmar Reseteo a 0';
            }
        })
        .catch(err => {
            alert('❌ Error de red / servidor: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-trash-can"></i> Confirmar Reseteo a 0';
        });
    }
</script>
@endif
@endsection