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

        {{-- CALENDARIO PRINCIPAL --}}
        <div class="matches-calendar-section">
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
</style>
