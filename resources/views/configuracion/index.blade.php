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
    <a href="{{ route('configuracion.index', ['tab' => 'sistema']) }}" class="config-tab {{ $activeTab === 'sistema' ? 'is-active' : '' }}">Sistema y Red</a>
</div>

@if($activeTab === 'general')
    <div class="config-grid">
        <form class="card" action="{{ route('configuracion.general.update') }}" method="POST">
            @csrf
            <div class="hero-kicker"><i class="fa-solid fa-sliders"></i> General</div>
            <h2 class="hero-title" style="font-size: 1.5rem; margin-top: 0.25rem;">Parámetros del negocio</h2>

            <div class="form-grid" style="margin-top: 1.25rem;">
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
        $localIP = '127.0.0.1';
        $output = shell_exec('ipconfig');
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

        if ($localIP === '127.0.0.1') {
            $localIP = getHostByName(getHostName());
        }

        // Detectar Repositorio GitHub vinculado
        $gitCmd = null;
        $testCmd = @shell_exec('git --version 2>&1');
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
                $remote = shell_exec($gitCmd . ' remote get-url origin 2>&1');
                if ($remote && !str_contains($remote, 'fatal') && !str_contains(strtolower($remote), 'no se reconoce')) {
                    $gitRepoUrl = trim($remote);
                }
                $branch = shell_exec($gitCmd . ' branch --show-current 2>&1');
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
            
            <div style="margin-top: 1.5rem;">
                <a href="{{ route('configuracion.sistema.downloadUpdater') }}" class="btn-modern btn-accent" style="width: auto; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-download"></i> Descargar Script Actualizador (.bat)
                </a>
            </div>
        </div>
    </div>
@else
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
@endif

<script src="{{ asset('js/configuracion.js') }}"></script>
@endsection