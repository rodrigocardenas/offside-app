<!-- 
    🎮 QUIZ RANKING VIEW
    Vista para mostrar el ranking dinámico del quiz MWC
    Ordena por: Puntos (DESC) y Tiempo (ASC) como desempate
-->

@extends('layouts.app')

@section('title', 'Quiz Ranking - ' . $group->name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-8 shadow-lg">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('groups.show', $group) }}" class="text-blue-100 hover:text-white transition">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al Grupo
                </a>
            </div>
            <h1 class="text-4xl font-bold mb-2">🎮 {{ $group->name }}</h1>
            <p class="text-blue-100">Código: <span class="font-mono font-semibold">{{ $group->code }}</span></p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <!-- Total Players -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Jugadores</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" id="totalPlayers">0</p>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <i class="fas fa-users text-blue-600 dark:text-blue-300 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Your Position -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Tu Posición</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" id="userPosition">—</p>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                        <i class="fas fa-crown text-yellow-600 dark:text-yellow-300 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Your Points -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Tus Puntos</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" id="userPoints">0</p>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <i class="fas fa-star text-green-600 dark:text-green-300 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Your Time -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm font-medium">Tu Tiempo Total</p>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1" id="userTime">00:00:00</p>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                        <i class="fas fa-clock text-purple-600 dark:text-purple-300 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ranking Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Ranking Global</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Ordenado por puntos (descendente) y tiempo de respuesta (ascendente)
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Posición</span>
                            </th>
                            <th class="px-6 py-3 text-left">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Jugador</span>
                            </th>
                            <th class="px-6 py-3 text-center">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Puntos</span>
                            </th>
                            <th class="px-6 py-3 text-center">
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Tiempo Total</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="rankingBody" class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr class="text-center py-8">
                            <td colspan="4" class="px-6 py-8">
                                <div class="flex items-center justify-center">
                                    <i class="fas fa-spinner fa-spin text-gray-400 text-2xl mr-2"></i>
                                    <span class="text-gray-500 dark:text-gray-400">Cargando ranking...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Podium (Top 3) -->
        <div class="mt-12">
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">🏆 Podio</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="podium">
                <!-- Podium items will be injected here -->
            </div>
        </div>
    </div>
</div>

<style>
    .podium-position {
        @apply relative;
    }

    .podium-medal {
        @apply text-4xl mb-2 text-center;
    }

    .podium-badge {
        @apply inline-block px-3 py-1 rounded-full text-xs font-semibold text-white;
    }

    .podium-rank-1 {
        @apply bg-yellow-500;
    }

    .podium-rank-2 {
        @apply bg-gray-400;
    }

    .podium-rank-3 {
        @apply bg-orange-600;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetchQuizRanking();
    // Refresh every 10 seconds
    setInterval(fetchQuizRanking, 10000);
});

function fetchQuizRanking() {
    const groupId = {{ $group->id }};
    
    fetch(`/groups/${groupId}/quiz-ranking`)
        .then(response => response.json())
        .then(data => {
            renderRanking(data);
            renderPodium(data);
        })
        .catch(error => {
            console.error('Error fetching ranking:', error);
            document.getElementById('rankingBody').innerHTML = `
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-red-600 dark:text-red-400">
                        Error al cargar el ranking. Intenta nuevamente.
                    </td>
                </tr>
            `;
        });
}

function renderRanking(data) {
    const tbody = document.getElementById('rankingBody');
    const { players, stats } = data;

    // Update stats
    document.getElementById('totalPlayers').textContent = stats.total_players;
    document.getElementById('userPosition').textContent = stats.user_position ? `#${stats.user_position}` : '—';
    document.getElementById('userPoints').textContent = stats.user_points;
    document.getElementById('userTime').textContent = stats.user_time_formatted || '00:00:00';

    if (players.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="px-6 py-8 text-center">
                    <p class="text-gray-500 dark:text-gray-400">No hay jugadores en el ranking aún</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = players.map((player, index) => {
        const isCurrentUser = player.is_current_user;
        const rowClass = isCurrentUser 
            ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500' 
            : 'hover:bg-gray-50 dark:hover:bg-gray-700/50';
        
        const medalEmoji = ['🥇', '🥈', '🥉'][index] || '•';
        
        return `
            <tr class="${rowClass}">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">${medalEmoji}</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-white">#${player.rank}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold overflow-hidden">
                            ${player.avatar 
                                ? `<img src="${player.avatar}" alt="${player.name}" class="w-full h-full object-cover">`
                                : player.name.charAt(0).toUpperCase()
                            }
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">
                                ${player.name}
                                ${isCurrentUser ? '<span class="ml-2 inline-block px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">TÚ</span>' : ''}
                            </p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full font-semibold">
                        ${player.total_points} pts
                    </span>
                </td>
                <td class="px-6 py-4 text-center">
                    <span class="text-gray-600 dark:text-gray-400 font-mono text-sm">
                        ${player.total_time_formatted}
                    </span>
                </td>
            </tr>
        `;
    }).join('');
}

function renderPodium(data) {
    const { players } = data;
    const podiumEl = document.getElementById('podium');
    
    if (players.length === 0) {
        podiumEl.innerHTML = `
            <div class="col-span-3 text-center py-8 text-gray-500 dark:text-gray-400">
                Sin participantes aún
            </div>
        `;
        return;
    }

    const positions = [0, 1, 2]; // 1st, 2nd, 3rd
    const medals = ['🥇', '🥈', '🥉'];
    const ranks = ['first', 'second', 'third'];
    
    podiumEl.innerHTML = positions.map((pos) => {
        const player = players[pos];
        if (!player) return '';
        
        return `
            <div class="text-center">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden transform ${pos === 0 ? 'scale-105 mb-4' : 'mb-8'}">
                    <div class="h-32 bg-gradient-to-br ${
                        pos === 0 ? 'from-yellow-400 to-yellow-600' :
                        pos === 1 ? 'from-gray-300 to-gray-400' :
                        'from-orange-400 to-orange-600'
                    } flex items-center justify-center">
                        <span class="text-6xl">${medals[pos]}</span>
                    </div>
                    <div class="p-4">
                        <div class="h-16 w-16 mx-auto mb-3 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-2xl font-bold overflow-hidden">
                            ${player.avatar 
                                ? `<img src="${player.avatar}" alt="${player.name}" class="w-full h-full object-cover">`
                                : player.name.charAt(0).toUpperCase()
                            }
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">${player.name}</h4>
                        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-2">${player.total_points} pts</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-1">${player.total_time_formatted}</p>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}
</script>
@endsection
