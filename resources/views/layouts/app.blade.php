<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'IntelliCarnic')</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/custom.css">
    <link rel="stylesheet" href="/css/vender.css">
</head>
<body>

    @if(request()->routeIs('login'))
        @yield('content')
    @else
        <div class="app-layout">
            <!-- Sidebar Navigation -->
            @php
                $appSettings = \App\Models\Setting::values();
                $empresaNombre = !empty($appSettings['empresa_nombre']) ? $appSettings['empresa_nombre'] : 'IntelliCarnic';
                $empresaLogo = !empty($appSettings['empresa_logo']) ? asset('storage/'.$appSettings['empresa_logo']) : null;
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
                    <li>
                        <a href="{{ route('articulos.index') }}" class="{{ request()->routeIs('articulos.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-box"></i> Artículos
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('vender.normal') }}" class="{{ request()->routeIs('vender.normal') ? 'active' : '' }}">
                            <i class="fa-solid fa-desktop"></i> Venta Normal
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('vender.tactil') }}" class="{{ request()->routeIs('vender.tactil') ? 'active' : '' }}">
                            <i class="fa-solid fa-hand-pointer"></i> TPV Táctil
                        </a>
                    </li>
                </ul>
                <ul class="nav-links" style="margin-top: auto;">
                    <li>
                        <a href="{{ route('configuracion.index') }}" class="{{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-gear"></i> Configuración
                        </a>
                    </li>
                </ul>
                <div class="sidebar-footer">
                    <div class="sidebar-status">Entorno de trabajo</div>
                    <div class="sidebar-user-card">
                        <div class="avatar">D</div>
                        <div>
                            <div class="sidebar-user-name">Usuario</div>
                            <div class="sidebar-user-role">Administrador</div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content Area -->
            <main class="main-content">
                <!-- Top Bar -->
                <header class="top-bar">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <button id="sidebarToggle" style="background: none; border: none; font-size: 1.25rem; color: var(--text-main); cursor: pointer;">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        @hasSection('header-actions')
                            <div class="top-bar-actions">
                                @yield('header-actions')
                            </div>
                        @endif
                    </div>
                    <div class="user-profile">
                        <span>{{ auth()->user()->name ?? 'Usuario' }}</span>
                        <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}</div>
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
    </script>
</body>
</html>
