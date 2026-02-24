@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';

    $status = $match['status'] ?? 'Not Started';
    $isLive = $status === 'In Play';
    $isFinished = $status === 'Match Finished';

    $bgColor = $isDark ? '#1a524e' : '#f9f9f9';
    $borderColor = $isDark ? '#2d7a77' : '#e0e0e0';
    $textColor = $isDark ? '#f1fff8' : '#333333';
    $secondaryText = $isDark ? '#a0d5d0' : '#666666';
    $accentColor = '#00deb0';

    $homeTeam = $match['home_team'];
    $awayTeam = $match['away_team'];
    $kickOffTime = $match['kick_off_time'];
    $competition = $match['competition'];
    $score = $match['score'];
@endphp

<div class="match-card"
     style="background: {{ $bgColor }}; border: 1px solid {{ $borderColor }}; border-radius: 10px; padding: 12px; margin: 0 8px;">

    {{-- HEADER CON COMPETENCIA Y HORA --}}
    <div class="match-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; gap: 8px;">
        <span class="competition-badge" style="font-size: 11px; font-weight: 600; color: {{ $accentColor }}; text-transform: uppercase;">
            {{ $competition['name'] ?? 'Liga' }}
        </span>

        <div style="display: flex; align-items: center; gap: 4px;">
            <span class="match-time" style="font-size: 13px; font-weight: 700; color: {{ $textColor }};">
                {{ $kickOffTime }}
            </span>
            @if($isLive)
                <span class="live-badge" style="display: inline-block; width: 8px; height: 8px; background: #ff6b6b; border-radius: 50%; animation: pulse 1.5s infinite;"></span>
            @endif
        </div>
    </div>

    {{-- EQUIPOS --}}
    <div class="teams-container" style="display: flex; align-items: center; gap: 8px; justify-content: space-between;">

        {{-- EQUIPO LOCAL --}}
        <div class="team home-team" style="display: flex; align-items: center; gap: 8px; flex: 1;">
            @if($homeTeam['crest_url'])
                <img src="{{ $homeTeam['crest_url'] }}"
                     alt="{{ $homeTeam['name'] }}"
                     class="team-crest"
                     style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain; background: {{ $isDark ? 'rgba(255,255,255,0.05)' : '#f0f0f0' }}; padding: 2px;">
            @else
                <div class="team-crest-placeholder"
                     style="width: 32px; height: 32px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e0e0e0' }}; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-shield-alt" style="font-size: 16px; opacity: 0.5;"></i>
                </div>
            @endif

            <span class="team-name" style="font-size: 13px; font-weight: 600; color: {{ $textColor }}; flex: 1; text-align: right;">
                {{ $homeTeam['name'] }}
            </span>
        </div>

        {{-- RESULTADO O ESTADO --}}
        <div class="match-score" style="flex: 0 0 auto;">
            @if($isFinished)
                <div style="font-size: 16px; font-weight: 700; color: {{ $textColor }}; text-align: center; padding: 0 8px;">
                    {{ $score['home'] ?? '-' }} - {{ $score['away'] ?? '-' }}
                </div>
            @elseif($isLive)
                <div style="font-size: 12px; font-weight: 700; color: #ff6b6b; text-align: center; padding: 0 8px; letter-spacing: 1px;">
                    EN VIVO
                </div>
            @else
                <div style="font-size: 12px; font-weight: 600; color: {{ $secondaryText }}; text-align: center; padding: 0 8px;">
                    vs
                </div>
            @endif
        </div>

        {{-- EQUIPO VISITANTE --}}
        <div class="team away-team" style="display: flex; align-items: center; gap: 8px; flex: 1;">
            <span class="team-name" style="font-size: 13px; font-weight: 600; color: {{ $textColor }}; flex: 1; text-align: left;">
                {{ $awayTeam['name'] }}
            </span>

            @if($awayTeam['crest_url'])
                <img src="{{ $awayTeam['crest_url'] }}"
                     alt="{{ $awayTeam['name'] }}"
                     class="team-crest"
                     style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain; background: {{ $isDark ? 'rgba(255,255,255,0.05)' : '#f0f0f0' }}; padding: 2px;">
            @else
                <div class="team-crest-placeholder"
                     style="width: 32px; height: 32px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e0e0e0' }}; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-shield-alt" style="font-size: 16px; opacity: 0.5;"></i>
                </div>
            @endif
        </div>
    </div>

    {{-- FOOTER - BOTONES DE ACCIÓN --}}
    @if(!$isFinished)
        <div style="display: flex; gap: 8px; margin-top: 10px;">
            <button class="btn-predict"
                    onclick="openMatchGroupsModal({{ $match['id'] }}, '{{ $match['home_team']['name'] }} vs {{ $match['away_team']['name'] }}', '{{ $match['competition']['name'] }}')"
                    style="flex: 1; padding: 8px; background: linear-gradient(135deg, #17b796, {{ $accentColor }}); border: none; border-radius: 6px; color: white; font-weight: 600; font-size: 12px; cursor: pointer; transition: all 0.3s;">
                <i class="fas fa-star"></i> Predecir
            </button>
            <button class="btn-details"
                    data-match='{{ json_encode($match) }}'
                    onclick="openMatchDetailsModalFromButton(this)"
                    style="flex: 1; padding: 8px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e8e8e8' }}; border: none; border-radius: 6px; color: {{ $textColor }}; font-weight: 600; font-size: 12px; cursor: pointer; transition: all 0.3s;">
                <i class="fas fa-info-circle"></i> Detalles
            </button>
        </div>
    @endif
</div>

<style>
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .match-card {
        transition: all 0.3s ease;
    }

    .match-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
</style>
