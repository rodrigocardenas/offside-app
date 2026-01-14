<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso denegado - {{ config('app.name') }}</title>
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-offside-light dark:bg-offside-dark transition-colors duration-200">
    <div class="min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full text-center">
            <!-- Icono de tarjeta roja -->
            <div class="mb-8">
                <svg class="mx-auto h-24 w-24 text-red-500 dark:text-red-400" fill="currentColor" viewBox="0 0 24 24">
                    <rect x="8" y="4" width="8" height="16" rx="1" />
                    <path d="M9 8 L15 8 M9 12 L15 12" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
            </div>

            <!-- Error 403 -->
            <h1 class="text-6xl sm:text-8xl font-bold text-red-500 dark:text-red-400 mb-4">
                403
            </h1>

            <!-- Mensaje principal -->
            <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800 dark:text-white mb-4">
                ¡Tarjeta roja!
            </h2>

            <p class="text-base sm:text-lg text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
                No tienes permiso para acceder a esta zona del campo.
                <span class="block mt-2">{{ $exception->getMessage() ?: 'Contacta con tu entrenador (administrador) si crees que esto es un error.' }}</span>
            </p>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ url('/') }}"
                   class="inline-flex items-center justify-center px-6 py-3 bg-offside-primary hover:bg-offside-dark text-white font-medium rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Volver al inicio
                </a>

                <button onclick="window.history.back()"
                        class="inline-flex items-center justify-center px-6 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-offside-primary dark:text-offside-secondary font-medium rounded-lg border-2 border-offside-primary dark:border-offside-secondary transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver atrás
                </button>
            </div>

            <!-- Enlaces adicionales -->
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap justify-center gap-6 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-offside-primary dark:text-offside-secondary hover:underline">
                            Mi Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-offside-primary dark:text-offside-secondary hover:underline">
                            Iniciar sesión
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</body>
</html>
