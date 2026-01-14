<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página no encontrada - {{ config('app.name') }}</title>
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
            <!-- Icono de balón de fútbol -->
            <div class="mb-8">
                <svg class="mx-auto h-24 w-24 text-offside-primary dark:text-offside-secondary" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M12 2 L12 5 M12 19 L12 22 M2 12 L5 12 M19 12 L22 12" stroke="currentColor" stroke-width="1.5"/>
                </svg>
            </div>

            <!-- Error 404 -->
            <h1 class="text-6xl sm:text-8xl font-bold text-offside-primary dark:text-offside-secondary mb-4">
                404
            </h1>

            <!-- Mensaje principal -->
            <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800 dark:text-white mb-4">
                ¡Fuera de juego!
            </h2>

            <p class="text-base sm:text-lg text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
                Lo sentimos, la página que buscas no existe o ha sido movida.
                <span class="block mt-2">¡Pero no te preocupes! Podemos llevarte de vuelta al campo.</span>
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
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">¿Necesitas ayuda?</p>
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
