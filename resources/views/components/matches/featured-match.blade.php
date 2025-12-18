@props([
    'match' => null,
    'title' => 'Partido Destacado del Día'
])

@if($match)
<div class="featured-match">
    <div class="featured-title">
        <i class="fas fa-star"></i> {{ $title }}
    </div>
    <div class="match-card">
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
        </div>
    </div>
</div>
@endif
