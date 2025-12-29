<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->user()->theme_mode === 'light' ? 'light-theme' : 'dark-theme' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Offside Club') }}</title>
    <link rel="manifest" href="/manifest.json">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/png" sizes="192x192" href="/favicons/android-chrome-192x192.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Estilos para el tema claro */
        .light-theme {
            --bg-primary: #ffffff;
            --bg-secondary: #dbfef8;
            --text-primary: #1a1a1a;
            --text-secondary: #003b2f;
            --border-color: #dce2e1;
            --hover-bg: #f3f4f6;
            --accent: #e0850c;
            --green-main: #69bfb6;
            --green-dark: #003b2f;
            --gray-secondary: #dce2e1;
            --card-bg: rgba(255,255,255,0.85);
            --modal-bg: rgba(255,255,255,0.95);
        }

        /* Estilos para el tema oscuro (default) */
        .dark-theme {
            --bg-primary: rgb(0 46 44 / var(--tw-bg-opacity, 1));
            --bg-secondary: rgb(0 46 44 / var(--tw-bg-opacity, 2));
            --text-primary: #ffffff;
            --text-secondary: #e2e2e2;
            --border-color: #404040;
            --hover-bg: #333333;
            --bg-offside-dark: rgb(0 46 44 / var(--tw-bg-opacity, 1));
        }

        /* Aplicar variables CSS */
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        .bg-offside-dark {
            background-color: var(--bg-primary);
        }

        .text-offside-light {
            color: var(--text-secondary);
        }

        .border-offside-primary {
            border-color: var(--border-color);
        }

        .hover\:bg-white\/10:hover {
            background-color: var(--hover-bg);
        }

        .bg-white\/10 {
            background-color: var(--bg-secondary);
        }

        /* Ajustes específicos para el tema claro */
        .light-theme .bg-offside-primary {
            background-color: var(--green-main) !important;
        }

        .light-theme .text-offside-primary {
            color: var(--green-main) !important;
        }

        .light-theme .border-offside-primary {
            border-color: var(--green-main) !important;
        }

        .light-theme .hover\:bg-offside-primary\/90:hover {
            background-color: #FF6B35;
            opacity: 0.9;
        }

        .light-theme .bg-offside-dark {
            background-color: var(--card-bg) !important;
        }

        .light-theme .text-accent, .light-theme .bg-accent {
            color: var(--accent) !important;
            background-color: var(--accent) !important;
        }

        .light-theme .bg-gray-100, .light-theme .bg-gray-200 {
            background-color: var(--gray-secondary) !important;
        }

        .light-theme .text-white {
            color: var(--text-primary);
        }

        .light-theme .text-gray-400 {
            color: var(--text-secondary);
        }

        .light-theme .bg-black {
            background-color: var(--bg-primary);
        }

        .light-theme .bg-gray-900 {
            background-color: var(--bg-secondary);
        }

        .light-theme .border-gray-700 {
            border-color: var(--border-color);
        }

        .light-theme .hover\:bg-gray-700:hover {
            background-color: var(--hover-bg);
        }

        .light-theme .hover\:text-gray-300:hover {
            color: var(--text-primary);
        }

        /* Tarjetas, formularios y modales claros */
        .light-theme .bg-offside-dark,
        .light-theme .card,
        .light-theme .form {
            background-color: #fff !important;
            border: 1px solid var(--border-color) !important;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.04);
        }
        .light-theme .modal {
            background-color: var(--modal-bg) !important;
        }

        /* Inputs y selects en tema claro */
        .light-theme input,
        .light-theme select,
        .light-theme textarea {
            background-color: #fff !important;
            color: var(--text-primary) !important;
            border: 1px solid var(--border-color) !important;
        }
        .light-theme input:focus,
        .light-theme select:focus,
        .light-theme textarea:focus {
            border-color: var(--green-main) !important;
            box-shadow: 0 0 0 2px rgba(4,178,160,0.15);
        }
        /* Header navegación sólido */
        .light-theme .navigation-header, .light-theme nav, .light-theme .navbar {
            background-color: #fff !important;
            box-shadow: 0 2px 8px 0 rgba(0,0,0,0.04);
        }

        /* ==================== Header - Always Light ==================== */
        .header {
            background: #fff !important;
            border-bottom: 1px solid #e0e0e0 !important;
            color: #333 !important;
            z-index: 900 !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 10px 16px !important;
            height: 60px !important;
            width: 100% !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
        }

        /* Dark theme header */
        .dark-theme .header {
            background: #0f3d3a !important;
            border-bottom-color: #2a4a47 !important;
            color: #fff !important;
        }

        .header-logo {
            height: 32px !important;
            width: auto !important;
            max-width: 90px !important;
            object-fit: contain !important;
            flex-shrink: 0 !important;
        }

        .logo-container {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex-shrink: 0 !important;
        }

        .header-profile-btn {
            position: relative !important;
            flex-shrink: 0 !important;
            margin-left: auto !important;
        }

        .profile-btn {
            width: 44px !important;
            height: 44px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            overflow: hidden !important;
            border: 2px solid #e0e0e0 !important;
            background: #f5f5f5 !important;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dark-theme .profile-btn {
            border-color: #2a4a47 !important;
            background: #1a524e !important;
        }

        .profile-avatar {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 50% !important;
        }

        .profile-avatar-placeholder {
            width: 100% !important;
            height: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: linear-gradient(135deg, #00857B, #00B5A5) !important;
            color: white !important;
            font-weight: bold;
            font-size: 18px;
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: white !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000 !important;
            min-width: 180px;
        }

        .dark-theme .profile-dropdown {
            background: #0f3d3a !important;
            border-color: #2a4a47 !important;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #333 !important;
            text-decoration: none;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f0f0f0;
        }

        .dark-theme .dropdown-item {
            color: #b0b0b0 !important;
            border-bottom-color: #1a524e !important;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item:hover {
            background: #f5f5f5;
            color: #00857B;
        }

        .dark-theme .dropdown-item:hover {
            background: #1a524e;
            color: #00deb0;
        }

        .dropdown-item i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>
<body class="h-full font-sans antialiased">
    <div class="min-h-screen">
        <x-layout.header-profile
            :logo-url="$logoUrl ?? asset('images/logo_alone.png')"
            :alt-text="$altText ?? 'Offside Club'"
        />

        <!-- Page Content -->
        <main @class([
            'mt-12' => session('success') || session('error')
        ])>
            @if (session('success'))
                <div class="max-w-7xl mx-auto mt-12 px-4 sm:px-6 lg:px-8">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto mt-12 px-4 sm:px-6 lg:px-8">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    <!-- Scripts -->
    <script>
        // Función para cerrar modales al hacer clic fuera de ellos
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-backdrop')) {
                e.target.classList.add('hidden');
            }
        });

        // Banner de nueva versión disponible
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'NEW_VERSION_AVAILABLE') {
                    mostrarBannerNuevaVersion();
                }
            });
        }

        function mostrarBannerNuevaVersion() {
            if (document.getElementById('update-banner')) return;
            const banner = document.createElement('div');
            banner.id = 'update-banner';
            banner.style.position = 'fixed';
            banner.style.bottom = '0';
            banner.style.left = '0';
            banner.style.right = '0';
            banner.style.background = '#e0850c';
            banner.style.color = '#fff';
            banner.style.padding = '16px';
            banner.style.textAlign = 'center';
            banner.style.zIndex = '9999';
            banner.innerHTML = `
                ¡Nueva versión disponible!
                <button id="reload-btn" style="margin-left:16px;padding:8px 16px;background:#fff;color:#e0850c;border:none;border-radius:4px;cursor:pointer;font-weight:bold;">
                    Actualizar ahora
                </button>
            `;
            document.body.appendChild(banner);

            document.getElementById('reload-btn').onclick = function() {
                if (navigator.serviceWorker.controller) {
                    navigator.serviceWorker.controller.postMessage({ type: 'SKIP_WAITING' });
                }
                window.location.reload();
            };
        }
    </script>

    <!-- Navigation Module (UX Redesign) -->
    <script src="{{ asset('js/common/navigation.js') }}" defer></script>
</body>
</html>
