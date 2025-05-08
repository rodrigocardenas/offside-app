<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- PWA -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#2d3748">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-offside-192x192.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/navigation.js'])
    <script>
        // Registrar el Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('{{ asset('sw.js') }}')
                    .then(registration => {
                        console.log('ServiceWorker registrado con éxito');
                        // Verificar si la aplicación ya está instalada
                        checkInstallationStatus();
                    })
                    .catch(error => {
                        console.log('Error al registrar el ServiceWorker:', error);
                    });
            });
        }

        // Verificar si la aplicación ya está instalada
        function checkInstallationStatus() {
            // Para navegadores móviles
            if (window.matchMedia('(display-mode: standalone)').matches) {
                console.log('La aplicación ya está instalada');
                return true;
            }
            // Para Chrome en escritorio
            if (window.navigator.standalone === true) {
                console.log('La aplicación ya está instalada');
                return true;
            }
            return false;
        }

        // Variables globales
        let deferredPrompt;
        const installButton = document.getElementById('installButton');
        const installButtonNav = document.getElementById('installButtonNav');
        const installButtonContainer = document.getElementById('installButtonContainer');
        const installModal = document.getElementById('installModal');

        // Mostrar el botón de instalación si es compatible
        function showInstallPromotion() {
            // Mostrar el botón en la barra de navegación
            if (installButtonContainer) {
                installButtonContainer.style.display = 'flex';
            }
            // Mostrar el modal (como respaldo)
            if (installModal) {
                installModal.classList.remove('hidden');
            }
        }

        // Manejar el evento beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('Evento beforeinstallprompt activado');
            // Prevenir que el navegador muestre automáticamente el prompt
            e.preventDefault();
            // Guardar el evento para usarlo después
            deferredPrompt = e;
            // Mostrar el botón de instalación
            showInstallPromotion();
        });

        // Verificar si el evento beforeinstallprompt no se disparó
        // (algunos navegadores no lo disparan en ciertas condiciones)
        window.addEventListener('load', () => {
            // Esperar un momento para dar tiempo a que se dispare el evento beforeinstallprompt
            setTimeout(() => {
                if (!deferredPrompt && !checkInstallationStatus()) {
                    console.log('Mostrando botón de instalación manual');
                    showInstallPromotion();
                }
            }, 3000);
        });

        // Función para instalar la aplicación
        function installApp() {
            if (deferredPrompt) {
                console.log('Mostrando prompt de instalación');
                // Mostrar el prompt de instalación
                deferredPrompt.prompt();
                // Esperar a que el usuario responda al prompt
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('Usuario aceptó la instalación');
                        // Ocultar el botón después de la instalación
                        if (installButtonContainer) {
                            installButtonContainer.style.display = 'none';
                        }
                    } else {
                        console.log('Usuario rechazó la instalación');
                    }
                    // Limpiar el deferredPrompt
                    deferredPrompt = null;
                    // Ocultar el modal de instalación
                    closeInstallModal();
                });
            } else {
                // Si no hay deferredPrompt, redirigir a la documentación de instalación
                const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
                if (isIOS) {
                    // Instrucciones para iOS
                    alert('Para instalar esta aplicación en iOS, toca el botón compartir y luego "Añadir a la pantalla de inicio"');
                } else {
                    // Instrucciones para Android/otros
                    alert('Para instalar esta aplicación, por favor usa el menú de opciones de tu navegador.');
                }
            }
        }

        // Cerrar el modal
        function closeInstallModal() {
            if (installModal) {
                installModal.classList.add('hidden');
            }
        }
    </script>
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
    
    <!-- Modal de instalación -->
    <div id="installModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-offside-dark rounded-lg p-6 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-white">Instalar Offside Club</h3>
                <button onclick="closeInstallModal()" class="text-offside-light hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <p class="text-offside-light mb-6">¿Deseas instalar Offside Club en tu dispositivo para un mejor acceso?</p>
            <div class="flex justify-end space-x-4">
                <button onclick="closeInstallModal()" class="px-4 py-2 bg-offside-dark border border-offside-primary text-offside-primary rounded-md hover:bg-offside-primary hover:text-white transition-colors">
                    Ahora no
                </button>
                <button id="installButton" onclick="installApp()" class="px-4 py-2 bg-offside-primary text-white rounded-md hover:bg-opacity-90 transition-colors">
                    Instalar
                </button>
            </div>
        </div>
    </div>
    
    @stack('modals')
    @livewireScripts
    <script src="{{ asset('sw-update.js') }}"></script>
    
    <script>
        // Manejar clic en el botón de instalación de la barra de navegación
        document.addEventListener('DOMContentLoaded', function() {
            const installButtonNav = document.getElementById('installButtonNav');
            if (installButtonNav) {
                installButtonNav.addEventListener('click', function() {
                    installApp();
                });
            }
            
            // Ocultar el botón si la aplicación ya está instalada
            if (window.matchMedia('(display-mode: standalone)').matches) {
                const installButtonContainer = document.getElementById('installButtonContainer');
                if (installButtonContainer) {
                    installButtonContainer.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>
