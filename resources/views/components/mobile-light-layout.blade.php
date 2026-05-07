<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
    <meta name="user-id" content="{{ auth()->id() }}">
    @endauth

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
            background: #f5f5f5;
            color: #333;
        }
    </style>
</head>
<body>
    {{ $slot }}

    @stack('scripts')

    <!-- Firebase Push Notifications -->
    <script src="{{ asset('js/firebase-messaging-native.js') }}"></script>
    @auth
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.initializePushNotifications === 'function') {
                window.initializePushNotifications();
            }
        });
    </script>
    @endauth

    <!-- Toast Initialization Script -->
    <script>
        const initToasts = () => {
            if (window.__toastsInitialized) {
                return;
            }
            window.__toastsInitialized = true;

            console.log('🔔 initToasts ejecutándose en mobile-light-layout');
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
