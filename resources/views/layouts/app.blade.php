<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'IntelliCarnic')</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/vender.css') }}">
    <!-- Librería para generar códigos de barras -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>

    @auth
    <!-- Detección Dinámica de Pantalla Móvil y Redirección a Handheld -->
    <script>
        (function() {
            function checkMobileScreen() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('desktop') === '1') {
                    sessionStorage.setItem('force_desktop', '1');
                    sessionStorage.removeItem('force_handheld');
                } else if (urlParams.get('desktop') === '0') {
                    sessionStorage.removeItem('force_desktop');
                }

                if (urlParams.get('handheld') === '1') {
                    sessionStorage.setItem('force_handheld', '1');
                    sessionStorage.removeItem('force_desktop');
                }

                const isMobileScreen = window.innerWidth <= 768;
                const forceDesktop = sessionStorage.getItem('force_desktop') === '1' || urlParams.get('desktop') === '1';
                
                if (isMobileScreen && !forceDesktop) {
                    const path = window.location.pathname;
                    if (!path.startsWith('/handheld') && !path.startsWith('/vender/ticket') && !path.startsWith('/clientes/abono') && !path.startsWith('/caja/ticket-cierre') && !path.startsWith('/login') && !path.startsWith('/dev')) {
                        if (path.startsWith('/compras')) {
                            window.location.replace("{{ route('handheld.compras') }}");
                        } else if (path.startsWith('/vender')) {
                            window.location.replace("{{ route('handheld.tpv') }}");
                        } else {
                            window.location.replace("{{ route('handheld.index') }}");
                        }
                    }
                }
            }

            checkMobileScreen();
        })();
    </script>
    @endauth
