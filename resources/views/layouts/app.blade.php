<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/navigation.js'])
    @stack('scripts')
</head>
<body class="h-full font-sans antialiased bg-black text-white">
    <div class="min-h-screen">
        @auth
            <div class="bg-gray-900 py-4 px-6 flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    @if(auth()->user()->hasRole('admin'))
                        <a href="{{ route('admin.dashboard') }}" class="text-white hover:text-gray-300">
                            Admin Panel
                        </a>
                    @endif
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-white">{{ auth()->user()->unique_id }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="ml-4">
                        @csrf
                        <button type="submit" class="text-white hover:text-gray-300">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
        @endauth
        {{ $slot }}
    
    @stack('styles')
    @stack('scripts')
    </div>
</body>
</html>
