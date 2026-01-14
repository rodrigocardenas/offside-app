<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sesión expirada - {{ config('app.name') }}</title>
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
            <!-- Icono de cronómetro -->
            <div class="mb-8">
                <svg class="mx-auto h-24 w-24 text-offside-primary dark:text-offside-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <!-- Error 419 -->
            <h1 class="text-6xl sm:text-8xl font-bold text-offside-primary dark:text-offside-secondary mb-4">
                419
            </h1>

            <!-- Mensaje principal -->
            <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800 dark:text-white mb-4">
                ¡Fin del tiempo reglamentario!
            </h2>

            <p class="text-base sm:text-lg text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
                Tu sesión ha expirado por seguridad.
                <span class="block mt-2">No te preocupes, solo necesitas recargar la página para continuar.</span>
            </p>

            <!-- Botones de acción -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="window.location.reload()"
                        class="inline-flex items-center justify-center px-6 py-3 bg-offside-primary hover:bg-offside-dark text-white font-medium rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Recargar página
                </button>

                <a href="{{ url('/') }}"
                   class="inline-flex items-center justify-center px-6 py-3 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-offside-primary dark:text-offside-secondary font-medium rounded-lg border-2 border-offside-primary dark:border-offside-secondary transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Ir al inicio
                </a>
            </div>

            <!-- Información adicional -->
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-500 dark:text-blue-400 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-left text-blue-700 dark:text-blue-300">
                            <strong>¿Por qué pasa esto?</strong><br>
                            Por tu seguridad, las sesiones expiran después de un tiempo de inactividad. Esto protege tu cuenta y tu información personal.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
