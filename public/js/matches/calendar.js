/**
 * Calendario de Partidos - JavaScript
 * Maneja la carga, filtrado y actualización de partidos
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Calendar JS cargado');
    loadInitialMatches();
});

/**
 * Carga los partidos iniciales
 */
function loadInitialMatches() {
    const fromDate = new Date().toISOString().split('T')[0];
    const toDate = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    fetchMatchesFromAPI(fromDate, toDate);
}

/**
 * Obtiene partidos de la API
 */
async function fetchMatchesFromAPI(fromDate, toDate, competitionId = null) {
    try {
        const url = new URL('/api/matches/calendar', window.location.origin);
        url.searchParams.append('from_date', fromDate);
        url.searchParams.append('to_date', toDate);

        if (competitionId) {
            url.searchParams.append('competition_id', competitionId);
        }

        const response = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`API Error: ${response.status}`);
        }

        const data = await response.json();
        console.log('Matches loaded:', data);

        // Actualizar la UI con los datos
        updateMatchesUI(data);

    } catch (error) {
        console.error('Error fetching matches:', error);
        showErrorMessage('Error al cargar los partidos');
    }
}

/**
 * Actualiza la interfaz con los partidos
 */
function updateMatchesUI(data) {
    const container = document.querySelector('.matches-calendar-section');

    if (!data.data || Object.keys(data.data).length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 40px 20px; color: #999;">
                <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                <p style="font-size: 16px; margin-bottom: 8px;">No hay partidos próximos</p>
                <p style="font-size: 14px;">Intenta ajustar los filtros</p>
            </div>
        `;
        return;
    }

    // Construir HTML para cada día
    let html = '';
    for (const [date, matches] of Object.entries(data.data)) {
        html += createDayHTML(date, matches);
    }

    container.innerHTML = html;
}

/**
 * Crea el HTML para un día de partidos
 */
function createDayHTML(date, matches) {
    const dateObj = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let dayBadge = date;
    if (dateObj.toDateString() === today.toDateString()) {
        dayBadge = '<span class="day-badge today">HOY</span>';
    } else if (dateObj.toDateString() === new Date(today.getTime() + 24*60*60*1000).toDateString()) {
        dayBadge = '<span class="day-badge tomorrow">MAÑANA</span>';
    } else {
        const formatter = new Intl.DateTimeFormat('es-ES', { day: '2-digit', month: 'short' });
        dayBadge = formatter.format(dateObj);
    }

    const dayName = new Intl.DateTimeFormat('es-ES', { weekday: 'long' }).format(dateObj);

    let matchesHTML = matches.map(match => createMatchCardHTML(match)).join('');

    return `
        <div class="calendar-day-group" style="margin-bottom: 16px;">
            <div class="day-header" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--bg-secondary); border-bottom: 1px solid var(--border-color);">
                <span class="day-date" style="font-weight: 700; font-size: 14px; color: var(--text-primary); min-width: 50px;">
                    ${dayBadge}
                </span>
                <span class="day-name" style="margin-left: auto; font-size: 13px; color: var(--text-secondary); text-transform: capitalize;">
                    ${dayName}
                </span>
            </div>
            <div class="matches-container" style="display: flex; flex-direction: column; gap: 8px; padding: 8px 8px;">
                ${matchesHTML}
            </div>
        </div>
    `;
}

/**
 * Crea el HTML para una tarjeta de partido
 */
function createMatchCardHTML(match) {
    const isDark = document.body.classList.contains('dark') || document.documentElement.dataset.theme === 'dark';
    const bgColor = isDark ? '#1a524e' : '#f9f9f9';
    const borderColor = isDark ? '#2d7a77' : '#e0e0e0';
    const textColor = isDark ? '#f1fff8' : '#333333';
    const secondaryText = isDark ? '#a0d5d0' : '#666666';

    const status = match.status || 'SCHEDULED';
    const isLive = status === 'LIVE';
    const isFinished = status === 'FINISHED';

    const homeTeam = match.home_team || {};
    const awayTeam = match.away_team || {};
    const competition = match.competition || {};
    const score = match.score || {};

    let scoreDisplay = '';
    if (isFinished) {
        scoreDisplay = `${score.home || '-'} - ${score.away || '-'}`;
    } else if (isLive) {
        scoreDisplay = '<span style="font-size: 12px; font-weight: 700; color: #ff6b6b; letter-spacing: 1px;">EN VIVO</span>';
    } else {
        scoreDisplay = 'vs';
    }

    const liveIndicator = isLive ? '<span class="live-badge" style="display: inline-block; width: 8px; height: 8px; background: #ff6b6b; border-radius: 50%; animation: pulse 1.5s infinite;"></span>' : '';

    return `
        <div class="match-card" style="background: ${bgColor}; border: 1px solid ${borderColor}; border-radius: 10px; padding: 12px; margin: 0 8px;">
            <div class="match-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; gap: 8px;">
                <span class="competition-badge" style="font-size: 11px; font-weight: 600; color: #00deb0; text-transform: uppercase;">
                    ${competition.name || 'Liga'}
                </span>
                <div style="display: flex; align-items: center; gap: 4px;">
                    <span class="match-time" style="font-size: 13px; font-weight: 700; color: ${textColor};">
                        ${match.kick_off_time || '--:--'}
                    </span>
                    ${liveIndicator}
                </div>
            </div>

            <div class="teams-container" style="display: flex; align-items: center; gap: 8px; justify-content: space-between;">
                <div class="team home-team" style="display: flex; align-items: center; gap: 8px; flex: 1;">
                    ${createTeamCrestHTML(homeTeam, isDark)}
                    <span class="team-name" style="font-size: 13px; font-weight: 600; color: ${textColor}; flex: 1; text-align: right;">
                        ${homeTeam.name || 'Equipo Local'}
                    </span>
                </div>

                <div class="match-score" style="flex: 0 0 auto; font-size: 16px; font-weight: 700; color: ${textColor}; text-align: center; padding: 0 8px;">
                    ${scoreDisplay}
                </div>

                <div class="team away-team" style="display: flex; align-items: center; gap: 8px; flex: 1;">
                    <span class="team-name" style="font-size: 13px; font-weight: 600; color: ${textColor}; flex: 1; text-align: left;">
                        ${awayTeam.name || 'Equipo Visitante'}
                    </span>
                    ${createTeamCrestHTML(awayTeam, isDark)}
                </div>
            </div>

            ${!isFinished ? `
                <div style="display: flex; gap: 8px; margin-top: 10px;">
                    <button class="btn-predict" onclick="openPredictModal(${match.id})" 
                            style="flex: 1; padding: 8px; background: linear-gradient(135deg, #17b796, #00deb0); border: none; border-radius: 6px; color: white; font-weight: 600; font-size: 12px; cursor: pointer;">
                        <i class="fas fa-star"></i> Predecir
                    </button>
                    <button class="btn-details" onclick="openMatchDetails(${match.id})" 
                            style="flex: 1; padding: 8px; background: ${isDark ? 'rgba(255,255,255,0.1)' : '#e8e8e8'}; border: none; border-radius: 6px; color: ${textColor}; font-weight: 600; font-size: 12px; cursor: pointer;">
                        <i class="fas fa-info-circle"></i> Detalles
                    </button>
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * Crea el HTML para el escudo del equipo
 */
function createTeamCrestHTML(team, isDark) {
    if (team.crest_url) {
        return `
            <img src="${team.crest_url}" 
                 alt="${team.name}" 
                 class="team-crest"
                 style="width: 32px; height: 32px; border-radius: 4px; object-fit: contain; background: ${isDark ? 'rgba(255,255,255,0.05)' : '#f0f0f0'}; padding: 2px;">
        `;
    } else {
        return `
            <div class="team-crest-placeholder" 
                 style="width: 32px; height: 32px; background: ${isDark ? 'rgba(255,255,255,0.1)' : '#e0e0e0'}; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-shield-alt" style="font-size: 16px; opacity: 0.5;"></i>
            </div>
        `;
    }
}

/**
 * Abre el modal de predicción
 */
function openPredictModal(matchId) {
    console.log('Opening predict modal for match:', matchId);
    // TODO: Implementar modal de predicción
    alert('Modal de predicción - Match ID: ' + matchId);
}

/**
 * Abre los detalles del partido
 */
function openMatchDetails(matchId) {
    console.log('Opening match details for match:', matchId);
    // TODO: Implementar detalles del partido
    alert('Detalles del partido - Match ID: ' + matchId);
}

/**
 * Muestra un mensaje de error
 */
function showErrorMessage(message) {
    const container = document.querySelector('.matches-calendar-section');
    container.innerHTML = `
        <div style="text-align: center; padding: 40px 20px; color: #ff6b6b;">
            <i class="fas fa-exclamation-circle" style="font-size: 48px; margin-bottom: 16px;"></i>
            <p style="font-size: 16px;">${message}</p>
        </div>
    `;
}

// Estilos globales de animación
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
`;
document.head.appendChild(style);
