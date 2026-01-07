<x-app-layout
    :logo-url="asset('images/logo_alone.png')"
    alt-text="Offside Club"
>
    @section('navigation-title', 'Tienda')

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
            <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">Marketplace</h1>
            <p style="color: {{ $textSecondary }}; font-size: 0.95rem;">
                Descubre productos deportivos de nuestros sponsors
            </p>
        </div>

        <!-- Featured Banner -->
        <div style="background: linear-gradient(135deg, {{ $accentColor }}cc, {{ $accentDark }}cc); padding: 2rem; border-radius: 16px; margin-bottom: 2rem; border: 1px solid {{ $accentColor }}; position: relative; overflow: hidden;">
            <div style="position: relative; z-index: 2;">
                <h2 style="font-size: 1.75rem; font-weight: 700; color: #000; margin-bottom: 0.5rem;">¡Nuevas Colecciones!</h2>
                <p style="color: rgba(0,0,0,0.8); font-size: 0.95rem; margin-bottom: 1rem;">
                    Productos exclusivos de nuestros partners deportivos
                </p>
                <button style="background: #000; color: {{ $accentColor }}; padding: 0.75rem 1.5rem; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; transition: all 0.3s ease;"
                    onmouseover="this.style.background='{{ $accentDark }}'; this.style.color='#000';"
                    onmouseout="this.style.background='#000'; this.style.color='{{ $accentColor }}';">
                    Explorar Ahora
                </button>
            </div>
        </div>

        <!-- Sponsors Section -->
        @if($sponsors->count() > 0)
        <div style="margin-bottom: 2rem;">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; color: {{ $textPrimary }};">Nuestros Sponsors</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem;">
                @foreach($sponsors as $sponsorName => $sponsorProducts)
                <div style="background: {{ $bgSecondary }}; padding: 1rem; border-radius: 12px; text-align: center; border: 1px solid {{ $borderColor }}; transition: all 0.3s ease; cursor: pointer;"
                    onmouseover="this.style.borderColor='{{ $accentColor }}'; this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.2)';"
                    onmouseout="this.style.borderColor='{{ $borderColor }}'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <div style="height: 50px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.5rem;">
                        <img src="{{ $sponsorProducts->first()['logo'] }}" alt="{{ $sponsorName }}" style="max-width: 90%; max-height: 90%; object-fit: contain;">
                    </div>
                    <p style="font-size: 0.85rem; font-weight: 600; color: {{ $textPrimary }};">{{ $sponsorName }}</p>
                    <span style="font-size: 0.75rem; color: {{ $accentColor }}; font-weight: 600;">{{ $sponsorProducts->count() }} items</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Products Grid -->
        <div style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: {{ $textPrimary }};">Productos Destacados</h3>
                <div style="display: flex; gap: 0.5rem;">
                    <button style="background: {{ $bgSecondary }}; color: {{ $textPrimary }}; padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid {{ $borderColor }}; cursor: pointer; font-size: 0.85rem; transition: all 0.2s ease;"
                        onmouseover="this.style.borderColor='{{ $accentColor }}'; this.style.color='{{ $accentColor }}';"
                        onmouseout="this.style.borderColor='{{ $borderColor }}'; this.style.color='{{ $textPrimary }}';">
                        Filtrar
                    </button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem;">
                @foreach($products as $product)
                <div style="background: {{ $bgSecondary }}; border-radius: 12px; overflow: hidden; border: 1px solid {{ $borderColor }}; transition: all 0.3s ease; display: flex; flex-direction: column;"
                    onmouseover="this.style.borderColor='{{ $accentColor }}'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.3)'; this.style.transform='translateY(-8px)';"
                    onmouseout="this.style.borderColor='{{ $borderColor }}'; this.style.boxShadow='none'; this.style.transform='translateY(0)';">

                    <!-- Product Image -->
                    <div style="position: relative; overflow: hidden; background: {{ $bgTertiary }}; height: 180px; display: flex; align-items: center; justify-content: center;">
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;"
                            onmouseover="this.style.transform='scale(1.1)';"
                            onmouseout="this.style.transform='scale(1)';">
                        <!-- Sponsor Badge -->
                        <div style="position: absolute; top: 0.75rem; right: 0.75rem; background: {{ $accentColor }}; color: #000; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 700;">
                            {{ $product['sponsor'] }}
                        </div>
                        <!-- Rating Badge -->
                        <div style="position: absolute; bottom: 0.75rem; left: 0.75rem; background: rgba(0,0,0,0.7); color: {{ $accentColor }}; padding: 0.35rem 0.75rem; border-radius: 20px; font-size: 0.7rem; font-weight: 600;">
                            ★ {{ $product['rating'] }}
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div style="padding: 1rem; flex-grow: 1; display: flex; flex-direction: column;">
                        <span style="font-size: 0.7rem; color: {{ $accentColor }}; font-weight: 600; margin-bottom: 0.25rem; text-transform: uppercase;">
                            {{ $product['category'] }}
                        </span>
                        <h4 style="font-size: 0.95rem; font-weight: 600; color: {{ $textPrimary }}; margin-bottom: 0.5rem; line-height: 1.3;">
                            {{ $product['name'] }}
                        </h4>
                        <p style="font-size: 0.8rem; color: {{ $textSecondary }}; margin-bottom: 0.75rem; line-height: 1.4; flex-grow: 1;">
                            {{ $product['description'] }}
                        </p>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 1.25rem; font-weight: 700; color: {{ $accentColor }};">
                                {{ $product['price'] }}
                            </span>
                            <button style="background: {{ $accentColor }}; color: #000; padding: 0.5rem 1rem; border-radius: 8px; border: none; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: all 0.2s ease;"
                                onmouseover="this.style.background='{{ $accentDark }}';"
                                onmouseout="this.style.background='{{ $accentColor }}';">
                                Ver
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- CTA Section -->
        <div style="background: {{ $bgSecondary }}; padding: 2rem; border-radius: 16px; border: 2px dashed {{ $borderColor }}; text-align: center;">
            <h3 style="font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; color: {{ $textPrimary }};">
                ¿Eres una Marca Deportiva?
            </h3>
            <p style="color: {{ $textSecondary }}; margin-bottom: 1rem;">
                Únete a nuestro programa de sponsors y llegue a miles de aficionados
            </p>
            <button style="background: {{ $accentColor }}; color: #000; padding: 0.75rem 2rem; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; transition: all 0.3s ease;"
                onmouseover="this.style.background='{{ $accentDark }}'; this.style.transform='scale(1.05)';"
                onmouseout="this.style.background='{{ $accentColor }}'; this.style.transform='scale(1)';">
                Contáctanos
            </button>
        </div>
    </div>

    <!-- Menú inferior fijo -->
    <x-layout.bottom-navigation active-item="mercados" />

    <!-- Modal de Feedback -->
    <x-feedback-modal />

</x-app-layout>