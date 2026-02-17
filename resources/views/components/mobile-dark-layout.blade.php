<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Offside Club') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Component Styles -->
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">

    @vite(['resources/js/app.js'])

    @stack('styles')

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0a2e2c;
            color: #e0e0e0;
        }

        /* Dark Theme Color Variables */
        :root {
            --dark-bg-primary: #0a2e2c;
            --dark-bg-secondary: #0f3d3a;
            --dark-bg-tertiary: #1a524e;
            --dark-text-primary: #ffffff;
            --dark-text-secondary: #b0b0b0;
            --dark-border: #2a4a47;
            --dark-accent: #00deb0;
            --dark-accent-hover: #0eb88a;
            --dark-card: #1a3d3a;
        }

        /* Override Components CSS for Dark Theme */
        .main-container {
            background: var(--dark-bg-primary);
            color: var(--dark-text-primary);
        }

        .header {
            background: var(--dark-bg-secondary);
            border-bottom-color: var(--dark-border);
            color: var(--dark-text-primary);
        }

        .profile-btn:hover {
            border-color: var(--dark-accent);
            box-shadow: 0 2px 8px rgba(0, 222, 176, 0.2);
        }

        .profile-dropdown {
            background: var(--dark-bg-secondary);
            border-color: var(--dark-border);
            color: var(--dark-text-primary);
        }

        .dropdown-item {
            color: var(--dark-text-secondary);
            border-bottom-color: var(--dark-bg-tertiary);
        }

        .dropdown-item:hover {
            background: var(--dark-bg-tertiary);
            color: var(--dark-accent);
        }

        .stats-bar {
            background: var(--dark-bg-secondary);
            border-bottom-color: var(--dark-border);
        }

        .stat-item {
            color: var(--dark-text-secondary);
        }

        .stat-value {
            color: var(--dark-text-primary);
        }

        .notification-banner {
            background: rgba(255, 193, 7, 0.15);
            border-left-color: #ffc107;
            color: #ffb300;
        }

        .featured-match {
            background: var(--dark-bg-secondary);
            border-color: var(--dark-border);
        }

        .featured-title {
            color: var(--dark-text-primary);
        }

        .match-card {
            background: var(--dark-bg-tertiary);
            border-color: var(--dark-border);
        }

        .team-name {
            color: var(--dark-text-primary);
        }

        .match-league {
            color: var(--dark-text-secondary);
        }

        .section-title {
            color: var(--dark-text-primary);
        }

        .group-card {
            background: var(--dark-bg-secondary);
            border-color: var(--dark-border);
        }

        .group-card:hover {
            border-color: var(--dark-accent);
            box-shadow: 0 4px 8px rgba(0, 222, 176, 0.15);
        }

        .group-avatar {
            background: var(--dark-accent);
        }

        .group-info h3 {
            color: var(--dark-text-primary);
        }

        .group-stats {
            color: #00deb0;
        }

        .bottom-menu {
            background: var(--dark-bg-secondary);
            border-top-color: var(--dark-border);
        }

        .menu-item {
            color: var(--dark-text-secondary);
        }

        .menu-item.active {
            color: var(--dark-accent);
        }

        /* Settings Specific Dark Theme */
        .settings-tabs {
            background: var(--dark-bg-secondary);
            border-bottom-color: var(--dark-border);
        }

        .settings-tab-btn {
            background: var(--dark-bg-tertiary);
            border-color: var(--dark-border);
            color: var(--dark-text-secondary);
        }

        .settings-tab-btn:hover {
            background: var(--dark-bg-secondary);
            border-color: var(--dark-accent);
        }

        .settings-tab-btn.active {
            background: rgba(0, 222, 176, 0.15);
            border-color: var(--dark-accent);
            color: var(--dark-accent);
        }

        .settings-section {
            background: var(--dark-bg-secondary);
            border-color: var(--dark-border);
        }

        .theme-card {
            background: var(--dark-bg-tertiary);
            border-color: var(--dark-border);
            color: var(--dark-text-secondary);
        }

        .theme-radio:checked + .theme-card {
            border-color: var(--dark-accent);
            background: rgba(0, 222, 176, 0.1);
            box-shadow: 0 0 0 2px rgba(0, 222, 176, 0.2);
        }

        .coming-soon {
            background: var(--dark-bg-tertiary);
        }

        /* Profile Form Dark Theme */
        .profile-section {
            background: var(--dark-bg-primary);
        }

        .profile-section > div {
            background: var(--dark-bg-secondary);
            border-color: var(--dark-border);
        }

        .profile-section label {
            color: var(--dark-text-primary);
        }

        .profile-section input[type="text"],
        .profile-section input[type="email"],
        .profile-section select {
            background: var(--dark-bg-tertiary);
            border-color: var(--dark-border);
            color: var(--dark-text-primary);
        }

        .profile-section input[type="text"]:focus,
        .profile-section input[type="email"]:focus,
        .profile-section select:focus {
            border-color: var(--dark-accent);
            box-shadow: 0 0 0 3px rgba(0, 222, 176, 0.1);
        }

        .profile-section p {
            color: var(--dark-text-secondary);
        }

        .profile-section button[type="submit"] {
            background: linear-gradient(135deg, #00deb0, #00c9a3);
        }

        .profile-section button[type="submit"]:hover {
            background: linear-gradient(135deg, #0eb88a, #00b599);
            box-shadow: 0 4px 12px rgba(0, 222, 176, 0.3);
        }

        /* Alert Messages Dark */
        .alert-success {
            background: rgba(40, 167, 69, 0.15);
            border-left-color: #28a745;
            color: #5cdd6f;
        }
    </style>
</head>
<body>
    {{ $slot }}

    @stack('scripts')

    <!-- Toast Initialization Script -->
    <script>
        const initToasts = () => {
            if (window.__toastsInitialized) {
                return;
            }
            window.__toastsInitialized = true;

            console.log('🔔 initToasts ejecutándose en mobile-dark-layout');
            console.log('showSuccessToast disponible:', typeof window.showSuccessToast);

            @if(session('success'))
                console.log('✅ Session success detectada: "{{ addslashes(session('success')) }}"');
                if (typeof window.showSuccessToast === 'function') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    window.showSuccessToast('{{ addslashes(session('success')) }}');
                    console.log('✅ Toast mostrado');
                }
            @endif

            @if(session('error'))
                console.log('❌ Session error detectada: "{{ addslashes(session('error')) }}"');
                if (typeof window.showErrorToast === 'function') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    window.showErrorToast('{{ addslashes(session('error')) }}');
                }
            @endif

            @if(session('warning'))
                console.log('⚠️ Session warning detectada: "{{ addslashes(session('warning')) }}"');
                if (typeof window.showWarningToast === 'function') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    window.showWarningToast('{{ addslashes(session('warning')) }}');
                }
            @endif

            @if($errors->any())
                @foreach($errors->all() as $error)
                    if (typeof window.showErrorToast === 'function') {
                        window.showErrorToast('{{ addslashes($error) }}');
                    }
                @endforeach
            @endif
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(initToasts, 100);
            });
        } else {
            setTimeout(initToasts, 100);
        }
    </script>
</body>
</html>
