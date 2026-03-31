<!-- Penalty History/Debt Leaderboard Component -->
<div class="penalty-history-container">
    <!-- Header -->
    <div class="mb-6">
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-2">
            <span>🏆 Castigos de Grupo</span>
        </h2>
        <p class="text-gray-600 dark:text-gray-400">Deudas pendientes y castigos cumplidos</p>
    </div>

    <!-- Filtros -->
    <div class="flex gap-2 mb-6 flex-wrap">
        <button
            onclick="filterPenalties('ALL')"
            class="px-4 py-2 rounded font-bold text-sm bg-blue-600 text-white"
            id="filterAll"
        >
            📊 Todos
        </button>
        <button
            onclick="filterPenalties('PENDING')"
            class="px-4 py-2 rounded font-bold text-sm bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white hover:bg-gray-400 transition"
            id="filterPending"
        >
            ⏳ Pendientes
        </button>
        <button
            onclick="filterPenalties('FULFILLED')"
            class="px-4 py-2 rounded font-bold text-sm bg-gray-300 dark:bg-gray-600 text-gray-900 dark:text-white hover:bg-gray-400 transition"
            id="filterFulfilled"
        >
            ✅ Cumplidas
        </button>
    </div>

    <!-- Tabs de Tipo -->
    <div class="flex gap-2 mb-6 border-b border-gray-300 dark:border-gray-600">
        <button
            onclick="filterByType('ALL')"
            class="font-bold px-4 py-3 border-b-2 border-transparent hover:border-blue-600 transition"
            id="typeAll"
            style="border-color: rgb(37, 99, 235);"
        >
            📋 Todos los Tipos
        </button>
        <button
            onclick="filterByType('POINTS')"
            class="font-bold px-4 py-3 border-b-2 border-transparent hover:border-blue-600 transition text-gray-600 dark:text-gray-400"
            id="typePoints"
        >
            💔 Puntos
        </button>
        <button
            onclick="filterByType('SOCIAL')"
            class="font-bold px-4 py-3 border-b-2 border-transparent hover:border-blue-600 transition text-gray-600 dark:text-gray-400"
            id="typeSocial"
        >
            🍽️ Social
        </button>
        <button
            onclick="filterByType('REVENGE')"
            class="font-bold px-4 py-3 border-b-2 border-transparent hover:border-blue-600 transition text-gray-600 dark:text-gray-400"
            id="typeRevenge"
        >
            ⚔️ Venganza
        </button>
    </div>

    <!-- Lista de Castigos -->
    <div id="penaltiesContainer" class="space-y-4">
        <!-- Se carga dinámicamente -->
    </div>
</div>

