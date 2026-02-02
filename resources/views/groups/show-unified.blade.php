<x-app-layout>
    @section('navigation-title', $group->nam>e)>

    @php
        // Tomar tema del usuario
        $themeMode = auth()->user()->theme_mode ?? 'light';
        $isDark = $themeMode === 'dark';

        // Paleta de colores unificada
        $bgPrimary = $isDark ? '#0a2e2c' : '#f5f5f5';
        $bgSecondary = $isDark ? '#0f3d3a' : '#ffffff';
        $bgTertiary = $isDark ? '#1a524e' : '#f9f9f9';
        $textPrimary = $isDark ? '#ffffff' : '#333333';
        $textSecondary = $isDark ? '#b0b0b0' : '#666666';
        $borderColor = $isDark ? '#2a4a47' : '#e0e0e0';
        $accentColor = '#00deb0';
        $accentDark = '#17b796';
    @endphp

    <div class="min-h-screen p-4 md:p-6 pb-24" style="background: {{ $bgPrimary }};">
        <div class="max-w-4xl mx-auto mt-16">
            <!-- Encabezado con imagen del grupo -->
            <div class="mb-8 text-center">
                <div class="flex flex-col md:flex-row items-center justify-center gap-4 mb-4">
                    @if($group->logo)
                        <img src="{{ asset('storage/' . $group->logo) }}" alt="{{ $group->name }}"
                             class="h-16 w-16 rounded-lg shadow-md">
                    @endif
                    <div>
                        {{-- <h1 class="text-3xl md:text-4xl font-bold" style="color: {{ $textPrimary }};">
                            {{ $group->name }}
                        </h1> --}}
                        <p class="text-sm font-medium mt-2" style="color: {{ $textSecondary }};">
                            <i class="fas fa-sync-alt mr-2" style="color: {{ $accentColor }};"></i>
                            Información actualizada
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="mb-6 flex gap-2 md:gap-4 border-b" style="border-color: {{ $borderColor }}; overflow-x-auto; -webkit-overflow-scrolling: touch;">
                <button class="tab-button active px-4 py-3 md:px-6 font-semibold text-sm md:text-base transition-all duration-300 whitespace-nowrap"
                        data-tab="ranking"
                        style="color: {{ $accentColor }}; border-bottom: 3px solid {{ $accentColor }};">
                    <i class="fas fa-crown mr-2"></i>
                    <span class="hidden sm:inline">Ranking</span>
                    <span class="sm:hidden">Ranking</span>
                </button>
                <button class="tab-button px-4 py-3 md:px-6 font-semibold text-sm md:text-base transition-all duration-300 whitespace-nowrap"
                        data-tab="results"
                        style="color: {{ $textSecondary }}; border-bottom: 3px solid transparent;"
                        onmouseover="this.style.color='{{ $accentColor }}';"
                        onmouseout="this.style.color='{{ $textSecondary }}';">
                    <i class="fas fa-chart-line mr-2"></i>
                    <span class="hidden sm:inline">Mis Resultados</span>
                    <span class="sm:hidden">Resultados</span>
                </button>
            </div>

            <!-- Tab: Ranking -->
            <div id="ranking-tab" class="tab-content active">
                <div class="rounded-2xl p-6 shadow-lg" style="background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }};">
                    @if($rankings->isEmpty())
                        <div class="text-center py-12">
                            <i class="fas fa-chart-line text-4xl mb-4" style="color: {{ $textSecondary }};"></i>
                            <p style="color: {{ $textSecondary }};">Aún no hay puntuaciones para mostrar en este grupo.</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($rankings as $index => $user)
                                @php
                                    $medalColor = '';
                                    $medalBg = '';
                                    $medalText = '';
                                    if ($index === 0) {
                                        $medalColor = '#fbbf24';
                                        $medalBg = '#fbbf24';
                                        $medalText = '#000';
                                    } elseif ($index === 1) {
                                        $medalColor = '#d1d5db';
                                        $medalBg = '#d1d5db';
                                        $medalText = '#000';
                                    } elseif ($index === 2) {
                                        $medalColor = '#f97316';
                                        $medalBg = '#f97316';
                                        $medalText = '#fff';
                                    } else {
                                        $medalColor = $accentColor;
                                        $medalBg = $accentDark;
                                        $medalText = '#fff';
                                    }
                                @endphp
                                <div class="flex items-center rounded-xl p-4 transition-all duration-300 hover:shadow-md"
                                     style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }};">
                                    <!-- Avatar con borde coloreado según posición -->
                                    <div class="flex-shrink-0 mr-4 relative">
                                        @if($user->avatar)
                                            <img src="{{ $user->avatar_url }}"
                                                 alt="{{ $user->name }}"
                                                 class="w-12 h-12 md:w-16 md:h-16 rounded-full shadow-lg object-cover"
                                                 style="border: 3px solid {{ $medalColor }};">
                                        @else
                                            <div class="w-12 h-12 md:w-16 md:h-16 rounded-full flex items-center justify-center text-white font-bold text-lg md:text-xl shadow-lg"
                                                 style="background: {{ $accentColor }}; border: 3px solid {{ $medalColor }};">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                        @endif
                                        @if($index < 3)
                                            <div class="absolute -bottom-1 -right-1 w-6 h-6 flex items-center justify-center rounded-full text-xs font-bold shadow-md"
                                                 style="background: {{ $medalBg }}; color: {{ $medalText }};">
                                                @if($index === 0)
                                                    <i class="fas fa-crown text-xs"></i>
                                                @elseif($index === 1)
                                                    <i class="fas fa-medal text-xs"></i>
                                                @else
                                                    <i class="fas fa-award text-xs"></i>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Nombre y información -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-sm md:text-base truncate" style="color: {{ $textPrimary }};">
                                            {{ $user->name }}
                                        </h3>
                                        <p class="text-xs mt-1" style="color: {{ $textSecondary }};">
                                            <i class="fas fa-calendar-alt mr-1" style="color: {{ $accentColor }};"></i>
                                            Miembro desde {{ $user->created_at->format('d/m/Y') }}
                                        </p>
                                    </div>

                                    <!-- Puntuación -->
                                    <div class="text-right flex-shrink-0">
                                        <span class="text-2xl md:text-3xl font-bold block" style="color: {{ $accentColor }};">
                                            {{ $user->total_points ?? 0 }}
                                        </span>
                                        <p class="text-xs mt-1" style="color: {{ $textSecondary }};">pts</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Tab: Resultados (Cargado vía AJAX) -->
            <div id="results-tab" class="tab-content hidden">
                <div id="results-content" class="rounded-2xl p-6 shadow-lg" style="background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }};">
                    <!-- Loading skeleton -->
                    <div class="space-y-4">
                        @for($i = 0; $i < 3; $i++)
                            <div class="rounded-lg p-4 animate-pulse" style="background: {{ $bgTertiary }}; border: 1px solid {{ $borderColor }};">
                                <div class="h-4 rounded mb-2" style="background: {{ $borderColor }}; width: 60%;"></div>
                                <div class="h-3 rounded" style="background: {{ $borderColor }}; width: 40%;"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            <!-- Botón de volver -->
            <div class="mt-8 text-center">
                <a href="{{ route('groups.show', $group) }}"
                   class="inline-flex items-center px-6 py-3 rounded-lg font-medium transition-all duration-300 hover:shadow-lg"
                   style="background: {{ $accentColor }}; color: #000;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al Grupo
                </a>
            </div>
        </div>
    </div>

    <!-- Menú inferior fijo -->
    <x-layout.bottom-navigation active-item="grupo" />

    <!-- Modal de Feedback -->
    <x-feedback-modal />

    <script>
        const themeConfig = {
            bgPrimary: '{{ $bgPrimary }}',
            bgSecondary: '{{ $bgSecondary }}',
            bgTertiary: '{{ $bgTertiary }}',
            textPrimary: '{{ $textPrimary }}',
            textSecondary: '{{ $textSecondary }}',
            borderColor: '{{ $borderColor }}',
            accentColor: '{{ $accentColor }}',
            accentDark: '{{ $accentDark }}'
        };

        const groupId = {{ $group->id }};
        let resultsLoaded = false;

        // Event listeners para tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                switchTab(tabName);
            });
        });

        function switchTab(tabName) {
            // Actualizar botones
            document.querySelectorAll('.tab-button').forEach(btn => {
                if (btn.dataset.tab === tabName) {
                    btn.classList.add('active');
                    btn.style.color = themeConfig.accentColor;
                    btn.style.borderBottomColor = themeConfig.accentColor;
                    btn.style.borderBottomWidth = '3px';
                } else {
                    btn.classList.remove('active');
                    btn.style.color = themeConfig.textSecondary;
                    btn.style.borderBottomColor = 'transparent';
                    btn.style.borderBottomWidth = '3px';
                }
            });

            // Mostrar/ocultar contenido
            document.querySelectorAll('.tab-content').forEach(content => {
                if (content.id === tabName + '-tab') {
                    content.classList.remove('hidden');
                    content.style.display = 'block';

                    // Cargar resultados si es necesario
                    if (tabName === 'results' && !resultsLoaded) {
                        loadResults();
                    }
                } else {
                    content.classList.add('hidden');
                    content.style.display = 'none';
                }
            });

            // Scroll suave en mobile
            if (window.innerWidth < 768) {
                document.querySelector('.tab-content:not(.hidden)').scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        }

        function loadResults() {
            fetch(`/groups/${groupId}/ranking?json=1`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                renderResults(data);
                resultsLoaded = true;
            })
            .catch(error => {
                console.error('Error loading results:', error);
                document.getElementById('results-content').innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-exclamation-circle text-4xl mb-4" style="color: ${themeConfig.textSecondary};"></i>
                        <p style="color: ${themeConfig.textSecondary};">Error al cargar los resultados. Intenta de nuevo.</p>
                    </div>
                `;
            });
        }

        function renderResults(data) {
            const container = document.getElementById('results-content');

            if (!data.groupedAnswers || Object.keys(data.groupedAnswers).length === 0) {
                container.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-4xl mb-4" style="color: ${themeConfig.textSecondary};"></i>
                        <h3 class="mt-2 text-sm font-medium" style="color: ${themeConfig.textPrimary};">No hay resultados aún</h3>
                        <p class="mt-1 text-sm" style="color: ${themeConfig.textSecondary};">
                            Aún no tienes predicciones con resultados verificados en este grupo.
                        </p>
                    </div>
                `;
                return;
            }

            let html = '';

            // Estadísticas
            html += `
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="p-4 rounded-lg text-center" style="background: ${themeConfig.bgTertiary}; border: 1px solid ${themeConfig.borderColor};">
                        <div class="text-2xl font-bold" style="color: ${themeConfig.accentColor};">${data.stats.correct_answers}</div>
                        <div class="text-xs mt-1" style="color: ${themeConfig.textSecondary};">Correctas</div>
                    </div>
                    <div class="p-4 rounded-lg text-center" style="background: ${themeConfig.bgTertiary}; border: 1px solid ${themeConfig.borderColor};">
                        <div class="text-2xl font-bold" style="color: ${themeConfig.accentColor};">${data.stats.accuracy_percentage}%</div>
                        <div class="text-xs mt-1" style="color: ${themeConfig.textSecondary};">Precisión</div>
                    </div>
                    <div class="p-4 rounded-lg text-center" style="background: ${themeConfig.bgTertiary}; border: 1px solid ${themeConfig.borderColor};">
                        <div class="text-2xl font-bold" style="color: ${themeConfig.accentColor};">${data.stats.total_answers}</div>
                        <div class="text-xs mt-1" style="color: ${themeConfig.textSecondary};">Totales</div>
                    </div>
                    <div class="p-4 rounded-lg text-center" style="background: ${themeConfig.bgTertiary}; border: 1px solid ${themeConfig.borderColor};">
                        <div class="text-2xl font-bold" style="color: ${themeConfig.accentColor};">${data.stats.total_points}</div>
                        <div class="text-xs mt-1" style="color: ${themeConfig.textSecondary};">Puntos</div>
                    </div>
                </div>
            `;

            // Resultados por fecha
            for (const [date, answers] of Object.entries(data.groupedAnswers)) {
                const formattedDate = new Date(date).toLocaleDateString('es-ES', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                html += `<div class="mb-4 rounded-xl overflow-hidden" style="background: ${themeConfig.bgTertiary}; border: 1px solid ${themeConfig.borderColor};">`;
                html += `<div class="px-4 md:px-6 py-3 font-bold" style="border-bottom: 1px solid ${themeConfig.borderColor}; color: ${themeConfig.textPrimary};">`;
                html += formattedDate;
                html += `</div>`;

                answers.forEach(answer => {
                    const isCorrect = answer.is_correct;
                    const correctClass = isCorrect ? 'bg-green-500' : 'bg-red-500';
                    const correctIcon = isCorrect ? '✓' : '✗';

                    html += `
                        <div class="px-4 md:px-6 py-4" style="border-bottom: 1px solid ${themeConfig.borderColor};">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium mb-2" style="color: ${themeConfig.textPrimary};">
                                        ${answer.question.title}
                                    </h4>
                                    ${answer.question.football_match ? `
                                        <p class="text-xs mb-3" style="color: ${themeConfig.textSecondary};">
                                            ${answer.question.football_match.home_team} vs ${answer.question.football_match.away_team}
                                        </p>
                                    ` : ''}
                                    <div class="flex flex-wrap gap-2 items-center text-xs mb-2">
                                        <span style="color: ${themeConfig.textSecondary};">Tu respuesta:</span>
                                        <span class="px-2.5 py-0.5 rounded-full text-white ${correctClass}">
                                            ${answer.question_option.text}
                                        </span>
                                        <span style="color: ${isCorrect ? '#10b981' : '#ef4444'};">
                                            <i class="fas fa-${isCorrect ? 'check' : 'times'}"></i>
                                        </span>
                                    </div>
                                    ${!isCorrect && answer.correct_option ? `
                                        <div class="text-xs">
                                            <span style="color: ${themeConfig.textSecondary};">Respuesta correcta:</span>
                                            <span class="px-2.5 py-0.5 rounded-full text-white bg-green-500 ml-2">
                                                ${answer.correct_option.text}
                                            </span>
                                        </div>
                                    ` : !isCorrect && !answer.correct_option && !answer.question.result_verified_at ? `
                                        <div class="text-xs">
                                            <span style="color: ${themeConfig.textSecondary};">Estado:</span>
                                            <span class="px-2.5 py-0.5 rounded-full text-white bg-yellow-500 ml-2">
                                                Pendiente de verificación
                                            </span>
                                            <p style="color: ${themeConfig.textSecondary}; margin-top: 4px;">
                                                Partido en estado: <strong>${answer.question.football_match?.status || 'desconocido'}</strong>
                                            </p>
                                        </div>
                                    ` : !isCorrect && !answer.correct_option ? `
                                        <div class="text-xs">
                                            <span style="color: ${themeConfig.textSecondary};">Resultado:</span>
                                            <span class="px-2.5 py-0.5 rounded-full text-white bg-red-500 ml-2">
                                                Respuesta Incorrecta
                                            </span>
                                        </div>
                                    ` : ''}
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <div class="text-lg font-bold" style="color: ${themeConfig.accentColor};">
                                        ${answer.points_earned} pts
                                    </div>
                                    <div class="text-xs" style="color: ${themeConfig.textSecondary};">
                                        ${new Date(answer.created_at).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += `</div>`;
            }

            container.innerHTML = html;
        }
    </script>

    <style>
        .tab-button {
            position: relative;
            cursor: pointer;
        }

        .tab-content {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile optimizations */
        @media (max-width: 640px) {
            .tab-button {
                font-size: 0.875rem;
                padding: 0.75rem 1rem !important;
            }

            .tab-content {
                border-radius: 0.75rem;
            }
        }
    </style>
</x-app-layout>