</head>
<body>

    @if(request()->routeIs('login') || request()->is('login') || !auth()->check())
        @yield('content')
    @else
        <div class="app-layout">
            <!-- Sidebar Navigation -->
            @php
                $appSettings = \App\Models\Setting::values();
                $empresaNombre = !empty($appSettings['empresa_nombre']) ? $appSettings['empresa_nombre'] : 'IntelliCarnic';
                $empresaLogo = !empty($appSettings['empresa_logo']) ? asset('storage/'.$appSettings['empresa_logo']) : null;
                $authUser = auth()->user();
            @endphp
            <nav class="sidebar">
                <div class="sidebar-header">
                    <div class="sidebar-mark">
                        @if($empresaLogo)
                            <img src="{{ $empresaLogo }}" alt="Logo" style="max-width: 100%; max-height: 100%; border-radius: 4px; object-fit: contain;">
                        @else
                            <i class="fa-solid fa-cube"></i>
                        @endif
                    </div>
                    <div>
                        <div class="sidebar-brand">{{ $empresaNombre }}</div>
                        <div class="sidebar-subtitle">Sistema profesional</div>
                    </div>
                </div>
                <ul class="nav-links">
                    @if(!$authUser || $authUser->hasPermission('dashboard.ver'))
                    <li>
                        <a href="{{ route('dashboard.index') }}" class="{{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-chart-line"></i> Dashboard
                        </a>
                    </li>
                    @endif

                    @if(!$authUser || $authUser->hasModuleAccess('articulos'))
                    <li>
                        <a href="{{ route('articulos.index') }}" class="{{ request()->routeIs('articulos.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-box"></i> Artículos
                        </a>
                    </li>
                    @endif

                    @if(!$authUser || $authUser->hasPermission('ventas.crear_normal'))
                    <li>
                        <a href="{{ route('vender.normal') }}" class="{{ request()->routeIs('vender.normal') ? 'active' : '' }}">
                            <i class="fa-solid fa-desktop"></i> Venta Normal
                        </a>
                    </li>
                    @endif

                    @if(!$authUser || $authUser->hasPermission('ventas.crear_tactil'))
                    <li>
                        <a href="{{ route('vender.tactil') }}" class="{{ request()->routeIs('vender.tactil') ? 'active' : '' }}">
                            <i class="fa-solid fa-hand-pointer"></i> TPV Táctil
                        </a>
                    </li>
                    @endif

                    @if(!$authUser || $authUser->hasModuleAccess('caja'))
                    <li>
                        <a href="{{ route('caja.index') }}" class="{{ request()->routeIs('caja.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-cash-register"></i> Control de Caja
                        </a>
                    </li>
                    @endif

                    @if(!$authUser || $authUser->hasPermission('compras.ver') || $authUser->hasPermission('compras.crear'))
                    <li>
                        <a href="{{ route('compras.index') }}" class="{{ request()->routeIs('compras.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-truck-ramp-box"></i> Compras
                        </a>
                    </li>
                    @endif

                    @if(!$authUser || $authUser->hasModuleAccess('transferencias'))
                    <li>
                        <a href="{{ route('transferencias.index') }}" class="{{ request()->routeIs('transferencias.*') ? 'active' : '' }}" style="position: relative;">
                            <i class="fa-solid fa-right-left"></i> Transferencias
                            @php
                                $sucActual = \App\Models\Sucursal::actual();
                                $pendCount = $sucActual ? \App\Models\Transferencia::where('sucursal_destino_id', $sucActual->id)->where('estado', 'en_transito')->count() : 0;
                            @endphp
                            @if($pendCount > 0)
                                <span style="background:#ef4444;color:#fff;border-radius:50%;font-size:0.65rem;font-weight:700;padding:2px 6px;margin-left:6px;">{{ $pendCount }}</span>
                            @endif
                        </a>
                    </li>
                    @endif

                    @if(!$authUser || $authUser->hasPermission('proveedores.ver'))
                    <li>
                        <a href="{{ route('proveedores.index') }}" class="{{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-truck-field"></i> Proveedores
                        </a>
                    </li>
                    @endif

                    @if(!$authUser || $authUser->hasModuleAccess('clientes'))
                    <li>
                        <a href="{{ route('clientes.index') }}" class="{{ request()->routeIs('clientes.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-users"></i> Clientes
                        </a>
                    </li>
                    @endif

                    @if(!$authUser || $authUser->hasModuleAccess('reportes'))
                    <li>
                        <a href="{{ route('reportes.index') }}" class="{{ request()->routeIs('reportes.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-chart-pie"></i> Reportes
                        </a>
                    </li>
                    @endif
                </ul>

                @if(!$authUser || $authUser->hasModuleAccess('configuracion'))
                <ul class="nav-links" style="margin-top: auto;">
                    <li>
                        <a href="{{ route('configuracion.index') }}" class="{{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-gear"></i> Configuración
                        </a>
                    </li>
                </ul>
                @endif

                <div class="sidebar-footer">
                    <div class="sidebar-status">Entorno de trabajo</div>
                    <div class="sidebar-user-card">
                        <div class="avatar">{{ strtoupper(substr($authUser->name ?? 'U', 0, 1)) }}</div>
                        <div>
                            <div class="sidebar-user-name">{{ $authUser->name ?? 'Usuario' }}</div>
                            <div class="sidebar-user-role">{{ $authUser ? $authUser->getRoleName() : 'Invitado' }}</div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="main-content">
                <!-- Top Bar -->
                <header class="top-bar">
                    <div style="display: flex; align-items: center; gap: 1rem; flex: 1; min-width: 0;">
                        <button id="sidebarToggle" style="background: none; border: none; font-size: 1.25rem; color: var(--text-main); cursor: pointer; flex-shrink: 0;">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        @hasSection('header-actions')
                            <div class="top-bar-actions">
                                @yield('header-actions')
                            </div>
                        @endif
                    </div>
                    {{-- Perfil de Usuario con Menú Desplegable Interactivo (Siempre a la derecha) --}}
                    <div class="user-dropdown-container" style="position: relative; margin-left: auto; flex-shrink: 0;">
                        <button type="button" id="btnUserMenuToggle" onclick="toggleUserDropdown(event)" style="background: none; border: none; padding: 4px 6px; border-radius: var(--radius-md); cursor: pointer; display: flex; align-items: center; gap: 0.6rem; transition: background 0.15s ease;" onmouseover="this.style.background='rgba(0,0,0,0.04)'" onmouseout="this.style.background='transparent'">
                            <div style="text-align: right;">
                                <div style="font-weight: 700; font-size: 0.88rem; color: var(--text-main); line-height: 1.2;">{{ $authUser->name ?? 'Usuario' }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 600; display: flex; align-items: center; justify-content: flex-end; gap: 4px;">
                                    <span>{{ $authUser ? $authUser->getRoleName() : '' }}</span>
                                    <i class="fa-solid fa-chevron-down" style="font-size: 0.65rem;"></i>
                                </div>
                            </div>
                            @php
                                $badge = $authUser ? $authUser->getRoleBadgeStyle() : ['bg' => '#f1f5f9', 'color' => '#475569', 'border' => '#cbd5e1'];
                            @endphp
                            <div class="avatar" style="width: 36px; height: 36px; min-width: 36px; background: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; border: 2px solid {{ $badge['border'] }}; font-weight: 800; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-sizing: border-box;">
                                {{ strtoupper(substr($authUser->name ?? 'U', 0, 1)) }}
                            </div>
                        </button>

                        {{-- Menú Desplegable --}}
                        <div id="userDropdownMenu" style="display: none; position: absolute; right: 0; top: calc(100% + 8px); width: 260px; max-width: calc(100vw - 2rem); background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1); z-index: 9999; overflow: hidden;">
                            {{-- Cabecera del Usuario --}}
                            <div style="padding: 1rem; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 0.75rem;">
                                <div class="avatar" style="width: 40px; height: 40px; min-width: 40px; background: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; border: 1.5px solid {{ $badge['border'] }}; font-weight: 800; font-size: 1rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    {{ strtoupper(substr($authUser->name ?? 'U', 0, 2)) }}
                                </div>
                                <div style="overflow: hidden; text-align: left;">
                                    <div style="font-weight: 800; font-size: 0.9rem; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $authUser->name ?? 'Usuario' }}</div>
                                    <div style="font-size: 0.76rem; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $authUser->email ?? '' }}</div>
                                    <span style="display: inline-block; margin-top: 4px; background: {{ $badge['bg'] }}; color: {{ $badge['color'] }}; padding: 1px 6px; border-radius: 6px; font-size: 0.7rem; font-weight: 700;">
                                        {{ $authUser ? $authUser->getRoleName() : '' }}
                                    </span>
                                </div>
                            </div>

                            {{-- Enlaces del Menú --}}
                            <div style="padding: 0.5rem;">
                                <a href="{{ route('configuracion.index', ['tab' => 'users']) }}" style="display: flex; align-items: center; gap: 0.65rem; padding: 0.6rem 0.75rem; border-radius: var(--radius-sm); color: var(--text-main); text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: background 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                    <i class="fa-solid fa-users-gear" style="color: #2563eb; width: 18px; text-align: center;"></i>
                                    <span>Gestión de Usuarios</span>
                                </a>
                                <a href="{{ route('configuracion.index', ['tab' => 'general']) }}" style="display: flex; align-items: center; gap: 0.65rem; padding: 0.6rem 0.75rem; border-radius: var(--radius-sm); color: var(--text-main); text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: background 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                    <i class="fa-solid fa-sliders" style="color: #475569; width: 18px; text-align: center;"></i>
                                    <span>Configuración General</span>
                                </a>
                                <a href="{{ route('handheld.index', ['handheld' => 1]) }}" onclick="sessionStorage.setItem('force_handheld', '1'); sessionStorage.removeItem('force_desktop');" style="display: flex; align-items: center; gap: 0.65rem; padding: 0.6rem 0.75rem; border-radius: var(--radius-sm); color: var(--text-main); text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: background 0.15s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                    <i class="fa-solid fa-mobile-screen-button" style="color: #d97706; width: 18px; text-align: center;"></i>
                                    <span>Terminal Handheld</span>
                                </a>
                            </div>

                            {{-- Botón Cerrar Sesión --}}
                            <div style="border-top: 1px solid var(--border-color); padding: 0.5rem; background: #fff;">
                                <form action="{{ route('logout') }}" method="POST" onsubmit="sessionStorage.removeItem('force_desktop'); sessionStorage.removeItem('force_handheld');" style="margin: 0;">
                                    @csrf
                                    <button type="submit" style="width: 100%; display: flex; align-items: center; gap: 0.65rem; padding: 0.6rem 0.75rem; border-radius: var(--radius-sm); border: none; background: none; color: #dc2626; font-size: 0.85rem; font-weight: 700; cursor: pointer; text-align: left; transition: background 0.15s ease;" onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='transparent'">
                                        <i class="fa-solid fa-arrow-right-from-bracket" style="width: 18px; text-align: center;"></i>
                                        <span>Cerrar Sesión</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <div class="content-area">
                    <h1 class="page-title">@yield('title')</h1>
                    @yield('content')
                </div>
            </main>
        </div>
    @endif

    {{-- Modals slot: rendered outside .content-area so position:fixed works correctly --}}
    @stack('modals')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebarToggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (toggleBtn && sidebar) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                });
            }
        });

        // Interceptor para redireccionar limpiamente a la pantalla de Login completa si expira la sesión
        (function() {
            const originalFetch = window.fetch;
            if (originalFetch) {
                window.fetch = async function(...args) {
                    const response = await originalFetch.apply(this, args);
                    if (response.status === 401 || (response.redirected && response.url.includes('/login'))) {
                        window.location.href = "{{ route('login') }}";
                    }
                    return response;
                };
            }
        })();

        // Menú desplegable interactivo del perfil de usuario
        function toggleUserDropdown(event) {
            event.stopPropagation();
            const menu = document.getElementById('userDropdownMenu');
            if (menu) {
                menu.style.display = (menu.style.display === 'none' || !menu.style.display) ? 'block' : 'none';
            }
        }

        document.addEventListener('click', function(event) {
            const container = document.querySelector('.user-dropdown-container');
            const menu = document.getElementById('userDropdownMenu');
            if (container && menu && !container.contains(event.target)) {
                menu.style.display = 'none';
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>