<script>
    let allPenalties = [];
    let currentFilter = 'ALL';
    let currentTypeFilter = 'ALL';

    function loadPenalties(groupId) {
        fetch(`/api/pre-matches/${groupId}/penalties`)
            .then(res => res.json())
            .then(data => {
                allPenalties = data.penalties || [];
                renderPenalties();
            })
            .catch(err => console.error('Error loading penalties:', err));
    }

    function filterPenalties(status) {
        currentFilter = status;
        document.getElementById('filterAll').classList.remove('bg-blue-600', 'text-white');
        document.getElementById('filterPending').classList.remove('bg-blue-600', 'text-white');
        document.getElementById('filterFulfilled').classList.remove('bg-blue-600', 'text-white');

        document.getElementById('filterAll').classList.add('bg-gray-300', 'dark:bg-gray-600', 'text-gray-900', 'dark:text-white');
        document.getElementById('filterPending').classList.add('bg-gray-300', 'dark:bg-gray-600', 'text-gray-900', 'dark:text-white');
        document.getElementById('filterFulfilled').classList.add('bg-gray-300', 'dark:bg-gray-600', 'text-gray-900', 'dark:text-white');

        document.getElementById('filter' + status).classList.add('bg-blue-600', 'text-white');
        document.getElementById('filter' + status).classList.remove('bg-gray-300', 'dark:bg-gray-600', 'text-gray-900', 'dark:text-white');

        renderPenalties();
    }

    function filterByType(type) {
        currentTypeFilter = type;
        document.querySelectorAll('[id^="type"]').forEach(btn => {
            btn.style.borderColor = 'transparent';
            btn.classList.add('text-gray-600', 'dark:text-gray-400');
        });

        document.getElementById('type' + type).style.borderColor = 'rgb(37, 99, 235)';
        document.getElementById('type' + type).classList.remove('text-gray-600', 'dark:text-gray-400');

        renderPenalties();
    }

    function renderPenalties() {
        let filtered = allPenalties;

        if (currentFilter !== 'ALL') {
            filtered = filtered.filter(p => {
                if (currentFilter === 'PENDING') return !p.fulfilled_at;
                if (currentFilter === 'FULFILLED') return p.fulfilled_at;
            });
        }

        if (currentTypeFilter !== 'ALL') {
            filtered = filtered.filter(p => p.penalty_type === currentTypeFilter);
        }

        if (filtered.length === 0) {
            document.getElementById('penaltiesContainer').innerHTML = `
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p class="text-lg">No hay castigos con estos filtros</p>
                </div>
            `;
            return;
        }

        const html = filtered.map(penalty => `
            <div class="border-l-4 pl-4 py-4 rounded bg-gray-50 dark:bg-gray-700/50 transition"
                style="border-color: ${getPenaltyColor(penalty.penalty_type)};">

                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <img src="${penalty.user.avatar || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(penalty.user.name)}"
                            alt="${penalty.user.name}" class="w-8 h-8 rounded-full object-cover">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">${penalty.user.name}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                ${penalty.created_at ? new Date(penalty.created_at).toLocaleDateString('es-ES') : 'Fecha'}
                            </p>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="px-3 py-1 rounded text-xs font-bold ${getPenaltyBadgeClass(penalty.penalty_type)}">
                            ${getPenaltyLabel(penalty.penalty_type)}
                        </span>
                    </div>
                </div>

                <!-- Descripción del castigo -->
                <div class="mb-3 p-3 bg-white dark:bg-gray-600 rounded text-sm">
                    <p class="text-gray-900 dark:text-white font-medium">${penalty.penalty_description}</p>
                </div>

                <!-- Detalles según tipo -->
                <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                    <div class="p-2 bg-white dark:bg-gray-600 rounded">
                        <p class="text-xs text-gray-600 dark:text-gray-400">Pre Match</p>
                        <p class="font-bold text-gray-900 dark:text-white">
                            ${penalty.pre_match?.home_team?.name || 'Match ?'} vs
                            ${penalty.pre_match?.away_team?.name || '?'}
                        </p>
                    </div>

                    ${penalty.penalty_type === 'POINTS' ? `
                        <div class="p-2 bg-red-50 dark:bg-red-900 rounded">
                            <p class="text-xs text-red-600 dark:text-red-300">Puntos Perdidos</p>
                            <p class="font-bold text-red-800 dark:text-red-200">-${penalty.points_lost}</p>
                        </div>
                    ` : `
                        <div class="p-2 bg-orange-50 dark:bg-orange-900 rounded">
                            <p class="text-xs text-orange-600 dark:text-orange-300">Estado</p>
                            <p class="font-bold text-orange-800 dark:text-orange-200">
                                ${penalty.fulfilled_at ? '✅ Cumplido' : '⏳ Pendiente'}
                            </p>
                        </div>
                    `}
                </div>

                <!-- Estado de Cumplimiento -->
                ${penalty.fulfilled_at ? `
                    <div class="p-3 bg-green-50 dark:bg-green-900 rounded text-sm border-l-4 border-l-green-500">
                        <p class="text-green-800 dark:text-green-200 font-bold">
                            ✅ Cumplido el ${new Date(penalty.fulfilled_at).toLocaleDateString('es-ES')}
                        </p>
                    </div>
                ` : `
                    <div class="p-3 bg-yellow-50 dark:bg-yellow-900 rounded text-sm border-l-4 border-l-yellow-500">
                        <p class="text-yellow-800 dark:text-yellow-200 font-bold">
                            ⏳ Pendiente de cumplimiento
                        </p>
                        <button onclick="markFulfilled(${penalty.id})" class="mt-2 w-full bg-green-600 hover:bg-green-700 text-white py-1 rounded text-xs font-bold transition">
                            ✅ Marcar como Cumplido
                        </button>
                    </div>
                `}
            </div>
        `).join('');

        document.getElementById('penaltiesContainer').innerHTML = html;
    }

    function getPenaltyColor(type) {
        switch(type) {
            case 'POINTS': return 'rgb(239, 68, 68)'; // red
            case 'SOCIAL': return 'rgb(249, 115, 22)'; // orange
            case 'REVENGE': return 'rgb(168, 85, 247)'; // purple
            default: return 'rgb(107, 114, 128)'; // gray
        }
    }

    function getPenaltyLabel(type) {
        switch(type) {
            case 'POINTS': return '💔 Puntos';
            case 'SOCIAL': return '🍽️ Social';
            case 'REVENGE': return '⚔️ Venganza';
            default: return '❓ Desconocido';
        }
    }

    function getPenaltyBadgeClass(type) {
        switch(type) {
            case 'POINTS': return 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
            case 'SOCIAL': return 'bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200';
            case 'REVENGE': return 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200';
            default: return 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200';
        }
    }

    function markFulfilled(penaltyId) {
        fetch(`/api/penalties/${penaltyId}/fulfill`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.message) {
                alert('✅ Castigo marcado como cumplido!');
                location.reload();
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('❌ Error al actualizar');
        });
    }
</script>
