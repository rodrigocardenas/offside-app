<x-app-layout
    :logo-url="asset('images/logo_alone.png')"
    alt-text="Offside Club"
>
    @section('navigation-title', __('views.settings.marketplace'))

    @php
        $themeMode = auth()->user()->theme_mode ?? 'light';
        $isDark = $themeMode === 'dark';

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

    <div class="min-h-screen p-4 md:p-6 pb-24" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }}; margin-top: 3.75rem;">
        <!-- Header -->
        <div class="text-center mb-8">
            {{-- <h1 class="text-3xl md:text-4xl font-bold mb-4 text-{{ $isDark ? 'white' : 'gray-900' }}">{{ __('views.settings.in_app_purchases') }}</h1> --}}
            <p class="text-lg" style="color: {{ $textSecondary }};">
                {{ __('views.settings.in_app_purchases_desc') }}
            </p>
        </div>

        <!-- Coming Soon Banner -->
        <div class="bg-gradient-to-br from-green-400 to-blue-500 rounded-2xl p-8 mb-8 shadow-2xl relative overflow-hidden">
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            <div class="relative z-10 text-center">
                {{-- <div class="mb-6">
                    <i class="fas fa-rocket text-6xl text-black animate-bounce"></i>
                </div> --}}
                {{-- <h2 class="text-2xl md:text-3xl font-bold text-black mb-8">{{ __('messages.coming_soon') }}</h2> --}}

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-4">
                    <!-- Battle Pass -->
                    <div class="bg-white bg-opacity-20 backdrop-blur-md rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="text-center">
                            <i class="fas fa-shield-alt text-4xl text-black mb-4"></i>
                            <h3 class="text-xl font-semibold text-black mb-2">{{ __('messages.battle_pass') }}</h3>
                            <p class="text-black text-opacity-80">{{ __('messages.battle_pass_desc') }}</p>
                        </div>
                    </div>

                    <!-- Unlock Features -->
                    <div class="bg-white bg-opacity-20 backdrop-blur-md rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="text-center">
                            <i class="fas fa-unlock text-4xl text-black mb-4"></i>
                            <h3 class="text-xl font-semibold text-black mb-2">{{ __('messages.unlock_features') }}</h3>
                            <p class="text-black text-opacity-80">{{ __('messages.unlock_features_desc') }}</p>
                        </div>
                    </div>

                    <!-- Special Items -->
                    <div class="bg-white bg-opacity-20 backdrop-blur-md rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="text-center">
                            <i class="fas fa-star text-4xl text-black mb-4"></i>
                            <h3 class="text-xl font-semibold text-black mb-2">{{ __('messages.special_items') }}</h3>
                            <p class="text-black text-opacity-80">{{ __('messages.special_items_desc') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl p-8 shadow-2xl text-center">
            <h3 class="text-2xl md:text-3xl font-bold text-white mb-4">{{ __('messages.contact_businesses') }}</h3>
            <p class="text-white text-lg mb-6">{{ __('messages.contact_businesses_desc') }}</p>
            <a href="mailto:contact@offsideclub.com" class="inline-block bg-white text-purple-600 font-semibold py-3 px-6 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                <i class="fas fa-envelope mr-2"></i>{{ __('messages.contact_us') }}
            </a>
        </div>
    </div>

    <!-- Menú inferior fijo -->
    <x-layout.bottom-navigation active-item="mercados" />

    <!-- Modal de Feedback -->
    <x-feedback-modal />

</x-app-layout>

