<x-app-layout
    :logo-url="asset('images/logo_alone.png')"
    alt-text="Offside Club"
>
    @section('navigation-title', __('views.settings.marketplace'))

    @php
        $themeMode = auth()->user()->theme_mode ?? 'auto';
        $isDark = $themeMode === 'dark' || ($themeMode === 'auto' && false);

        // Colores dinámicos
        $bgPrimary = $isDark ? '#0a2e2c' : '#f5f5f5';
        $bgSecondary = $isDark ? '#0f3d3a' : '#ffffff';
        $bgTertiary = $isDark ? '#1a524e' : '#f9f9f9';
        $textPrimary = $isDark ? '#ffffff' : '#333333';
        $textSecondary = $isDark ? '#b0b0b0' : '#666666';
        $borderColor = $isDark ? '#2a4a47' : '#e0e0e0';
        $accentColor = '#00deb0';
        $accentDark = '#17b796';
    @endphp

    <div class="min-h-screen p-1 md:p-6 pb-24" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }}; margin-top: 3.75rem;">
        <!-- Header -->
        <div style="margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">{{ __('views.settings.in_app_purchases') }}</h1>
            <p style="color: {{ $textSecondary }}; font-size: 0.95rem;">
                {{ __('views.settings.in_app_purchases_desc') }}
            </p>
        </div>

        <!-- Coming Soon Banner -->
        <div style="background: linear-gradient(135deg, {{ $accentColor }}cc, {{ $accentDark }}cc); padding: 3rem 2rem; border-radius: 16px; margin-bottom: 2rem; border: 1px solid {{ $accentColor }}; position: relative; overflow: hidden; text-align: center;">
            <div style="position: relative; z-index: 2;">
                <i class="fas fa-rocket" style="font-size: 48px; color: #000; margin-bottom: 1rem; display: block;"></i>
                <h2 style="font-size: 1.75rem; font-weight: 700; color: #000; margin-bottom: 1rem;">{{ __('messages.coming_soon') }}</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                    <!-- Battle Pass -->
                    <div style="background: rgba(0, 0, 0, 0.2); padding: 1.5rem; border-radius: 12px; backdrop-filter: blur(10px);">
                        <i class="fas fa-shield-alt" style="font-size: 32px; color: #000; margin-bottom: 0.5rem; display: block;"></i>
                        <h3 style="font-size: 1rem; font-weight: 600; color: #000; margin-bottom: 0.5rem;">{{ __('messages.battle_pass') ?? 'Pases de Batalla' }}</h3>
                        <p style="color: rgba(0,0,0,0.7); font-size: 0.85rem;">Desbloquea recompensas exclusivas y contenido premium</p>
                    </div>

                    <!-- Unlock Features -->
                    <div style="background: rgba(0, 0, 0, 0.2); padding: 1.5rem; border-radius: 12px; backdrop-filter: blur(10px);">
                        <i class="fas fa-unlock" style="font-size: 32px; color: #000; margin-bottom: 0.5rem; display: block;"></i>
                        <h3 style="font-size: 1rem; font-weight: 600; color: #000; margin-bottom: 0.5rem;">{{ __('messages.unlock_features') ?? 'Características' }}</h3>
                        <p style="color: rgba(0,0,0,0.7); font-size: 0.85rem;">Desbloquea herramientas y análisis avanzados</p>
                    </div>

                    <!-- More Features -->
                    <div style="background: rgba(0, 0, 0, 0.2); padding: 1.5rem; border-radius: 12px; backdrop-filter: blur(10px);">
                        <i class="fas fa-star" style="font-size: 32px; color: #000; margin-bottom: 0.5rem; display: block;"></i>
                        <h3 style="font-size: 1rem; font-weight: 600; color: #000; margin-bottom: 0.5rem;">{{ __('messages.special_items') ?? 'Artículos Especiales' }}</h3>
                        <p style="color: rgba(0,0,0,0.7); font-size: 0.85rem;">Acceso exclusivo a contenido limitado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menú inferior fijo -->
    <x-layout.bottom-navigation active-item="mercados" />

    <!-- Modal de Feedback -->
    <x-feedback-modal />

</x-app-layout>

