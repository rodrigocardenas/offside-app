@php
    // Detectar tema: para login, usar tema claro por defecto ya que el usuario no está autenticado
    $themeMode = 'light';
    $isDark = false;
    $layout = 'mobile-light-layout';

    // Colores dinámicos
    $bgCard = '#fff';
    $bgInput = '#fff';
    $textColor = '#333';
    $labelColor = '#333';
    $borderColor = '#e0e0e0';
    $accentColor = '#00deb0';
    $accentDark = '#17b796';
@endphp

<x-dynamic-layout :layout="$layout">
    <div class="main-container" style="padding: 20px 16px; display: flex; flex-direction: column; min-height: 100vh; justify-content: center;">
        <div style="max-width: 414px; width: 100%; margin: 0 auto;">
            <!-- Logo y Título -->
            <div style="text-align: center; margin-bottom: 32px;">
                <div style="margin-bottom: 16px;">
                    <img src="{{ asset('images/logo_alone.png') }}" alt="Offside Club" style="width: 60px; height: 60px; margin: 0 auto; display: block;">
                </div>
                <h1 style="font-size: 28px; font-weight: 700; color: {{ $textColor }}; margin: 0 0 8px 0;">Offside Club</h1>
                <p style="color: #999; font-size: 14px; margin: 0;">{{ __('auth.app_description') }}</p>
            </div>

            <!-- Formulario de Login -->
            <div style="background: {{ $bgCard }}; border-radius: 12px; padding: 20px; border: 1px solid {{ $borderColor }}; margin-bottom: 24px;">
                <h2 style="font-size: 20px; font-weight: 600; color: {{ $textColor }}; margin: 0 0 20px 0; text-align: center;">
                    {{ __('auth.login') }}
                </h2>

                <form method="POST" action="{{ route('login') }}" style="display: flex; flex-direction: column; gap: 16px;">
                    @csrf

                    <!-- Campo de Timezone Oculto -->
                    <input type="hidden" id="timezone" name="timezone" value="">

                    <!-- Campo Username/Nickname -->
                    <div>
                        <label for="name" style="display: block; font-weight: 600; color: {{ $labelColor }}; font-size: 14px; margin-bottom: 8px;">
                            <i class="fas fa-user" style="color: {{ $accentColor }}; margin-right: 6px;"></i>
                            {{ __('auth.nickname_label') }}
                        </label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            style="width: 100%; border: 1px solid {{ $borderColor }}; border-radius: 8px; padding: 12px; font-size: 14px; color: {{ $textColor }}; background: {{ $bgInput }}; box-sizing: border-box; font-family: inherit;"
                            placeholder="tu_nickname">
                        @error('name')
                            <p style="color: #dc3545; font-size: 12px; margin-top: 6px; margin: 6px 0 0 0;">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Botón Iniciar Sesión -->
                    <button
                        type="submit"
                        style="width: 100%; padding: 12px 16px; background: linear-gradient(135deg, {{ $accentDark }}, {{ $accentColor }}); color: white; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 8px;">
                        <i class="fas fa-sign-in-alt"></i>
                        {{ __('auth.login_button') }}
                    </button>

                    <!-- Divisor O -->
                    <div style="display: flex; align-items: center; gap: 12px; margin: 16px 0;">
                        <div style="flex: 1; height: 1px; background: {{ $borderColor }};"></div>
                        <span style="color: #999; font-size: 12px; font-weight: 500;">O CONTINÚA CON</span>
                        <div style="flex: 1; height: 1px; background: {{ $borderColor }};"></div>
                    </div>

                    <!-- Botón Google OAuth -->
                    <a href="{{ route('auth.google') }}" style="width: 100%; padding: 12px 16px; background: #fff; color: #333; border: 1px solid {{ $borderColor }}; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                        <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Google
                    </a>

                    <!-- Enlaces alternativos -->
                    @if (Route::has('register'))
                        <div style="text-align: center; margin-top: 12px; font-size: 13px;">
                            <a href="{{ route('register') }}" style="color: {{ $accentColor }}; text-decoration: none; transition: color 0.3s ease;">
                                {{ __('auth.register_new_account') }}
                            </a>
                        </div>
                    @endif
                </form>
            </div>

            <!-- Información Adicional -->
            <div style="background: rgba(0, 222, 176, 0.08); border-left: 4px solid {{ $accentColor }}; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                <p style="color: {{ $labelColor }}; font-size: 13px; margin: 0; line-height: 1.5;">
                    <i class="fas fa-info-circle" style="color: {{ $accentColor }}; margin-right: 8px;"></i>
                    <strong>¿Nuevo en Offside Club?</strong> Crea tu cuenta para empezar a hacer predicciones y compite con otros usuarios.
                </p>
            </div>

            <!-- Modal de instalación PWA -->
            <div id="pwaInstallModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: none; align-items: flex-end; justify-content: center; z-index: 9999;">
                <div style="background: {{ $bgCard }}; width: 100%; max-width: 414px; border-radius: 20px 20px 0 0; padding: 24px; color: {{ $textColor }};">
                    <button onclick="closePwaModal()" style="position: absolute; top: 16px; right: 16px; background: none; border: none; font-size: 24px; color: #999; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>

                    <div style="text-align: center;">
                        <img src="{{ asset('images/logo_alone.png') }}" alt="Offside Club" style="width: 50px; height: 50px; margin: 0 auto 16px; display: block;">
                        <h3 style="font-size: 18px; font-weight: 600; color: {{ $textColor }}; margin: 0 0 12px 0;">¡Instala Offside Club!</h3>
                        <p style="color: #999; font-size: 13px; margin: 0 0 16px 0;">Accede desde cualquier lugar sin conexión a internet</p>

                        <!-- Instrucciones para Android -->
                        <div id="androidInstructions" style="display: none;">
                            <button id="installPwaButton" style="width: 100%; background: linear-gradient(135deg, {{ $accentDark }}, {{ $accentColor }}); color: white; padding: 12px 16px; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; margin-bottom: 12px;">
                                Instalar App
                            </button>
                        </div>

                        <!-- Instrucciones para iOS -->
                        <div id="iosInstructions" style="display: none; text-align: left;">
                            <p style="color: #999; font-size: 13px; margin-bottom: 12px;">Para instalar en tu iPhone o iPad:</p>
                            <ol style="color: #999; font-size: 13px; margin: 0; padding-left: 20px; text-align: left;">
                                <li style="margin-bottom: 6px;">Toca el botón <strong>Compartir</strong> en Safari</li>
                                <li style="margin-bottom: 6px;">Desplázate y selecciona <strong>Añadir a la pantalla de inicio</strong></li>
                                <li>Toca <strong>Añadir</strong> para confirmar</li>
                            </ol>
                        </div>

                        <button onclick="closePwaModal()" style="width: 100%; background: none; color: {{ $accentColor }}; padding: 12px; border: 1px solid {{ $borderColor }}; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 12px;">
                            Más tarde
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .main-container input[type="text"]:focus,
        .main-container input[type="password"]:focus {
            outline: none;
            border-color: {{ $accentColor }} !important;
            box-shadow: 0 0 0 3px rgba(0, 222, 176, 0.1);
        }

        .main-container button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 222, 176, 0.3);
        }

        .main-container button[type="submit"]:active {
            transform: translateY(0);
        }

        .main-container a[href*="/auth/google"] {
            transition: all 0.3s ease;
        }

        .main-container a[href*="/auth/google"]:hover {
            background: #f8f8f8 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-color: {{ $accentColor }} !important;
        }

        .main-container a[href*="/auth/google"]:active {
            transform: scale(0.98);
        }

        #pwaInstallModal {
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 16px;
            }
        }
    </style>

    <script>
        // Variables globales
        let deferredPrompt;
        const pwaInstallModal = document.getElementById('pwaInstallModal');
        const installPwaButton = document.getElementById('installPwaButton');
        const androidInstructions = document.getElementById('androidInstructions');
        const iosInstructions = document.getElementById('iosInstructions');

        // Función global para cerrar el modal
        function closePwaModal() {
            if (pwaInstallModal) {
                pwaInstallModal.style.display = 'none';
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
            // if (pwaInstallModal) {
            //     pwaInstallModal.style.display = 'flex';
            //     showDeviceInstructions();
            // }
        }

        // Detectar el dispositivo y mostrar las instrucciones correspondientes
        function showDeviceInstructions() {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isAndroid = /Android/.test(navigator.userAgent);

            if (isIOS) {
                iosInstructions.style.display = 'block';
                androidInstructions.style.display = 'none';
            } else if (isAndroid) {
                androidInstructions.style.display = 'block';
                iosInstructions.style.display = 'none';
            }
        }

        // Escuchar el evento beforeinstallprompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;

            // Actualizar las instrucciones si el modal está visible
            if (pwaInstallModal.style.display === 'flex') {
                showDeviceInstructions();
            }
        });

        window.addEventListener('load', function() {
            // Verificar si es la primera visita y no está instalada
            if (!localStorage.getItem('pwaInstallShown') && !isPWAInstalled()) {
                showInstallModal();
            }

            // Manejar la instalación
            if (installPwaButton) {
                installPwaButton.addEventListener('click', async () => {
                    if (deferredPrompt) {
                        try {
                            deferredPrompt.prompt();
                            const { outcome } = await deferredPrompt.userChoice;
                            if (outcome === 'accepted') {
                                console.log('Usuario aceptó la instalación');
                            }
                        } catch (error) {
                            console.error('Error durante la instalación:', error);
                        }
                        deferredPrompt = null;
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

            // 🌍 Capturar y setear el timezone del dispositivo
            document.addEventListener('DOMContentLoaded', function() {
                try {
                    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                    const timezoneInput = document.getElementById('timezone');
                    if (timezoneInput) {
                        timezoneInput.value = timezone;
                        console.log('Timezone del dispositivo capturado:', timezone);
                    }
                } catch (e) {
                    console.warn('No se pudo detectar el timezone:', e);
                }
            });

            // Registrar el Service Worker
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registrado con éxito');
                    })
                    .catch(function(error) {
                        console.error('Error al registrar el ServiceWorker:', error);
                    });
            }
        });
    </script>
</x-dynamic-layout>
