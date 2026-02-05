@props([
    'isDark' => true,
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
        $overlayBg = 'rgba(0, 0, 0, 0.55)';
        $hoverBg = 'rgba(255,255,255,0.08)';
        $accentBg = 'rgba(0, 222, 176, 0.12)';
        $surfaceShadow = '0 14px 40px rgba(0, 0, 0, 0.55)';
    } else {
        // Light theme colors
        $textPrimary = '#1a1a1a';
        $textSecondary = '#666666';
        $bgSecondary = '#f5f5f5';
        $bgTertiary = '#eeeeee';
        $borderColor = '#ddd';
        $accentColor = '#00b893';
        $overlayBg = 'rgba(0, 0, 0, 0.3)';
        $hoverBg = 'rgba(0, 184, 147, 0.05)';
        $accentBg = 'rgba(0, 184, 147, 0.1)';
        $surfaceShadow = '0 12px 34px rgba(0, 0, 0, 0.15)';
    }
@endphp

<!-- Modal de Detalles del Partido -->
<div id="matchDetailsModal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: {{ $overlayBg }}; display: none; align-items: center; justify-content: center; z-index: 9998; padding: 16px;">
    <div style="background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; border-radius: 16px; width: 100%; max-width: 500px; max-height: 85vh; overflow-y: auto; box-shadow: {{ $surfaceShadow }};">
        
        <!-- Header -->
        <div style="padding: 24px; border-bottom: 1px solid {{ $borderColor }}; display: flex; justify-content: space-between; align-items: flex-start; position: sticky; top: 0; background: {{ $bgSecondary }}; z-index: 10;">
            <div style="flex: 1;">
                <h3 id="detailsTitle" style="margin: 0; font-size: 18px; font-weight: 700; color: {{ $textPrimary }}; margin-bottom: 4px;">
                    <i class="fas fa-info-circle"></i> Detalles del Partido
                </h3>
            </div>
            <button id="closeMatchDetailsModal" type="button" style="background: none; border: none; font-size: 24px; color: {{ $textSecondary }}; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s ease; flex-shrink: 0;"
                    onmouseover="this.style.background='{{ $hoverBg }}'" onmouseout="this.style.background='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Body -->
        <div style="padding: 24px;">
            
            <!-- Equipos -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; padding: 16px; background: {{ $bgTertiary }}; border-radius: 12px;">
                <div style="flex: 1; text-align: center;">
                    <img id="detailsHomeTeamCrest" src="" alt="" style="width: 48px; height: 48px; margin: 0 auto 8px; border-radius: 4px; object-fit: contain; display: none;">
                    <div id="detailsHomeTeamCrestPlaceholder" style="width: 48px; height: 48px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e0e0e0' }}; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                        <i class="fas fa-shield-alt" style="font-size: 24px; opacity: 0.5;"></i>
                    </div>
                    <p id="detailsHomeTeamName" style="margin: 0; font-size: 16px; font-weight: 700; color: {{ $textPrimary }};"></p>
                </div>

                <div style="flex: 0 0 auto; text-align: center; padding: 0 16px;">
                    <div id="detailsScore" style="font-size: 32px; font-weight: 700; color: {{ $accentColor }}; margin-bottom: 8px;">-</div>
                    <div id="detailsStatus" style="font-size: 12px; font-weight: 600; color: {{ $textSecondary }};"></div>
                </div>

                <div style="flex: 1; text-align: center;">
                    <img id="detailsAwayTeamCrest" src="" alt="" style="width: 48px; height: 48px; margin: 0 auto 8px; border-radius: 4px; object-fit: contain; display: none;">
                    <div id="detailsAwayTeamCrestPlaceholder" style="width: 48px; height: 48px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e0e0e0' }}; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px;">
                        <i class="fas fa-shield-alt" style="font-size: 24px; opacity: 0.5;"></i>
                    </div>
                    <p id="detailsAwayTeamName" style="margin: 0; font-size: 16px; font-weight: 700; color: {{ $textPrimary }};"></p>
                </div>
            </div>

            <!-- Información General -->
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px;">
                
                <!-- Competencia -->
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: {{ $bgTertiary }}; border-radius: 8px;">
                    <i class="fas fa-trophy" style="color: {{ $accentColor }}; width: 20px; text-align: center;"></i>
                    <div style="flex: 1;">
                        <p style="margin: 0; font-size: 12px; color: {{ $textSecondary }};">Competencia</p>
                        <p id="detailsCompetition" style="margin: 0; font-size: 14px; font-weight: 600; color: {{ $textPrimary }};"></p>
                    </div>
                </div>

                <!-- Hora -->
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: {{ $bgTertiary }}; border-radius: 8px;">
                    <i class="fas fa-clock" style="color: {{ $accentColor }}; width: 20px; text-align: center;"></i>
                    <div style="flex: 1;">
                        <p style="margin: 0; font-size: 12px; color: {{ $textSecondary }};">Hora</p>
                        <p id="detailsTime" style="margin: 0; font-size: 14px; font-weight: 600; color: {{ $textPrimary }};"></p>
                    </div>
                </div>

                <!-- Estadio -->
                <div id="stadiumSection" style="display: none; flex-direction: column;">
                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: {{ $bgTertiary }}; border-radius: 8px;">
                        <i class="fas fa-building" style="color: {{ $accentColor }}; width: 20px; text-align: center;"></i>
                        <div style="flex: 1;">
                            <p style="margin: 0; font-size: 12px; color: {{ $textSecondary }};">Estadio</p>
                            <p id="detailsStadium" style="margin: 0; font-size: 14px; font-weight: 600; color: {{ $textPrimary }};"></p>
                        </div>
                    </div>
                </div>

                <!-- Árbitro -->
                <div id="refereeSection" style="display: none; flex-direction: column;">
                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: {{ $bgTertiary }}; border-radius: 8px;">
                        <i class="fas fa-whistle" style="color: {{ $accentColor }}; width: 20px; text-align: center;"></i>
                        <div style="flex: 1;">
                            <p style="margin: 0; font-size: 12px; color: {{ $textSecondary }};">Árbitro</p>
                            <p id="detailsReferee" style="margin: 0; font-size: 14px; font-weight: 600; color: {{ $textPrimary }};"></p>
                        </div>
                    </div>
                </div>

                <!-- Matchday -->
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: {{ $bgTertiary }}; border-radius: 8px;">
                    <i class="fas fa-layer-group" style="color: {{ $accentColor }}; width: 20px; text-align: center;"></i>
                    <div style="flex: 1;">
                        <p style="margin: 0; font-size: 12px; color: {{ $textSecondary }};">Jornada</p>
                        <p id="detailsMatchday" style="margin: 0; font-size: 14px; font-weight: 600; color: {{ $textPrimary }};"></p>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div style="display: flex; gap: 12px;">
                <button id="detailsPredictBtn" onclick="closeMatchDetailsModal(); openMatchGroupsModal(window.currentMatchId, window.currentMatchTeams, window.currentMatchCompetition);"
                        style="flex: 1; padding: 12px; background: linear-gradient(135deg, #17b796, #00deb0); border: none; border-radius: 8px; color: white; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-star"></i> Predecir
                </button>
                <button onclick="closeMatchDetailsModal()"
                        style="flex: 1; padding: 12px; background: {{ $isDark ? 'rgba(255,255,255,0.1)' : '#e8e8e8' }}; border: none; border-radius: 8px; color: {{ $textSecondary }}; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.background='{{ $hoverBg }}'" onmouseout="this.style.background='{{ $isDark ? 'rgba(255,255,255,0.1)' : '#e8e8e8' }}'">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('matchDetailsModal');
        const closeBtn = document.getElementById('closeMatchDetailsModal');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeMatchDetailsModal);
        }

        // Close modal when clicking outside
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeMatchDetailsModal();
            }
        });

        // Expose close function globally
        window.closeMatchDetailsModal = function() {
            modal.style.display = 'none';
        };

        // Expose function to open modal from button with data attribute
        window.openMatchDetailsModalFromButton = function(buttonElement) {
            const matchDataJson = buttonElement.getAttribute('data-match');
            const matchData = JSON.parse(matchDataJson);
            window.openMatchDetailsModal(matchData);
        };

        // Expose open function globally
        window.openMatchDetailsModal = function(matchData) {
            if (!matchData) return;

            // Guardar datos actuales para el modal de predicción
            window.currentMatchId = matchData.id;
            window.currentMatchTeams = matchData.home_team.name + ' vs ' + matchData.away_team.name;
            window.currentMatchCompetition = matchData.competition.name;

            // Actualizar equipos
            const homeTeamCrest = document.getElementById('detailsHomeTeamCrest');
            const homeTeamCrestPlaceholder = document.querySelector('div[id="detailsHomeTeamCrestPlaceholder"]');
            const awayTeamCrest = document.getElementById('detailsAwayTeamCrest');
            const awayTeamCrestPlaceholder = document.querySelector('div[id="detailsAwayTeamCrestPlaceholder"]');

            if (matchData.home_team.crest_url) {
                homeTeamCrest.src = matchData.home_team.crest_url;
                homeTeamCrest.style.display = 'block';
                homeTeamCrestPlaceholder.style.display = 'none';
            } else {
                homeTeamCrest.style.display = 'none';
                homeTeamCrestPlaceholder.style.display = 'flex';
            }

            if (matchData.away_team.crest_url) {
                awayTeamCrest.src = matchData.away_team.crest_url;
                awayTeamCrest.style.display = 'block';
                awayTeamCrestPlaceholder.style.display = 'none';
            } else {
                awayTeamCrest.style.display = 'none';
                awayTeamCrestPlaceholder.style.display = 'flex';
            }

            document.getElementById('detailsHomeTeamName').textContent = matchData.home_team.name;
            document.getElementById('detailsAwayTeamName').textContent = matchData.away_team.name;

            // Actualizar score
            const isFinished = matchData.status === 'Finished' || matchData.status === 'Match Finished';
            if (isFinished && matchData.score.home !== null && matchData.score.away !== null) {
                document.getElementById('detailsScore').textContent = matchData.score.home + ' - ' + matchData.score.away;
                document.getElementById('detailsStatus').textContent = 'Finalizado';
            } else {
                document.getElementById('detailsScore').textContent = 'vs';
                document.getElementById('detailsStatus').textContent = matchData.kick_off_time;
            }

            // Actualizar información general
            document.getElementById('detailsCompetition').textContent = matchData.competition.name;
            document.getElementById('detailsTime').textContent = matchData.kick_off_time;
            document.getElementById('detailsMatchday').textContent = matchData.stage || '-';

            // Mostrar/ocultar secciones opcionales
            const stadiumSection = document.getElementById('stadiumSection');
            const refereeSection = document.getElementById('refereeSection');

            if (matchData.stadium) {
                document.getElementById('detailsStadium').textContent = matchData.stadium;
                stadiumSection.style.display = 'flex';
            } else {
                stadiumSection.style.display = 'none';
            }

            if (matchData.referee) {
                document.getElementById('detailsReferee').textContent = matchData.referee;
                refereeSection.style.display = 'flex';
            } else {
                refereeSection.style.display = 'none';
            }

            // Mostrar/ocultar botón predecir según estado
            const predictBtn = document.getElementById('detailsPredictBtn');
            if (isFinished) {
                predictBtn.style.display = 'none';
            } else {
                predictBtn.style.display = 'block';
            }

            // Abrir modal
            modal.style.display = 'flex';
        };
    });
</script>

<style>
    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>
