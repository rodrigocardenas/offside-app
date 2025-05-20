<x-app-layout>
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6">
        <div class="max-w-md mx-auto">
            <div class="bg-white bg-opacity-10 rounded-xl p-6 backdrop-blur-sm" style="margin-top: 50px;">
                <div class="flex items-center justify-center mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-center text-offside-light mb-6">Iniciar sesión</h2>

                <!-- Modal de instalación PWA -->
                <div id="pwaInstallModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4" style="margin-top: 100px;">
                    <div class="bg-white rounded-2xl w-full max-w-md p-6 relative max-h-[90vh] overflow-y-auto">
                        <button onclick="closePwaModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div class="text-center">
                            <img src="{{ asset('images/logo_white_bg.png') }}" alt="Offside Club" class="mx-auto mb-4 h-10 rounded-lg shadow-lg">
                            <h3 class="text-xl font-bold text-gray-900 mb-3">¡Instala Offside Club!</h3>

                            <!-- Instrucciones para Android -->
                            <div id="androidInstructions" class="hidden">
                                <p class="text-gray-600 mb-4">Instala la aplicación directamente en tu dispositivo Android para una mejor experiencia.</p>
                                <button id="installPwaButton" class="w-full bg-[#FF6B35] text-white px-6 py-3 rounded-lg font-semibold hover:bg-[#FF6B35]/90 transition-colors mb-4">
                                    Instalar App
                                </button>
                            </div>

                            <!-- Instrucciones para iOS -->
                            <div id="iosInstructions" class="hidden">
                                <p class="text-gray-600 mb-4">Para instalar en tu iPhone o iPad:</p>
                                <ol class="text-left text-gray-600 mb-4 space-y-2">
                                    <li>1. Toca el botón <span class="font-semibold">Compartir</span> en Safari</li>
                                    <li>2. Desplázate y selecciona <span class="font-semibold">Añadir a la pantalla de inicio</span></li>
                                    <li>3. Toca <span class="font-semibold">Añadir</span> para confirmar</li>
                                </ol>
                            </div>

                            <!-- Instrucciones para otros dispositivos -->
                            <div id="otherInstructions" class="hidden">
                                <p class="text-gray-600 mb-4">Para instalar la aplicación:</p>
                                <ol class="text-left text-gray-600 mb-4 space-y-2">
                                    <li>1. Abre el menú de opciones de tu navegador</li>
                                    <li>2. Busca la opción "Instalar" o "Añadir a aplicaciones"</li>
                                    <li>3. Sigue las instrucciones en pantalla</li>
                                </ol>
                            </div>

                            <button onclick="closePwaModal()" class="w-full text-gray-600 hover:text-gray-800 mt-2">
                                Más tarde
                            </button>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-400 mb-2">Nombre de usuario</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-offside-primary @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- <div class="flex items-center justify-between mt-6">
                        <a href="{{ route('register') }}" class="text-sm text-offside-light hover:text-white transition-colors">
                            ¿No tienes cuenta?
                        </a>
                    </div> --}}

                    <button type="submit" class="w-full bg-gradient-to-r from-orange-500 to-orange-400 text-white py-2 rounded-lg font-semibold hover:from-orange-600 hover:to-orange-500 transition-all">
                        Iniciar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let deferredPrompt;
        const pwaInstallModal = document.getElementById('pwaInstallModal');
        const installPwaButton = document.getElementById('installPwaButton');
        const androidInstructions = document.getElementById('androidInstructions');
        const iosInstructions = document.getElementById('iosInstructions');
        const otherInstructions = document.getElementById('otherInstructions');

        // Función global para cerrar el modal
        function closePwaModal() {
            console.log('Cerrando modal PWA');
            if (pwaInstallModal) {
                pwaInstallModal.classList.add('hidden');
            }
            localStorage.setItem('pwaInstallShown', 'true');
        }

        // Función para verificar si la PWA ya está instalada
        function isPWAInstalled() {
            return window.matchMedia('(display-mode: standalone)').matches ||
                   window.navigator.standalone === true;
        }

        // Función para mostrar el modal de instalación
        function showInstallModal() {
            if (pwaInstallModal) {
                console.log('Mostrando modal PWA');
                pwaInstallModal.classList.remove('hidden');
                showDeviceInstructions();
            }
        }

        // Detectar el dispositivo y mostrar las instrucciones correspondientes
        function showDeviceInstructions() {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isAndroid = /Android/.test(navigator.userAgent);

            console.log('Detectando dispositivo:', { isIOS, isAndroid, deferredPrompt });

            if (isIOS) {
                iosInstructions.classList.remove('hidden');
                androidInstructions.classList.add('hidden');
                otherInstructions.classList.add('hidden');
            } else if (isAndroid && deferredPrompt) {
                androidInstructions.classList.remove('hidden');
                iosInstructions.classList.add('hidden');
                otherInstructions.classList.add('hidden');
            } else {
                otherInstructions.classList.remove('hidden');
                androidInstructions.classList.add('hidden');
                iosInstructions.classList.add('hidden');
            }
        }

        // Escuchar el evento beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('Evento beforeinstallprompt capturado');
            e.preventDefault();
            deferredPrompt = e;

            // Actualizar las instrucciones si el modal está visible
            if (!pwaInstallModal.classList.contains('hidden')) {
                showDeviceInstructions();
            }
        });

        window.addEventListener('load', function() {
            console.log('Página cargada, inicializando PWA...');

            // Verificar si es la primera visita y no está instalada
            if (!localStorage.getItem('pwaInstallShown') && !isPWAInstalled()) {
                console.log('Primera visita detectada y PWA no instalada');
                showInstallModal();
            }

            // Manejar la instalación
            if (installPwaButton) {
                installPwaButton.addEventListener('click', async () => {
                    console.log('Botón de instalación clickeado');
                    if (deferredPrompt) {
                        console.log('Mostrando prompt de instalación');
                        try {
                            deferredPrompt.prompt();
                            const { outcome } = await deferredPrompt.userChoice;
                            console.log('Resultado de la instalación:', outcome);
                            if (outcome === 'accepted') {
                                console.log('Usuario aceptó la instalación');
                                // Esperar a que la instalación se complete
                                setTimeout(() => {
                                    if (isPWAInstalled()) {
                                        console.log('PWA instalada correctamente');
                                    }
                                }, 1000);
                            } else {
                                console.log('Usuario rechazó la instalación');
                            }
                        } catch (error) {
                            console.error('Error durante la instalación:', error);
                        }
                        deferredPrompt = null;
                    } else {
                        console.log('No hay prompt de instalación disponible');
                        // Mostrar instrucciones alternativas
                        alert('Para instalar la aplicación, por favor usa el menú de opciones de tu navegador.');
                    }
                    closePwaModal();
                });
            }

            // Cerrar modal al hacer clic fuera
            if (pwaInstallModal) {
                pwaInstallModal.addEventListener('click', (e) => {
                    if (e.target === pwaInstallModal) {
                        closePwaModal();
                    }
                });
            }

            // Registrar el Service Worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registrado con éxito:', registration);
                    })
                    .catch(function(error) {
                        console.error('Error al registrar el ServiceWorker:', error);
                    });
            }
        });
    </script>
</x-app-layout>
