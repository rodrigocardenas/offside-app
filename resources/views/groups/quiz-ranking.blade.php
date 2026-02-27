<!--
    🎮 QUIZ RANKING VIEW
    Vista para mostrar el ranking dinámico del quiz MWC
    Ordena por: Puntos (DESC) y Tiempo (ASC) como desempate
    Usa componentes y estilos de la aplicación
-->

<x-app-layout>
    @section('navigation-title', '🎮 ' . $group->name)

    @php
        // Variables de tema compartidas globalmente
        $isDark = $isDark ?? true;
        $bgPrimary = $bgPrimary ?? '#0a2e2c';
        $bgSecondary = $bgSecondary ?? '#0f3d3a';
        $bgTertiary = $bgTertiary ?? '#1a524e';
        $textPrimary = $textPrimary ?? '#ffffff';
        $textSecondary = $textSecondary ?? '#b0b0b0';
        $borderColor = $borderColor ?? '#2a4a47';
        $componentsBackground = $componentsBackground ?? '#1a524e';
        $accentColor = $accentColor ?? '#00deb0';
        $accentDark = $accentDark ?? '#17b796';
    @endphp

    <div class="min-h-screen" style="background: {{ $bgPrimary }}; padding: 1rem; padding-top: 5rem; padding-bottom: 6rem;">
        <div class="max-w-4xl mx-auto">
            <!-- Header con información del grupo -->
            <div class="mb-8 text-center">
                <div class="flex items-center justify-center gap-4 mb-4">
                    @if($group->logo)
                        <img src="{{ asset('storage/' . $group->logo) }}" alt="{{ $group->name }}" class="h-16 w-16 rounded-lg shadow-md">
                    @endif
                    <div>
                        <h1 class="text-4xl font-bold" style="color: {{ $textPrimary }};">🎮 {{ $group->name }}</h1>
                    </div>
                </div>
                <p class="text-sm font-medium mt-3" style="color: {{ $textSecondary }};">
                    <i class="fas fa-sync-alt mr-2" style="color: {{ $accentColor }};"></i>Ranking actualizado cada 10 segundos
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <!-- Total Players -->
                <div class="rounded-lg p-6 shadow-md transition-shadow hover:shadow-lg" style="background: {{ $componentsBackground }}; border: 1px solid {{ $borderColor }};">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium" style="color: {{ $textSecondary }};">Jugadores</p>
                            <p class="text-3xl font-bold mt-1" style="color: {{ $textPrimary }};" id="totalPlayers">0</p>
                        </div>
                        <div class="p-3 rounded-full" style="background: rgba(0, 222, 176, 0.1);">
                            <i class="fas fa-users text-xl" style="color: {{ $accentColor }};"></i>
                        </div>
                    </div>
                </div>

                <!-- Your Position -->
                <div class="rounded-lg p-6 shadow-md transition-shadow hover:shadow-lg" style="background: {{ $componentsBackground }}; border: 1px solid {{ $borderColor }};">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium" style="color: {{ $textSecondary }};">Tu Posición</p>
                            <p class="text-3xl font-bold mt-1" style="color: {{ $accentColor }};" id="userPosition">—</p>
                        </div>
                        <div class="p-3 rounded-full" style="background: rgba(0, 222, 176, 0.1);">
                            <i class="fas fa-crown text-xl" style="color: {{ $accentColor }};"></i>
                        </div>
                    </div>
                </div>

                <!-- Your Points -->
                <div class="rounded-lg p-6 shadow-md transition-shadow hover:shadow-lg" style="background: {{ $componentsBackground }}; border: 1px solid {{ $borderColor }};">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium" style="color: {{ $textSecondary }};">Tus Puntos</p>
                            <p class="text-3xl font-bold mt-1" style="color: {{ $textPrimary }};" id="userPoints">0</p>
                        </div>
                        <div class="p-3 rounded-full" style="background: rgba(0, 222, 176, 0.1);">
                            <i class="fas fa-star text-xl" style="color: {{ $accentColor }};"></i>
                        </div>
                    </div>
                </div>

                <!-- Your Time -->
                <div class="rounded-lg p-6 shadow-md transition-shadow hover:shadow-lg" style="background: {{ $componentsBackground }}; border: 1px solid {{ $borderColor }};">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium" style="color: {{ $textSecondary }};">Tu Tiempo</p>
                            <p class="text-3xl font-bold mt-1" style="color: {{ $textPrimary }};" id="userTime">00:00:00</p>
                        </div>
                        <div class="p-3 rounded-full" style="background: rgba(0, 222, 176, 0.1);">
                            <i class="fas fa-clock text-xl" style="color: {{ $accentColor }};"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Podium (Top 3) -->
            <div class="mb-12" id="podiumContainer" style="display: none;">
                <h2 class="text-2xl font-bold mb-6" style="color: {{ $textPrimary }};">🏆 Podio</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="podium">
                    <!-- Podium items will be injected here -->
                </div>
            </div>

            <!-- Ranking de todos los jugadores -->
            <div class="rounded-lg shadow-md overflow-hidden" style="background: {{ $componentsBackground }}; border: 1px solid {{ $borderColor }};">
                <div class="px-6 py-4" style="border-bottom: 1px solid {{ $borderColor }};">
                    <h3 class="text-lg font-bold" style="color: {{ $textPrimary }};">📊 Ranking Completo</h3>
                    <p class="text-xs mt-1" style="color: {{ $textSecondary }};">
                        Ordenado por puntos (descendente) y tiempo total (ascendente)
                    </p>
                </div>

                <div id="rankingBody" class="divide-y" style="border-color: {{ $borderColor }};">
                    <!-- Ranking items will be injected here -->
                </div>
            </div>

            <!-- Botón de volver -->
            <div class="mt-8 text-center">
                <a href="{{ route('groups.show', $group) }}" class="inline-flex items-center px-6 py-2 rounded-lg font-medium transition-all duration-300 hover:shadow-lg"
                   style="background: {{ $accentColor }}; color: #000; cursor: pointer;"
                   onmouseover="this.style.background='{{ $accentDark }}'"
                   onmouseout="this.style.background='{{ $accentColor }}'">
                    <i class="fas fa-arrow-left mr-2"></i>Volver al grupo
                </a>
            </div>
        </div>
    </div>

    <!-- Menú inferior fijo -->
    <x-layout.bottom-navigation active-item="grupo" />

    <!-- Modal de Feedback -->
    <x-feedback-modal />

    <script>
    const themeColors = {
        bgPrimary: '{{ $bgPrimary }}',
        bgSecondary: '{{ $bgSecondary }}',
        bgTertiary: '{{ $bgTertiary }}',
        textPrimary: '{{ $textPrimary }}',
        textSecondary: '{{ $textSecondary }}',
        borderColor: '{{ $borderColor }}',
        componentsBackground: '{{ $componentsBackground }}',
        accentColor: '{{ $accentColor }}',
        accentDark: '{{ $accentDark }}'
    };

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
                    <div style="padding: 2rem; text-align: center; color: #ef4444;">
                        <i class="fas fa-exclamation-circle mr-2"></i>Error al cargar el ranking. Intenta nuevamente.
                    </div>
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
                <div style="padding: 3rem 1.5rem; text-align: center; color: ${themeColors.textSecondary};">
                    <i class="fas fa-chart-line text-3xl mb-3" style="display: block; margin-bottom: 1rem; color: ${themeColors.textSecondary};"></i>
                    <p>Aún no hay jugadores en el ranking</p>
                </div>
            `;
            return;
        }

        tbody.innerHTML = players.map((player, index) => {
            const isCurrentUser = player.is_current_user;
            const medalEmoji = ['🥇', '🥈', '🥉'][index] || '•';
            
            let medalColor = '';
            if (index === 0) {
                medalColor = '#fbbf24';
            } else if (index === 1) {
                medalColor = '#d1d5db';
            } else if (index === 2) {
                medalColor = '#f97316';
            } else {
                medalColor = themeColors.accentColor;
            }

            const backgroundColor = isCurrentUser ? 
                `rgba(0, 222, 176, 0.1)` : 
                `${themeColors.bgSecondary}`;
            
            const borderStyle = isCurrentUser ? 
                `4px solid ${themeColors.accentColor}` : 
                `1px solid ${themeColors.borderColor}`;

            return `
                <div style="display: flex; align-items: center; padding: 1rem 1.5rem; background: ${backgroundColor}; border-left: ${borderStyle}; transition: all 0.2s ease; cursor: default; hover-effect: true;"
                     onmouseover="this.style.background='${themeColors.bgTertiary}'"
                     onmouseout="this.style.background='${backgroundColor}'">
                    
                    <!-- Avatar -->
                    <div style="flex-shrink: 0; margin-right: 1rem; position: relative;">
                        <div style="width: 3rem; height: 3rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.25rem; overflow: hidden; border: 3px solid ${medalColor}; background: ${themeColors.accentColor}; color: #000;">
                            ${player.avatar 
                                ? `<img src="${player.avatar}" alt="${player.name}" style="width: 100%; height: 100%; object-fit: cover;">` 
                                : player.name.charAt(0).toUpperCase()
                            }
                        </div>
                        ${index < 3 ? `<div style="position: absolute; bottom: -4px; right: -4px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: ${medalColor}; font-size: 12px; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            ${index === 0 ? '👑' : (index === 1 ? '🎖️' : '🏅')}
                        </div>` : ''}
                    </div>

                    <!-- Player Info -->
                    <div style="flex: 1;">
                        <div style="font-weight: bold; color: ${themeColors.textPrimary};">
                            <span style="font-size: 1.5rem; margin-right: 0.5rem;">${medalEmoji}</span>
                            <span style="margin-right: 0.5rem;">#${player.rank}</span>
                            ${player.name}
                            ${isCurrentUser ? `<span style="margin-left: 0.5rem; padding: 0.25rem 0.5rem; background: ${themeColors.accentColor}; color: #000; border-radius: 0.25rem; font-size: 0.75rem; font-weight: bold;">TÚ</span>` : ''}
                        </div>
                    </div>

                    <!-- Points -->
                    <div style="flex-shrink: 0; text-align: right; margin-right: 2rem;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: ${themeColors.accentColor};">${player.total_points}</div>
                        <div style="font-size: 0.75rem; color: ${themeColors.textSecondary};">pts</div>
                    </div>

                    <!-- Time -->
                    <div style="flex-shrink: 0; text-align: right;">
                        <div style="font-size: 0.875rem; color: ${themeColors.textPrimary}; font-family: monospace; font-weight: 500;">${player.total_time_formatted}</div>
                        <div style="font-size: 0.75rem; color: ${themeColors.textSecondary};">tiempo</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function renderPodium(data) {
        const { players } = data;
        const podiumEl = document.getElementById('podium');
        const podiumContainer = document.getElementById('podiumContainer');

        if (players.length === 0) {
            podiumContainer.style.display = 'none';
            return;
        }

        podiumContainer.style.display = 'block';

        const positions = [0, 1, 2];
        const medals = ['🥇', '🥈', '🥉'];
        const colors = ['#fbbf24', '#d1d5db', '#f97316'];

        podiumEl.innerHTML = positions.map((pos) => {
            const player = players[pos];
            if (!player) return '';

            const scale = pos === 0 ? 'transform: scale(1.05);' : '';
            const marginTop = pos === 0 ? 'margin-bottom: 1rem;' : 'margin-bottom: 2rem;';

            return `
                <div style="text-align: center;">
                    <div style="background: ${themeColors.componentsBackground}; border: 1px solid ${themeColors.borderColor}; border-radius: 0.5rem; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.2); ${scale} ${marginTop}">
                        <div style="height: 8rem; background: linear-gradient(to bottom right, ${colors[pos]}, ${colors[pos]}); display: flex; align-items: center; justify-content: center;">
                            <span style="font-size: 3rem;">${medals[pos]}</span>
                        </div>
                        <div style="padding: 1rem;">
                            <div style="width: 4rem; height: 4rem; margin: 0 auto 0.75rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.5rem; overflow: hidden; background: ${themeColors.accentColor}; color: #000; border: 3px solid ${colors[pos]};">
                                ${player.avatar 
                                    ? `<img src="${player.avatar}" alt="${player.name}" style="width: 100%; height: 100%; object-fit: cover;">` 
                                    : player.name.charAt(0).toUpperCase()
                                }
                            </div>
                            <h4 style="font-size: 1.125rem; font-weight: bold; color: ${themeColors.textPrimary}; margin: 0.5rem 0;">${player.name}</h4>
                            <p style="font-size: 1.5rem; font-weight: bold; color: ${themeColors.accentColor}; margin: 0.5rem 0;">⭐ ${player.total_points} pts</p>
                            <p style="font-size: 0.75rem; color: ${themeColors.textSecondary}; font-family: monospace; margin: 0.5rem 0;">${player.total_time_formatted}</p>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }
    </script>

</x-app-layout>
