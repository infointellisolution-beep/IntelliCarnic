<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Handheld Terminal') - {{ config('app.name', 'IntelliCarnic') }}</title>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --accent: #d97706;
            --success: #10b981;
            --danger: #ef4444;
            --bg-dark: #0f172a;
            --card-bg: #1e293b;
            --border-color: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            touch-action: manipulation;
        }

        /* BARRA SUPERIOR COMPACTA (HANDHELD HEADER) */
        .hh-header {
            background: #1e293b;
            border-bottom: 1px solid var(--border-color);
            padding: 0.6rem 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .hh-title {
            font-size: 1rem;
            font-weight: 800;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .hh-title i {
            color: #38bdf8;
        }

        .hh-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hh-btn-icon {
            background: #334155;
            color: var(--text-main);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            text-decoration: none;
        }

        .hh-btn-icon:active {
            background: #475569;
        }

        /* CONTENEDOR PRINCIPAL HANDHELD */
        .hh-content {
            flex: 1;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
        }

        /* TARJETAS TÁCTILES GRANDES (TOUCH CARDS) */
        .touch-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem 1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: var(--text-main);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
            transition: transform 0.1s ease, border-color 0.1s ease;
        }

        .touch-card:active {
            transform: scale(0.98);
            border-color: var(--primary);
        }

        .touch-card-icon {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            flex-shrink: 0;
        }

        .touch-card-title {
            font-size: 1.15rem;
            font-weight: 800;
            margin-bottom: 0.2rem;
        }

        .touch-card-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.2;
        }

        /* FORMULARIOS Y BOTONES ADAPTADOS A HANDHELD */
        .hh-input {
            width: 100%;
            background: #0f172a;
            border: 1.5px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 0.85rem;
            color: white;
            font-size: 1rem;
            outline: none;
            margin-bottom: 0.75rem;
        }

        .hh-input:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.25);
        }

        .hh-btn {
            width: 100%;
            padding: 0.85rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            color: white;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }

        .hh-btn:active {
            opacity: 0.9;
        }

        .hh-btn-success { background: var(--success); }
        .hh-btn-accent { background: var(--accent); }
        .hh-btn-danger { background: var(--danger); }
        .hh-btn-secondary { background: #334155; }

        /* ALERTAS DE NOTIFICACIÓN */
        .hh-alert {
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hh-alert-success { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid #10b981; }
        .hh-alert-danger { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; }

        /* BADGES */
        .hh-badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .hh-badge-info { background: #0284c7; color: white; }
        .hh-badge-success { background: #059669; color: white; }
    </style>
</head>
<body>
    <!-- BARRA SUPERIOR -->
    <header class="hh-header">
        <div class="hh-title">
            <i class="fa-solid fa-mobile-screen-button"></i>
            <span>HANDHELD</span>
        </div>
        <div class="hh-actions">
            <a href="{{ route('handheld.index') }}" class="hh-btn-icon" title="Menú Principal Handheld">
                <i class="fa-solid fa-house"></i>
            </a>
            <form action="{{ route('logout') }}" method="POST" style="margin: 0; display: inline;">
                @csrf
                <button type="submit" class="hh-btn-icon" title="Cerrar Sesión" style="color: #ef4444;">
                    <i class="fa-solid fa-power-off"></i>
                </button>
            </form>
        </div>
    </header>

    <!-- CONTENIDO DE LA PÁGINA -->
    <main class="hh-content">
        @if(session('status'))
            <div class="hh-alert hh-alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="hh-alert hh-alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
