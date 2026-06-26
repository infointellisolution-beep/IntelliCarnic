<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SimplyGest')</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/css/custom.css">
</head>
<body>

    @if(request()->routeIs('login'))
        @yield('content')
    @else
        <div class="app-layout">
            <!-- Sidebar Navigation -->
            <nav class="sidebar">
                <div class="sidebar-header">
                    <div class="sidebar-mark">
                        <i class="fa-solid fa-cube"></i>
                    </div>
                    <div>
                        <div class="sidebar-brand">SimplyGest</div>
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
                        <a href="{{ route('configuracion.index') }}" class="{{ request()->routeIs('configuracion.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-gear"></i> Configuración
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('vender.index') }}" class="{{ request()->routeIs('vender.*') ? 'active' : '' }}">
                            <i class="fa-solid fa-cart-shopping"></i> Vender (TPV)
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
                    @hasSection('header-actions')
                        <div class="top-bar-actions">
                            @yield('header-actions')
                        </div>
                    @endif
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

</body>
</html>
