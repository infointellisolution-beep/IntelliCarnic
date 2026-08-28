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
    $userModal = $users->firstWhere('id', (int) old('user_id'));
    $userModalAction = $userModal ? route('configuracion.users.update', $userModal) : route('configuracion.users.store');
    $userModalMethod = $userModal ? 'PUT' : '';
@endphp
    <div class="config-grid config-grid-users">
        <form class="card" action="{{ route('configuracion.users.store') }}" method="POST">
            @csrf
            <div class="hero-kicker"><i class="fa-solid fa-user-plus"></i> Usuarios</div>
            <h2 class="hero-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Crear usuario</h2>

            <div class="form-grid" style="margin-top: 1.25rem;">
                <div class="input-group">
                    <label>Nombre</label>
                    <input type="text" class="input-modern" name="name" required>
                </div>
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" class="input-modern" name="email" required>
                </div>
                <div class="input-group">
                    <label>Contraseña</label>
                    <input type="password" class="input-modern" name="password" required>
                </div>
                <div class="input-group">
                    <label>Confirmar contraseña</label>
                    <input type="password" class="input-modern" name="password_confirmation" required>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                <button type="submit" class="btn-modern btn-accent" style="width: auto;">Guardar usuario</button>
            </div>
        </form>

        <div class="card">
            <div class="hero-kicker"><i class="fa-solid fa-users"></i> Usuarios registrados</div>
            <div class="familias-grid" style="margin-top: 1rem;">
                @forelse($users as $user)
                    @php($isProtectedAdmin = strtolower($user->email) === 'admin@gmail.com')
                    <div class="familia-card">
                        <div class="familia-card-head">
                            <div>
                                <div class="familia-card-title">{{ $user->name }}</div>
                                <div class="familia-card-desc" style="margin-top: 0.35rem;">{{ $user->email }}</div>
                            </div>
                            <span class="familia-card-badge">{{ $isProtectedAdmin ? 'Administrador' : 'ID '.$user->id }}</span>
                        </div>
                        <div class="familia-card-desc">Credenciales activas para acceso al sistema.</div>
                        <div class="flex-gap" style="justify-content: flex-end; margin-top: 0.85rem; gap: 0.5rem;">
                            <button type="button" class="btn-modern btn-secondary js-user-edit" style="width: auto;" data-user='@json($user)'>Editar</button>
                            @if(! $isProtectedAdmin)
                                <form action="{{ route('configuracion.users.destroy', $user) }}" method="POST" onsubmit="return confirm('¿Eliminar este usuario?');" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-modern btn-secondary" style="width: auto; color: var(--danger);">Eliminar</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="stock-empty">Todavía no hay usuarios registrados.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="user-modal" aria-hidden="true" @if($userModal) data-open="true" @endif>
        <div class="modal-backdrop js-user-modal-close" role="presentation"></div>
        <div class="modal-dialog modal-dialog-details" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
            <div class="modal-header">
                <div>
                    <div class="hero-kicker"><i class="fa-solid fa-user-pen"></i> Usuarios</div>
                    <h2 class="hero-title" id="user-modal-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Editar usuario</h2>
                </div>
                <button type="button" class="modal-close js-user-modal-close" aria-label="Cerrar modal">&times;</button>
            </div>

            <form id="user-modal-form" action="{{ $userModalAction }}" method="POST" class="modal-form">
                @csrf
                <input type="hidden" name="user_id" id="user-modal-id" value="{{ old('user_id', $userModal->id ?? '') }}">
                <input type="hidden" name="_method" id="user-modal-method" value="{{ $userModalMethod }}">

                <div class="modal-body">
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Nombre</label>
                            <input type="text" class="input-modern" name="name" id="user-modal-name" value="{{ old('name', $userModal->name ?? '') }}" required>
                        </div>
                        <div class="input-group">
                            <label>Email</label>
                            <input type="email" class="input-modern" name="email" id="user-modal-email" value="{{ old('email', $userModal->email ?? '') }}" required>
                        </div>
                        <div class="input-group">
                            <label>Contraseña</label>
                            <input type="password" class="input-modern" name="password" id="user-modal-password" placeholder="Deja vacío para conservarla">
                        </div>
                        <div class="input-group">
                            <label>Confirmar contraseña</label>
                            <input type="password" class="input-modern" name="password_confirmation" id="user-modal-password-confirmation" placeholder="Repite la nueva contraseña">
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <div style="color: var(--text-muted); font-size: 0.9rem;">La contraseña solo cambia si escribes una nueva.</div>
                    <div class="flex-gap" style="width: auto;">
                        <button type="button" class="btn-modern btn-secondary js-user-modal-close" style="width: auto;">Cancelar</button>
                        <button type="submit" class="btn-modern btn-accent" style="width: auto;">Guardar usuario</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@elseif($activeTab === 'sucursales')
    <div class="config-grid">
        {{-- Card 1: Configuración Sincronización Cloud con Hostinger --}}
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <div class="hero-kicker"><i class="fa-solid fa-cloud"></i> Sincronización en la Nube (Cloud Hub)</div>
                    <h2 class="hero-title" style="font-size: 1.35rem; margin-top: 0.25rem;">Servidor de Enlace en Hostinger</h2>
                </div>
                <button type="button" onclick="testConexionCloud()" class="btn-modern btn-secondary" id="btnTestCloud" style="width: auto;">
                    <i class="fa-solid fa-satellite-dish"></i> Probar Conexión
                </button>
            </div>

            <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1rem;">
                Conecta este servidor local con tu Buzón Central en Hostinger para intercambiar transferencias con las demás sucursales por internet.
            </p>

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
                    <input type="password" id="inputCloudToken" class="input-modern"
                           placeholder="Clave secreta"
                           value="{{ $settings['cloud_sync_token'] ?? 'IntelliCarnic_Sync_2026_Key' }}">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                <button type="button" onclick="guardarConfigCloud()" class="btn-modern btn-accent" style="width: auto;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Configuración Cloud
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

    function guardarConfigCloud() {
        const endpoint = document.getElementById('inputCloudEndpoint')?.value.trim();
        const token = document.getElementById('inputCloudToken')?.value.trim();

        fetch('{{ route("transferencias.api.guardar-config-cloud") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ cloud_sync_endpoint: endpoint, cloud_sync_token: token }),
        })
        .then(r => r.json())
        .then(() => alert('Configuración de sincronización en la nube guardada exitosamente.'))
        .catch(err => alert('Error: ' + err.message));
    }

    function testConexionCloud() {
        const btn = document.getElementById('btnTestCloud');
        const div = document.getElementById('estadoCloud');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Probando...';

        fetch('{{ route("transferencias.api.test-conexion") }}', {
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
            div.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Error: ' + err.message;
        });
    }
</script>
@endif
@endsection