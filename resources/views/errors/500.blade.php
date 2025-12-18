<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error Interno del Servidor - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            <div class="text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>

                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                    Error Interno del Servidor
                </h1>

                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    {{ $message ?? 'Ha ocurrido un error interno del servidor. Por favor, inténtalo de nuevo más tarde.' }}
                </p>

                @if(isset($error_code))
                    <div class="bg-gray-50 dark:bg-gray-700 rounded p-3 mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Código de error: <code class="bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-xs">{{ $error_code }}</code>
                        </p>
                    </div>
                @endif

                @if(isset($trace_id))
                    <div class="bg-gray-50 dark:bg-gray-700 rounded p-3 mb-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            ID de seguimiento: <code class="bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-xs">{{ $trace_id }}</code>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            Por favor, proporciona este ID si contactas al soporte técnico.
                        </p>
                    </div>
                @endif

                @if(config('app.debug'))
                    @if(isset($context) && is_array($context) && count($context) > 0)
                        <div class="bg-yellow-50 dark:bg-yellow-900 rounded p-3 mb-4 text-left">
                            <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-100 mb-2">Contexto (Desarrollo):</p>
                            <pre class="text-xs text-yellow-700 dark:text-yellow-200 overflow-x-auto">{{ json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    @if(isset($trace))
                        <div class="bg-red-50 dark:bg-red-900 rounded p-3 mb-4 text-left">
                            <p class="text-sm font-semibold text-red-800 dark:text-red-100 mb-2">Stack Trace (Desarrollo):</p>
                            <pre class="text-xs text-red-700 dark:text-red-200 overflow-x-auto">{{ $trace }}</pre>
                        </div>
                    @endif
                @endif

                <div class="flex space-x-4 justify-center">
                    <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        Ir al Inicio
                    </a>
                    <button onclick="window.location.reload()" class="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-600 focus:bg-gray-400 dark:focus:bg-gray-600 active:bg-gray-500 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        Recargar Página
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
