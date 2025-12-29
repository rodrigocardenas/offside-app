@props([
    'match' => null,
    'title' => 'Partido Destacado del Día'
])

@php
    $themeMode = auth()->user()->theme_mode ?? 'auto';
    $isDark = $themeMode === 'dark' || ($themeMode === 'auto' && false);
@endphp

@if($match)
<div class="featured-match">
    <div class="featured-title">
        <i class="fas fa-star"></i> {{ $title }}
    </div>
    <div class="match-card" onclick="openMatchGroupsModal({{ $match->id }}, '{{ ($match->homeTeam->name ?? $match->home_team) . ' vs ' . ($match->awayTeam->name ?? $match->away_team) }}', '{{ $match->competition->name ?? 'Liga' }}')" style="cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0, 222, 176, 0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
        <div class="match-teams">
            <span class="team-name">{{ $match->homeTeam->name ?? $match->home_team }}</span>
            @if(isset($match->homeTeam->crest_url))
                <img src="{{ asset('images/teams/' . $match->homeTeam->crest_url) }}" class="team-logo" alt="Home">
            @endif
            <span class="match-time-inline">{{ \Carbon\Carbon::parse($match->utc_date)->format('H:i') }}</span>
            @if(isset($match->awayTeam->crest_url))
                <img src="{{ asset('images/teams/' . $match->awayTeam->crest_url) }}" class="team-logo" alt="Away">
            @endif
            <span class="team-name">{{ $match->awayTeam->name ?? $match->away_team }}</span>
        </div>
        <div class="match-league">
            @if(isset($match->competition))
                <i class="fas fa-circle" style="color: white; font-size: 4px; vertical-align: middle;"></i>
                {{ $match->competition->name ?? 'Liga' }} • Jornada {{ $match->matchday ?? '-' }}
            @endif
            {{-- <div style="display: flex; align-items: center; gap: 8px; margin-top: 8px; color: #00deb0; font-size: 13px; font-weight: 600;">
                <i class="fas fa-mouse"></i> Haz clic para ver grupos
            </div> --}}
        </div>
    </div>
</div>

<!-- Modal de Grupos -->
<x-matches.match-groups-modal :match="$match" :is-dark="$isDark" />
@endif
