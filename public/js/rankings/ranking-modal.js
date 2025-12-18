/**
 * Ranking Modal Module
 * Handles full ranking display in modal
 */

let currentGroupId = null;

/**
 * Expand ranking - Show full ranking modal
 */
function expandRanking() {
    // Get group ID from current page
    const groupId = getGroupIdFromUrl();

    if (!groupId) {
        console.error('No group ID found');
        return;
    }

    currentGroupId = groupId;
    openRankingModal(groupId);
}

/**
 * Open ranking modal
 * @param {number} groupId - ID of the group
 */
function openRankingModal(groupId) {
    const modal = document.getElementById('ranking-modal');

    if (!modal) {
        console.error('Ranking modal not found');
        return;
    }

    // Show modal
    modal.classList.remove('hidden');

    // Add fade in animation
    setTimeout(() => {
        modal.style.opacity = '1';
    }, 10);

    // Load ranking data
    loadFullRanking(groupId);
}

/**
 * Close ranking modal
 */
function closeRankingModal() {
    const modal = document.getElementById('ranking-modal');

    if (!modal) return;

    // Fade out
    modal.style.opacity = '0';

    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

/**
 * Load full ranking data
 * @param {number} groupId - ID of the group
 */
async function loadFullRanking(groupId) {
    const loadingDiv = document.getElementById('ranking-loading');
    const listDiv = document.getElementById('ranking-list');
    const statsDiv = document.getElementById('ranking-stats');

    // Show loading
    loadingDiv.classList.remove('hidden');
    listDiv.classList.add('hidden');
    statsDiv.classList.add('hidden');

    try {
        const response = await fetch(`/groups/${groupId}/ranking`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load ranking');
        }

        const data = await response.json();

        // Render ranking
        renderFullRanking(data);

    } catch (error) {
        console.error('Error loading ranking:', error);
        showRankingError();
    }
}

/**
 * Render full ranking list
 * @param {Object} data - Ranking data
 */
function renderFullRanking(data) {
    const loadingDiv = document.getElementById('ranking-loading');
    const listDiv = document.getElementById('ranking-list');
    const statsDiv = document.getElementById('ranking-stats');

    // Hide loading
    loadingDiv.classList.add('hidden');

    if (!data.players || data.players.length === 0) {
        listDiv.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-users text-3xl mb-2"></i>
                <p>No hay jugadores en el ranking</p>
            </div>
        `;
        listDiv.classList.remove('hidden');
        return;
    }

    // Render players
    listDiv.innerHTML = data.players.map((player, index) => {
        const rank = index + 1;
        const isCurrentUser = player.is_current_user;
        const rankClass = getRankClass(rank);

        return `
            <div class="flex items-center gap-3 p-3 rounded-lg ${isCurrentUser ? 'bg-offside-primary bg-opacity-10 border-2 border-offside-primary' : 'bg-gray-50 border border-gray-200'} transition-all hover:shadow-md">
                <div class="w-8 h-8 rounded-full ${rankClass} flex items-center justify-center font-bold text-sm">
                    ${rank}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-800 truncate ${isCurrentUser ? 'text-offside-primary' : ''}">
                        ${isCurrentUser ? 'Tú' : player.name}
                    </div>
                    <div class="text-xs text-gray-600">
                        ${player.correct_answers || 0} aciertos
                    </div>
                </div>
                <div class="text-right">
                    <div class="font-bold text-offside-primary text-lg">
                        ${player.total_points || 0}
                    </div>
                    <div class="text-xs text-gray-600">puntos</div>
                </div>
                ${isCurrentUser ? '<i class="fas fa-star text-yellow-500"></i>' : ''}
            </div>
        `;
    }).join('');

    listDiv.classList.remove('hidden');

    // Update stats
    if (data.stats) {
        document.getElementById('total-players').textContent = data.stats.total_players || data.players.length;
        document.getElementById('user-position').textContent = data.stats.user_position ? `#${data.stats.user_position}` : '-';
        document.getElementById('user-points').textContent = data.stats.user_points || 0;
        statsDiv.classList.remove('hidden');
    }
}

/**
 * Get rank class for styling
 * @param {number} rank - Player rank
 * @returns {string} CSS class
 */
function getRankClass(rank) {
    switch(rank) {
        case 1: return 'bg-yellow-400 text-black';
        case 2: return 'bg-gray-400 text-black';
        case 3: return 'bg-orange-400 text-black';
        default: return 'bg-gray-600 text-white';
    }
}

/**
 * Show error message in modal
 */
function showRankingError() {
    const loadingDiv = document.getElementById('ranking-loading');
    const listDiv = document.getElementById('ranking-list');

    loadingDiv.classList.add('hidden');

    listDiv.innerHTML = `
        <div class="text-center py-8 text-red-600">
            <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
            <p>Error al cargar el ranking</p>
            <button onclick="loadFullRanking(${currentGroupId})" class="mt-4 px-4 py-2 bg-offside-primary text-white rounded-lg hover:bg-offside-secondary transition-colors">
                Reintentar
            </button>
        </div>
    `;

    listDiv.classList.remove('hidden');
}

/**
 * Get group ID from current URL
 * @returns {number|null} Group ID
 */
function getGroupIdFromUrl() {
    const pathParts = window.location.pathname.split('/');
    const groupsIndex = pathParts.indexOf('groups');

    if (groupsIndex !== -1 && pathParts[groupsIndex + 1]) {
        return parseInt(pathParts[groupsIndex + 1]);
    }

    return null;
}

/**
 * Close modal on outside click
 */
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('ranking-modal');

    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeRankingModal();
            }
        });
    }

    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRankingModal();
        }
    });
});

// Export functions
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        expandRanking,
        openRankingModal,
        closeRankingModal,
        loadFullRanking
    };
}
