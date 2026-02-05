@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
    $layout = $isDark ? 'mobile-dark-layout' : 'mobile-light-layout';
@endphp

<x-dynamic-layout :layout="$layout">
    @push('scripts')
        <script src="{{ asset('js/timezone-sync.js') }}"></script>
        <script src="{{ asset('js/common/navigation.js') }}"></script>
        <script src="{{ asset('js/matches/calendar.js') }}"></script>
    @endpush

    @section('navigation-title', 'Calendario de Partidos')

    <div class="main-container">
        {{-- HEADER --}}
        <x-layout.header-profile
            :logo-url="asset('images/logo_alone.png')"
            alt-text="Offside Club"
        />

        {{-- FILTROS Y CONTROLES --}}
        <x-matches.calendar-filters 
            :competitions="$competitions" 
            :selectedCompetitionId="$selectedCompetitionId ?? null"
        />

        {{-- SPINNER DE CARGA --}}
        <div id="loadingSpinner" style="display: none; text-align: center; padding: 60px 20px;">
            <div style="display: inline-block; width: 50px; height: 50px; border: 4px solid rgba(0, 222, 176, 0.2); border-top-color: #00deb0; border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <p style="margin-top: 16px; color: #999; font-size: 14px;">Cargando partidos...</p>
        </div>

        {{-- CALENDARIO PRINCIPAL --}}
        <div id="matchesContainer" class="matches-calendar-section">
            <div class="section-title">
                <i class="fas fa-calendar-alt"></i> Próximos Partidos
            </div>

            @forelse($matchesByDate as $date => $matches)
                <x-matches.calendar-day 
                    :date="$date" 
                    :matches="$matches" 
                />
            @empty
                <div style="text-align: center; padding: 40px 20px; color: #999;">
                    <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p style="font-size: 16px; margin-bottom: 8px;">No hay partidos próximos</p>
                    <p style="font-size: 14px;">Intenta ajustar los filtros</p>
                </div>
            @endforelse
        </div>

        {{-- ESTADÍSTICAS --}}
        @if($statistics)
            <x-matches.calendar-stats :stats="$statistics" />
        @endif

        {{-- NAVEGACIÓN INFERIOR --}}
        <x-layout.bottom-navigation active-item="partidos" />
        
        {{-- MODAL DE GRUPOS --}}
        <x-matches.match-groups-modal :match="null" :is-dark="$isDark" />
        
        {{-- MODAL DE DETALLES --}}
        <x-matches.match-details-modal :is-dark="$isDark" />
    </div>
</x-dynamic-layout>

<style>
    .matches-calendar-section {
        padding: 0 0 20px 0;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 18px;
        font-weight: 700;
        padding: 20px 16px 16px 16px;
        margin-bottom: 8px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
