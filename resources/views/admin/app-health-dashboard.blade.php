@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $timezone = config('app.timezone', 'UTC');
@endphp

@section('content')
<div class="min-h-screen bg-slate-950 py-12 text-white">
    <div
        id="app-health-dashboard"
        class="mx-auto flex max-w-7xl flex-col gap-10 px-6"
        data-endpoint="{{ route('admin.app-health-dashboard.data') }}"
        data-hours="{{ $hours }}"
        data-trend-days="{{ $trendDays }}"
        data-refresh="60"
        data-initial='@json($dashboard)'
    >
        <header class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-300">Panel general</p>
                <h1 class="mt-3 text-4xl font-semibold">Salud de la aplicación</h1>
                <p class="mt-2 max-w-2xl text-base text-slate-400">
                    Observa la actividad global en tiempo real: respuestas, crecimiento de usuarios y sesiones recientes.
                    Usa este tablero para detectar picos, caídas o comportamientos atípicos sin salir del navegador.
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <form method="GET" action="{{ route('admin.app-health-dashboard') }}" class="flex flex-col gap-2 text-sm text-slate-300">
                    <label for="hours" class="text-xs font-semibold uppercase tracking-widest text-slate-400">Rango dinámico</label>
                    <div class="flex items-center gap-3">
                        <select id="hours" name="hours" class="rounded-lg border border-slate-700 bg-slate-900/80 px-4 py-2 focus:border-sky-400 focus:outline-none">
                            @foreach([6, 12, 24, 48, 72, 168] as $option)
                                <option value="{{ $option }}" @selected($hours === $option)>{{ $option }}h</option>
                            @endforeach
                        </select>
                        <select id="trend_days" name="trend_days" class="rounded-lg border border-slate-700 bg-slate-900/80 px-4 py-2 focus:border-sky-400 focus:outline-none">
                            @foreach([7, 14, 21, 30] as $option)
                                <option value="{{ $option }}" @selected($trendDays === $option)>{{ $option }} días</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-lg bg-sky-500/90 px-4 py-2 font-semibold text-white hover:bg-sky-400">Aplicar</button>
                    </div>
                </form>
                <button id="app-health-refresh" type="button" class="rounded-lg border border-sky-300/40 px-4 py-2 text-sm font-semibold tracking-wide text-sky-200 transition-colors hover:bg-sky-500/10">
                    Auto-refresh: ON
                </button>
            </div>
        </header>

        <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-900/40 p-6 shadow-lg shadow-sky-500/10">
                <p class="text-xs uppercase tracking-[0.4em] text-slate-500">Respuestas</p>
                <h3 class="mt-4 text-4xl font-semibold" data-stat="answers_last_hours">{{ $dashboard['summary']['answers_last_hours'] }}</h3>
                <p class="mt-1 text-sm text-slate-400">En las últimas {{ $hours }} horas</p>
            </article>
            <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-emerald-900/40 to-emerald-500/10 p-6 shadow-lg shadow-emerald-500/15">
                <p class="text-xs uppercase tracking-[0.4em] text-emerald-200">Usuarios nuevos</p>
                <h3 class="mt-4 text-4xl font-semibold text-emerald-100" data-stat="new_users_24h">{{ $dashboard['summary']['new_users_24h'] }}</h3>
                <p class="mt-1 text-sm text-emerald-200">Registrados en 24 horas ({{ $dashboard['summary']['new_users_7d'] }} en 7 días)</p>
            </article>
            <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-amber-900/30 to-amber-500/10 p-6 shadow-lg shadow-amber-500/15">
                <p class="text-xs uppercase tracking-[0.4em] text-amber-200">Verificaciones</p>
                <h3 class="mt-4 text-4xl font-semibold text-amber-100" data-stat="verified_questions_24h">{{ $dashboard['summary']['verified_questions_24h'] }}</h3>
                <p class="mt-1 text-sm text-amber-200">Preguntas validadas en las últimas 24 horas</p>
            </article>
            <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-cyan-900/30 to-cyan-500/10 p-6 shadow-lg shadow-cyan-500/10">
                <p class="text-xs uppercase tracking-[0.4em] text-cyan-200">Logins</p>
                <h3 class="mt-4 text-4xl font-semibold text-cyan-100" data-stat="logins_24h">{{ $dashboard['summary']['logins_24h'] }}</h3>
                <p class="mt-1 text-sm text-cyan-200">Sesiones registradas en 24 horas</p>
            </article>
            <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-fuchsia-900/30 to-fuchsia-500/10 p-6 shadow-lg shadow-fuchsia-500/10">
                <p class="text-xs uppercase tracking-[0.4em] text-fuchsia-200">Actividad semanal</p>
                <h3 class="mt-4 text-4xl font-semibold text-fuchsia-100" data-stat="answers_last_7d">{{ $dashboard['summary']['answers_last_7d'] }}</h3>
                <p class="mt-1 text-sm text-fuchsia-200">Total de respuestas en 7 días</p>
            </article>
        </section>

        <section class="rounded-3xl border border-slate-800 bg-slate-900/40 p-6 shadow-2xl shadow-slate-950/30">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-semibold">Tendencia de participación</h2>
                    <p class="text-sm text-slate-400">Comparativa diaria de respuestas vs usuarios nuevos</p>
                </div>
                <span class="text-xs uppercase tracking-[0.35em] text-slate-500">Últimos {{ $trendDays }} días</span>
            </div>
            <div class="mt-6">
                <canvas id="app-health-chart" height="120"></canvas>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <article class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6">
                <header class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold">Últimas preguntas contestadas</h3>
                        <p class="text-sm text-slate-400">Detalle de los últimos 8 envíos</p>
                    </div>
                    <span class="text-xs uppercase tracking-[0.35em] text-slate-500">Live</span>
                </header>
                <div class="mt-5 flex flex-col divide-y divide-slate-800" data-list="recent-answers">
                    @forelse($dashboard['recent_answers'] as $answer)
                        <div class="py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-white">{{ Str::limit($answer['title'] ?? 'Pregunta sin título', 80) }}</p>
                                    <p class="text-xs text-slate-400">{{ $answer['group_name'] ?? 'Grupo general' }}</p>
                                </div>
                                <span class="text-xs font-semibold {{ $answer['is_correct'] ? 'text-emerald-300' : 'text-rose-300' }}">
                                    {{ $answer['is_correct'] ? 'Correcta' : 'Incorrecta' }}
                                </span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-400">
                                <span>Por {{ $answer['user']['unique_id'] ?? 'Usuario desconocido' }}</span>
                                <span>{{ ($answer['answered_at'] ?? null) ? $answer['answered_at']->tz($timezone)->diffForHumans() : '—' }}</span>
                                <span class="text-emerald-200 font-semibold">+{{ $answer['points'] ?? 0 }} pts</span>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-400">Sin respuestas recientes.</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6">
                <header class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold">Usuarios nuevos</h3>
                        <p class="text-sm text-slate-400">Últimos 8 registros</p>
                    </div>
                    <span class="text-xs uppercase tracking-[0.35em] text-slate-500">Onboarding</span>
                </header>
                <div class="mt-5 flex flex-col divide-y divide-slate-800" data-list="recent-users">
                    @forelse($dashboard['recent_users'] as $user)
                        <div class="py-4 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ $user['name'] }}</p>
                                <p class="text-xs text-slate-400">{{ $user['email'] }}</p>
                            </div>
                            <div class="text-right text-xs text-slate-400">
                                <p class="font-mono text-slate-200">{{ $user['unique_id'] }}</p>
                                <p>{{ ($user['created_at'] ?? null) ? $user['created_at']->tz($timezone)->diffForHumans() : '—' }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-400">Sin registros recientes.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6">
            <header class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-semibold">Logins recientes</h3>
                    <p class="text-sm text-slate-400">Monitorea qué usuarios están activos ahora mismo.</p>
                </div>
                <span class="text-xs uppercase tracking-[0.35em] text-slate-500">Sesiones</span>
            </header>
            <div class="mt-5 overflow-hidden rounded-xl border border-slate-800/70">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/70 text-xs uppercase tracking-[0.35em] text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Usuario</th>
                            <th class="px-4 py-3 text-left">Dispositivo</th>
                            <th class="px-4 py-3 text-left">IP</th>
                            <th class="px-4 py-3 text-left">Hora</th>
                        </tr>
                    </thead>
                    <tbody data-table="recent-logins" class="divide-y divide-slate-800/80">
                        @forelse($dashboard['recent_logins'] as $login)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-white">{{ $login['user']['name'] ?? 'Anónimo' }}</p>
                                    <p class="text-xs text-slate-400">{{ $login['user']['unique_id'] ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-3 text-slate-300">{{ $login['device'] ?? 'Sin datos' }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ $login['ip_address'] ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-400">{{ ($login['logged_in_at'] ?? null) ? $login['logged_in_at']->tz($timezone)->format('d/m H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-5 text-center text-slate-400">Sin datos de sesión todavía.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('app-health-dashboard');
        if (!root) {
            return;
        }

        const state = {
            endpoint: root.dataset.endpoint,
            hours: parseInt(root.dataset.hours, 10) || 24,
            trendDays: parseInt(root.dataset.trendDays, 10) || 7,
            refreshDelay: (parseInt(root.dataset.refresh, 10) || 60) * 1000,
            autoRefresh: true,
            timerId: null,
            data: JSON.parse(root.dataset.initial || '{}'),
        };

        const elements = {
            stats: {
                answersHours: document.querySelector('[data-stat="answers_last_hours"]'),
                answers7d: document.querySelector('[data-stat="answers_last_7d"]'),
                newUsers24h: document.querySelector('[data-stat="new_users_24h"]'),
                newUsers7d: document.querySelector('[data-stat="new_users_7d"]'),
                verified24h: document.querySelector('[data-stat="verified_questions_24h"]'),
                logins24h: document.querySelector('[data-stat="logins_24h"]'),
            },
            lists: {
                answers: document.querySelector('[data-list="recent-answers"]'),
                users: document.querySelector('[data-list="recent-users"]'),
            },
            tableLogins: document.querySelector('[data-table="recent-logins"]'),
            toggle: document.getElementById('app-health-refresh'),
            chartCtx: document.getElementById('app-health-chart'),
        };

        const chart = new Chart(elements.chartCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    {
                        label: 'Respuestas',
                        data: [],
                        borderColor: '#38bdf8',
                        backgroundColor: 'rgba(56, 189, 248, 0.2)',
                        tension: 0.4,
                        fill: true,
                    },
                    {
                        label: 'Usuarios nuevos',
                        data: [],
                        borderColor: '#34d399',
                        backgroundColor: 'rgba(52, 211, 153, 0.2)',
                        tension: 0.4,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#94a3b8',
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.2)',
                        },
                    },
                    x: {
                        ticks: {
                            color: '#94a3b8',
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.2)',
                        },
                    },
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#cbd5f5',
                        },
                    },
                },
            },
        });

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function relativeTime(value) {
            if (!value) {
                return '—';
            }
            const formatter = new Intl.RelativeTimeFormat('es', { numeric: 'auto' });
            const diffMs = new Date(value).getTime() - Date.now();
            const diffHours = diffMs / 3600000;
            if (Math.abs(diffHours) < 24) {
                const diffMinutes = diffMs / 60000;
                if (Math.abs(diffMinutes) < 60) {
                    return formatter.format(Math.round(diffMinutes), 'minute');
                }
                return formatter.format(Math.round(diffHours), 'hour');
            }
            const diffDays = diffHours / 24;
            return formatter.format(Math.round(diffDays), 'day');
        }

        function absoluteTime(value) {
            if (!value) {
                return '—';
            }
            return new Intl.DateTimeFormat('es-MX', {
                dateStyle: 'short',
                timeStyle: 'short',
            }).format(new Date(value));
        }

        function renderAnswers(list) {
            if (!list.length) {
                return '<p class="py-6 text-center text-sm text-slate-400">Sin respuestas recientes.</p>';
            }
            return list
                .map((answer) => {
                    const statusClass = answer.is_correct ? 'text-emerald-300' : 'text-rose-300';
                    const statusText = answer.is_correct ? 'Correcta' : 'Incorrecta';
                    const username = answer.user?.unique_id || 'Usuario desconocido';
                    const answeredAt = relativeTime(answer.answered_at);
                    const points = answer.points ?? 0;
                    return `
                        <div class="py-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-semibold text-white">${escapeHtml(answer.title || 'Pregunta sin título')}</p>
                                    <p class="text-xs text-slate-400">${escapeHtml(answer.group_name || 'Grupo general')}</p>
                                </div>
                                <span class="text-xs font-semibold ${statusClass}">${statusText}</span>
                            </div>
                            <div class="mt-2 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-400">
                                <span>Por ${escapeHtml(username)}</span>
                                <span>${escapeHtml(answeredAt)}</span>
                                <span class="text-emerald-200 font-semibold">+${escapeHtml(points)} pts</span>
                            </div>
                        </div>
                    `;
                })
                .join('');
        }

        function renderUsers(list) {
            if (!list.length) {
                return '<p class="py-6 text-center text-sm text-slate-400">Sin registros recientes.</p>';
            }
            return list
                .map((user) => `
                    <div class="py-4 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold">${escapeHtml(user.name || 'Usuario')}</p>
                            <p class="text-xs text-slate-400">${escapeHtml(user.email || 'sin correo')}</p>
                        </div>
                        <div class="text-right text-xs text-slate-400">
                            <p class="font-mono text-slate-200">${escapeHtml(user.unique_id || '—')}</p>
                            <p>${escapeHtml(relativeTime(user.created_at))}</p>
                        </div>
                    </div>
                `)
                .join('');
        }

        function renderLogins(list) {
            if (!list.length) {
                return '<tr><td colspan="4" class="px-4 py-5 text-center text-slate-400">Sin datos de sesión todavía.</td></tr>';
            }
            return list
                .map((login) => `
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-white">${escapeHtml(login.user?.name || 'Anónimo')}</p>
                            <p class="text-xs text-slate-400">${escapeHtml(login.user?.unique_id || '—')}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-300">${escapeHtml(login.device || 'Sin datos')}</td>
                        <td class="px-4 py-3 text-slate-400">${escapeHtml(login.ip_address || '—')}</td>
                        <td class="px-4 py-3 text-slate-400">${escapeHtml(absoluteTime(login.logged_in_at))}</td>
                    </tr>
                `)
                .join('');
        }

        function updateStats(summary) {
            if (!summary) {
                return;
            }
            if (elements.stats.answersHours) elements.stats.answersHours.textContent = summary.answers_last_hours ?? 0;
            if (elements.stats.answers7d) elements.stats.answers7d.textContent = summary.answers_last_7d ?? 0;
            if (elements.stats.newUsers24h) elements.stats.newUsers24h.textContent = summary.new_users_24h ?? 0;
            if (elements.stats.newUsers7d) elements.stats.newUsers7d.textContent = summary.new_users_7d ?? 0;
            if (elements.stats.verified24h) elements.stats.verified24h.textContent = summary.verified_questions_24h ?? 0;
            if (elements.stats.logins24h) elements.stats.logins24h.textContent = summary.logins_24h ?? 0;
        }

        function updateChart(trends) {
            if (!trends) {
                return;
            }
            const labels = (trends.answers || []).map((item) => item.label);
            chart.data.labels = labels;
            chart.data.datasets[0].data = (trends.answers || []).map((item) => item.total || 0);
            chart.data.datasets[1].data = (trends.new_users || []).map((item) => item.total || 0);
            chart.update();
        }

        function refreshLists(data) {
            if (elements.lists.answers) {
                elements.lists.answers.innerHTML = renderAnswers(data.recent_answers || []);
            }
            if (elements.lists.users) {
                elements.lists.users.innerHTML = renderUsers(data.recent_users || []);
            }
            if (elements.tableLogins) {
                elements.tableLogins.innerHTML = renderLogins(data.recent_logins || []);
            }
        }

        function applyData(payload) {
            state.data = payload;
            updateStats(payload.summary);
            updateChart(payload.trends);
            refreshLists(payload);
        }

        function fetchData() {
            return fetch(`${state.endpoint}?hours=${state.hours}&trend_days=${state.trendDays}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((response) => response.json())
                .then((json) => json.data || {})
                .catch((error) => {
                    console.error('No se pudo refrescar el dashboard general', error);
                    return state.data || {};
                });
        }

        function refresh() {
            fetchData().then(applyData);
        }

        function startTimer() {
            stopTimer();
            state.timerId = setInterval(refresh, state.refreshDelay);
        }

        function stopTimer() {
            if (state.timerId) {
                clearInterval(state.timerId);
                state.timerId = null;
            }
        }

        if (elements.toggle) {
            elements.toggle.addEventListener('click', () => {
                state.autoRefresh = !state.autoRefresh;
                elements.toggle.textContent = state.autoRefresh ? 'Auto-refresh: ON' : 'Auto-refresh: OFF';
                elements.toggle.classList.toggle('opacity-50', !state.autoRefresh);
                if (state.autoRefresh) {
                    startTimer();
                    refresh();
                } else {
                    stopTimer();
                }
            });
        }

        // Inicializar con datos de la vista
        applyData(state.data || {});
        startTimer();
    });
</script>
@endpush
