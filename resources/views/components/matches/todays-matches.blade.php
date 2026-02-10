@props([
    'matches' => [],
    'isDark' => true,
    'featuredMatch' => null,
])

@php
    // Dark theme colors
    if ($isDark) {
        $textPrimary = '#f1fff8';
        $textSecondary = '#9bcfcc';
        $bgSecondary = '#10302d';
        $bgTertiary = '#08201d';
        $borderColor = '#1d4f4a';
        $accentColor = '#00deb0';
        $gradientStart = '#17b796';
        $hoverBg = 'rgba(255,255,255,0.08)';
    } else {
        $textPrimary = '#1a1a1a';
        $textSecondary = '#666666';
        $bgSecondary = '#f5f5f5';
        $bgTertiary = '#eeeeee';
        $borderColor = '#ddd';
        $accentColor = '#00b893';
        $gradientStart = '#17a085';
        $hoverBg = 'rgba(0, 184, 147, 0.05)';
    }
@endphp

@if(count($matches) > 0 || $featuredMatch)
<div style="margin-bottom: 24px;">


    <!-- Matches Container -->
    <div style="padding: 0 8px;">
        <!-- PARTIDO DESTACADO (si existe) -->
        @if($featuredMatch)
            @php
                $status = $featuredMatch->status ?? 'SCHEDULED';
                $isLive = $status === 'LIVE';
                $isFinished = $status === 'FINISHED';
                $homeTeam = [
                    'name' => $featuredMatch->homeTeam?->name ?? $featuredMatch->home_team,
                    'crest_url' => $featuredMatch->homeTeam?->crest_url,
                ];
                $awayTeam = [
                    'name' => $featuredMatch->awayTeam?->name ?? $featuredMatch->away_team,
                    'crest_url' => $featuredMatch->awayTeam?->crest_url,
                ];
                $competition = [
                    'name' => $featuredMatch->competition?->name ?? 'Liga',
                ];
                $score = [
                    'home' => $featuredMatch->home_team_score,
                    'away' => $featuredMatch->away_team_score,
                ];
                $kickOffTime = \Carbon\Carbon::parse($featuredMatch->date)->format('H:i');
                $matchDate = \Carbon\Carbon::parse($featuredMatch->date)->locale(app()->getLocale())->format('d M Y');

                // Crear objeto completo para el modal
                $matchData = [
                    'id' => $featuredMatch->id,
                    'home_team' => $homeTeam,
                    'away_team' => $awayTeam,
                    'competition' => $competition,
                    'score' => $score,
                    'status' => $status,
                    'kick_off_time' => $kickOffTime,
                ];
            @endphp

            <div style="background: {{ $bgSecondary }}; border: 2px solid {{ $accentColor }}; border-radius: 12px; padding: 16px; margin-bottom: 12px; margin-top:10px; transition: all 0.2s ease; position: relative; overflow: hidden;"
                 onmouseover="this.style.background='{{ $hoverBg }}'; this.style.boxShadow='0 0 12px rgba(0, 222, 176, 0.3)'"
                 onmouseout="this.style.background='{{ $bgSecondary }}'; this.style.boxShadow='none'">

                <!-- Badge Destacado -->
                <div style="position: absolute; top: 8px; right: 8px; background: {{ $accentColor }}; color: {{ $isDark ? '#08201d' : '#fff' }}; padding: 4px 8px; border-radius: 6px; font-size: 9px; font-weight: 700; text-transform: uppercase;">
                    ⭐ {{ $matchDate }} • {{ $kickOffTime }}
                </div>

                <!-- Match Header -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <span style="font-size: 11px; font-weight: 700; color: {{ $accentColor }}; text-transform: uppercase;">
                        {{ $competition['name'] ?? 'Liga' }}
                    </span>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 12px; color: {{ $textSecondary }};">
                            {{ $kickOffTime }}
                        </span>
                        @if($isLive)
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <span style="width: 8px; height: 8px; background: #ff6b6b; border-radius: 50%; animation: pulse 1.5s infinite;"></span>
                                <span style="font-size: 11px; font-weight: 700; color: #ff6b6b;">EN VIVO</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Teams Container -->
                <div style="display: flex; align-items: center; gap: 8px; justify-content: space-between; margin-bottom: 12px;">
                    <!-- Home Team -->
                    <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                        @if($homeTeam['crest_url'] ?? false)
                            <img src="{{ $homeTeam['crest_url'] }}"
                                 alt="{{ $homeTeam['name'] ?? 'Home Team' }}"
                                 style="width: 36px; height: 36px; border-radius: 4px; object-fit: contain; background: {{ $isDark ? 'rgba(255,255,255,0.05)' : '#f0f0f0' }}; padding: 2px;">
                        @else
                            <div style="width: 36px; height: 36px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e0e0e0' }}; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-shield-alt" style="font-size: 16px; opacity: 0.5;"></i>
                            </div>
                        @endif
                        <span style="font-size: 13px; font-weight: 600; color: {{ $textPrimary }}; flex: 1; text-align: right;">
                            {{ $homeTeam['name'] ?? 'Equipo Local' }}
                        </span>
                    </div>

                    <!-- Score -->
                    <div style="flex: 0 0 auto; padding: 0 12px; text-align: center;">
                        @if($isFinished)
                            <div style="font-size: 24px; font-weight: 700; color: {{ $textPrimary }};">
                                {{ $score['home'] ?? '-' }} - {{ $score['away'] ?? '-' }}
                            </div>
                            <span style="font-size: 11px; color: {{ $textSecondary }}; font-weight: 600; text-transform: uppercase;">
                                FINAL
                            </span>
                        @elseif($isLive)
                            <div style="font-size: 24px; font-weight: 700; color: #ff6b6b;">
                                {{ $score['home'] ?? '-' }} - {{ $score['away'] ?? '-' }}
                            </div>
                            <span style="font-size: 11px; color: #ff6b6b; font-weight: 600;">EN VIVO</span>
                        @else
                            <div style="font-size: 16px; font-weight: 600; color: {{ $textSecondary }};">
                                VS
                            </div>
                        @endif
                    </div>

                    <!-- Away Team -->
                    <div style="display: flex; align-items: center; gap: 8px; flex: 1;">
                        <span style="font-size: 13px; font-weight: 600; color: {{ $textPrimary }}; flex: 1; text-align: left;">
                            {{ $awayTeam['name'] ?? 'Equipo Visitante' }}
                        </span>
                        @if($awayTeam['crest_url'] ?? false)
                            <img src="{{ $awayTeam['crest_url'] }}"
                                 alt="{{ $awayTeam['name'] ?? 'Away Team' }}"
                                 style="width: 36px; height: 36px; border-radius: 4px; object-fit: contain; background: {{ $isDark ? 'rgba(255,255,255,0.05)' : '#f0f0f0' }}; padding: 2px;">
                        @else
                            <div style="width: 36px; height: 36px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e0e0e0' }}; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-shield-alt" style="font-size: 16px; opacity: 0.5;"></i>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Action Buttons -->
                @if(!$isFinished)
                    <div style="display: flex; gap: 8px;">
                        <button class="btn-predict" onclick="openMatchGroupsModal({{ $featuredMatch->id }}, '{{ $homeTeam['name'] ?? 'Team' }} vs {{ $awayTeam['name'] ?? 'Team' }}', '{{ $competition['name'] ?? 'Competition' }}')"
                                style="flex: 1; padding: 10px; background: linear-gradient(135deg, {{ $gradientStart }}, {{ $accentColor }}); border: none; border-radius: 8px; color: white; font-weight: 600; font-size: 13px; cursor: pointer;">
                            <i class="fas fa-star"></i> Predecir
                        </button>
                        <button class="btn-details" data-match='{{ json_encode($matchData) }}' onclick="openMatchDetailsModalFromButton(this)"
                                style="flex: 1; padding: 10px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e8e8e8' }}; border: none; border-radius: 8px; color: {{ $textPrimary }}; font-weight: 600; font-size: 13px; cursor: pointer;">
                            <i class="fas fa-info-circle"></i> Detalles
                        </button>
                    </div>
                @else
                    <button class="btn-predict" data-match='{{ json_encode($matchData) }}' onclick="openMatchDetailsModalFromButton(this)"
                            style="width: 100%; padding: 10px; background: linear-gradient(135deg, {{ $gradientStart }}, {{ $accentColor }}); border: none; border-radius: 8px; color: white; font-weight: 600; font-size: 13px; cursor: pointer;">
                        <i class="fas fa-info-circle"></i> Ver Detalles
                    </button>
                @endif
            </div>
        @endif


    </div>
</div>

<style>
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
@endif
