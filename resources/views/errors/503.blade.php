<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Servicio no disponible - {{ config('app.name') }}</title>
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
            <!-- Icono de mantenimiento -->
            <div class="mb-8">
                <svg class="mx-auto h-24 w-24 text-offside-primary dark:text-offside-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>

            <!-- Error 503 -->
            <h1 class="text-6xl sm:text-8xl font-bold text-offside-primary dark:text-offside-secondary mb-4">
                503
            </h1>

            <!-- Mensaje principal -->
            <h2 class="text-2xl sm:text-3xl font-semibold text-gray-800 dark:text-white mb-4">
                ¡Tiempo de descanso!
            </h2>

            <p class="text-base sm:text-lg text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
                Estamos realizando tareas de mantenimiento para mejorar tu experiencia.
                <span class="block mt-2">Volveremos enseguida. ¡Gracias por tu paciencia!</span>
            </p>

            @if(isset($exception) && $exception->getMessage())
                <div class="bg-offside-light dark:bg-gray-700 rounded-lg p-4 mb-8">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        {{ $exception->getMessage() }}
                    </p>
                </div>
            @endif

            <!-- Botón de recarga -->
            <div class="flex justify-center">
                <button onclick="window.location.reload()"
                        class="inline-flex items-center justify-center px-6 py-3 bg-offside-primary hover:bg-offside-dark text-white font-medium rounded-lg transition-colors duration-200 shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Intentar de nuevo
                </button>
            </div>

            <!-- Información adicional -->
            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-3">
                        ¿Qué está pasando?
                    </h3>
                    <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2 text-left">
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-offside-primary dark:text-offside-secondary mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Estamos mejorando el rendimiento
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-offside-primary dark:text-offside-secondary mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Aplicando actualizaciones de seguridad
                        </li>
                        <li class="flex items-start">
                            <svg class="w-5 h-5 text-offside-primary dark:text-offside-secondary mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Tu información está segura
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
