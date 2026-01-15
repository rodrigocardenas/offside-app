@extends('layouts.app')

@php
    use Illuminate\Support\Str;
    $timezone = config('app.timezone', 'UTC');
@endphp

@section('content')
<div class="min-h-screen bg-slate-950 py-12 text-white">
    <div
        id="verification-dashboard-root"
        class="mx-auto flex max-w-7xl flex-col gap-10 px-6"
        data-endpoint="{{ route('admin.verification-dashboard.data') }}"
        data-hours="{{ $hours }}"
        data-refresh="60"
    >
        <header class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-emerald-300">Fase 5 · Monitoreo</p>
                <h1 class="mt-3 text-4xl font-semibold">Dashboard de verificación</h1>
                <p class="mt-2 max-w-2xl text-base text-slate-400">
                    Supervisa la salud de los jobs Gemini y la asignación de puntos sin abrir la consola. Esta vista cruza
                    métricas de <span class="text-emerald-300">verification_runs</span> y resalta fallas para actuar en minutos.
                </p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <form method="GET" action="{{ route('admin.verification-dashboard') }}" class="flex flex-col gap-2 text-sm text-slate-300">
                    <label for="hours" class="text-xs font-semibold uppercase tracking-widest text-slate-400">Rango (horas)</label>
                    <div class="flex items-center gap-3">
                        <select id="hours" name="hours" class="rounded-lg border border-slate-700 bg-slate-900/80 px-4 py-2 focus:border-emerald-400 focus:outline-none">
                            @foreach([6, 12, 24, 48, 72, 168] as $option)
                                <option value="{{ $option }}" @selected($hours === $option)>{{ $option }}h</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-lg bg-emerald-500/80 px-4 py-2 font-semibold text-white hover:bg-emerald-500">Actualizar</button>
                    </div>
                </form>
                <button id="auto-refresh-toggle" type="button" class="rounded-lg border border-emerald-400/40 px-4 py-2 text-sm font-semibold tracking-wide text-emerald-200 transition-colors hover:bg-emerald-500/10">
                    Auto-refresh: ON
                </button>
            </div>
        </header>

        <div class="flex flex-wrap gap-6 text-sm text-slate-400">
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase tracking-[0.3em] text-slate-500">Última ejecución</span>
                <span class="font-semibold text-slate-100" data-label="last-run">
                    {{ ($dashboard['summary']['last_run_at'] ?? null) ? $dashboard['summary']['last_run_at']->tz($timezone)->diffForHumans() : 'sin datos' }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase tracking-[0.3em] text-slate-500">Último fallo</span>
                <span class="font-semibold text-rose-200" data-label="last-failure">
                    {{ ($dashboard['summary']['last_failure_at'] ?? null) ? $dashboard['summary']['last_failure_at']->tz($timezone)->diffForHumans() : 'sin registros' }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase tracking-[0.3em] text-slate-500">Actualizado</span>
                <span class="font-semibold text-emerald-200" data-label="last-sync">{{ now()->tz($timezone)->format('d/m H:i:s') }}</span>
                <span class="text-xs text-slate-500" data-label="loading-state" hidden>Actualizando…</span>
            </div>
        </div>

        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-900/50 p-6 shadow-2xl shadow-emerald-500/5">
                <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Runs procesados</p>
                <p class="mt-4 text-4xl font-semibold" data-stat="total_runs">{{ $dashboard['summary']['total_runs'] }}</p>
                <p class="mt-1 text-sm text-slate-400">En las últimas {{ $hours }} horas.</p>
            </article>
            <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-emerald-900/40 to-emerald-500/10 p-6 shadow-2xl shadow-emerald-500/20">
                <p class="text-sm uppercase tracking-[0.3em] text-emerald-200">Runs exitosos</p>
                <p class="mt-4 text-4xl font-semibold text-emerald-100" data-stat="success_count">{{ $dashboard['summary']['success_count'] }}</p>
                <p class="mt-1 text-sm text-emerald-200">Tasa: <span data-stat="success_rate">{{ number_format($dashboard['summary']['success_rate'], 1) }}</span>%</p>
            </article>
            <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-rose-900/40 to-rose-500/10 p-6 shadow-2xl shadow-rose-500/10">
                <p class="text-sm uppercase tracking-[0.3em] text-rose-200">Runs con fallo</p>
                <p class="mt-4 text-4xl font-semibold text-rose-100" data-stat="failure_count">{{ $dashboard['summary']['failure_count'] }}</p>
                <p class="mt-1 text-sm text-rose-200">Incluye timeouts y errores Gemini.</p>
            </article>
            <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-sky-900/40 to-cyan-600/10 p-6 shadow-2xl shadow-cyan-500/10">
                <p class="text-sm uppercase tracking-[0.3em] text-cyan-200">Duración promedio</p>
                <p class="mt-4 text-4xl font-semibold text-cyan-100" data-stat="avg_duration">{{ number_format($dashboard['summary']['avg_duration_seconds'], 2) }} s</p>
                <p class="mt-1 text-sm text-cyan-200">Referencia para detectar cuellos de botella.</p>
            </article>
        </section>

        <section class="space-y-4">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-2xl font-semibold">Salud por job</h2>
                    <p class="text-sm text-slate-400">Distribución de ejecuciones agrupadas por clase.</p>
                </div>
                <span class="rounded-full border border-slate-800 px-4 py-1 text-xs uppercase tracking-[0.3em] text-slate-400">Ventana: {{ $hours }}h</span>
            </div>
            <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/40">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/60 text-xs uppercase tracking-[0.3em] text-slate-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Job</th>
                            <th class="px-4 py-3 text-left">Total</th>
                            <th class="px-4 py-3 text-left">Éxitos</th>
                            <th class="px-4 py-3 text-left">Fallos</th>
                            <th class="px-4 py-3 text-left">Tasa éxito</th>
                            <th class="px-4 py-3 text-left">Duración prom.</th>
                            <th class="px-4 py-3 text-left">Última corrida</th>
                        </tr>
                    </thead>
                    <tbody data-table="per_job" class="divide-y divide-slate-800/80 text-slate-200">
                        @forelse($dashboard['per_job'] as $job)
                            <tr>
                                <td class="px-4 py-4 font-mono text-sm text-slate-300">{{ Str::afterLast($job['job_name'], '\\') }}</td>
                                <td class="px-4 py-4">{{ $job['total'] }}</td>
                                <td class="px-4 py-4 text-emerald-200">{{ $job['success'] }}</td>
                                <td class="px-4 py-4 text-rose-300">{{ $job['failed'] }}</td>
                                <td class="px-4 py-4 font-semibold {{ $job['success_rate'] >= 90 ? 'text-emerald-300' : ($job['success_rate'] >= 70 ? 'text-amber-200' : 'text-rose-300') }}">{{ number_format($job['success_rate'], 1) }}%</td>
                                <td class="px-4 py-4">{{ number_format($job['avg_duration_seconds'], 2) }} s</td>
                                <td class="px-4 py-4 text-slate-400">{{ ($job['last_run_at'] ?? null) ? $job['last_run_at']->tz($timezone)->diffForHumans() : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-slate-400">Sin ejecuciones registradas para el rango seleccionado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <article class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
                <header class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold">Alertas recientes</h3>
                        <p class="text-sm text-slate-400">Últimos fallos registrados en verification_runs.</p>
                    </div>
                    <span class="rounded-full border border-rose-500/40 px-3 py-1 text-xs font-semibold uppercase tracking-[0.3em] text-rose-200">{{ count($dashboard['recent_failures']) }} activos</span>
                </header>
                <div class="mt-5 flex flex-col gap-4" data-list="failures">
                    @forelse($dashboard['recent_failures'] as $failure)
                        <div class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm font-semibold text-rose-200">
                                    {{ Str::afterLast($failure['job_name'], '\\') }} · Run #{{ $failure['id'] }}
                                </div>
                                <span class="text-xs text-rose-200">{{ ($failure['started_at'] ?? null) ? $failure['started_at']->tz($timezone)->format('d/m H:i:s') : '—' }}</span>
                            </div>
                            <p class="mt-3 text-sm text-rose-100">{{ $failure['error_message'] ?: 'Error no especificado' }}</p>
                            @php
                                $failureTags = collect($failure['metrics'] ?? [])->merge($failure['context'] ?? [])->take(4)->map(function ($value, $key) {
                                    $label = Str::headline(str_replace('_', ' ', $key));
                                    $display = is_array($value) ? json_encode($value) : $value;
                                    return [$label, $display];
                                });
                            @endphp
                            @if($failureTags->isNotEmpty())
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($failureTags as [$label, $display])
                                        <span class="rounded-full bg-rose-500/10 px-3 py-1 text-xs text-rose-100">{{ $label }}: {{ $display }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="py-12 text-center text-sm text-slate-400">Sin errores en el rango seleccionado. 🎉</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
                <header class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold">Ejecuciones más recientes</h3>
                        <p class="text-sm text-slate-400">Detalle de los últimos 12 registros.</p>
                    </div>
                    <span class="text-xs uppercase tracking-[0.3em] text-slate-500">Live feed</span>
                </header>
                <div class="mt-5 overflow-hidden rounded-xl border border-slate-800/80">
                    <table class="min-w-full divide-y divide-slate-800 text-sm">
                        <thead class="bg-slate-900/60 text-xs uppercase tracking-[0.3em] text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-left">Run</th>
                                <th class="px-4 py-3 text-left">Job</th>
                                <th class="px-4 py-3 text-left">Estado</th>
                                <th class="px-4 py-3 text-left">Duración</th>
                                <th class="px-4 py-3 text-left">Inicio</th>
                                <th class="px-4 py-3 text-left">Métricas</th>
                            </tr>
                        </thead>
                        <tbody data-table="recent_runs" class="divide-y divide-slate-800/80">
                            @forelse($dashboard['recent_runs'] as $run)
                                @php
                                    $statusColor = match ($run['status']) {
                                        'success' => 'bg-emerald-500/10 text-emerald-200 border border-emerald-500/30',
                                        'running' => 'bg-amber-500/10 text-amber-200 border border-amber-500/30',
                                        default => 'bg-rose-500/10 text-rose-200 border border-rose-500/30',
                                    };
                                    $metricTags = collect($run['metrics'] ?? [])->take(3)->map(function ($value, $key) {
                                        $label = Str::headline(str_replace('_', ' ', $key));
                                        $display = is_array($value) ? json_encode($value) : $value;
                                        return [$label, $display];
                                    });
                                @endphp
                                <tr>
                                    <td class="px-4 py-4 font-mono text-xs text-slate-400">#{{ $run['id'] }}</td>
                                    <td class="px-4 py-4 font-semibold text-slate-200">{{ Str::afterLast($run['job_name'], '\\') }}</td>
                                    <td class="px-4 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs {{ $statusColor }}">{{ Str::upper($run['status']) }}</span>
                                    </td>
                                    <td class="px-4 py-4 text-slate-300">{{ $run['duration_seconds'] ? number_format($run['duration_seconds'], 2) . ' s' : '—' }}</td>
                                    <td class="px-4 py-4 text-slate-400">{{ ($run['started_at'] ?? null) ? $run['started_at']->tz($timezone)->format('d/m H:i:s') : '—' }}</td>
                                    <td class="px-4 py-4">
                                        @if($metricTags->isEmpty())
                                            <span class="text-xs text-slate-500">sin métricas</span>
                                        @else
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($metricTags as [$label, $display])
                                                    <span class="rounded-full bg-slate-800/80 px-3 py-1 text-xs text-slate-300">{{ $label }}: {{ $display }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-400">No hay ejecuciones registradas todavía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('verification-dashboard-root');
        if (!root) {
            return;
        }

        const state = {
            endpoint: root.dataset.endpoint,
            hours: parseInt(root.dataset.hours, 10) || 24,
            refreshDelay: (parseInt(root.dataset.refresh, 10) || 60) * 1000,
            autoRefresh: true,
            timerId: null,
        };

        const elements = {
            stats: {
                total: document.querySelector('[data-stat="total_runs"]'),
                success: document.querySelector('[data-stat="success_count"]'),
                failures: document.querySelector('[data-stat="failure_count"]'),
                successRate: document.querySelector('[data-stat="success_rate"]'),
                avgDuration: document.querySelector('[data-stat="avg_duration"]'),
            },
            labels: {
                lastRun: document.querySelector('[data-label="last-run"]'),
                lastFailure: document.querySelector('[data-label="last-failure"]'),
                lastSync: document.querySelector('[data-label="last-sync"]'),
                loading: document.querySelector('[data-label="loading-state"]'),
            },
            tables: {
                perJob: document.querySelector('[data-table="per_job"]'),
                recentRuns: document.querySelector('[data-table="recent_runs"]'),
            },
            failures: document.querySelector('[data-list="failures"]'),
            toggle: document.getElementById('auto-refresh-toggle'),
        };

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatDate(value) {
            if (!value) {
                return '—';
            }
            return new Intl.DateTimeFormat('es-MX', {
                dateStyle: 'short',
                timeStyle: 'medium',
            }).format(new Date(value));
        }

        function formatRelative(value) {
            if (!value) {
                return 'sin datos';
            }
            const formatter = new Intl.RelativeTimeFormat('es', { numeric: 'auto' });
            const diffMs = new Date(value).getTime() - Date.now();
            const diffMinutes = diffMs / 60000;
            if (Math.abs(diffMinutes) < 60) {
                return formatter.format(Math.round(diffMinutes), 'minute');
            }
            const diffHours = diffMinutes / 60;
            return formatter.format(Math.round(diffHours), 'hour');
        }

        function successRateColor(rate) {
            if (rate >= 90) {
                return 'text-emerald-300';
            }
            if (rate >= 70) {
                return 'text-amber-200';
            }
            return 'text-rose-300';
        }

        function statusPill(status) {
            const normalized = String(status).toLowerCase();
            if (normalized === 'success') {
                return 'bg-emerald-500/10 text-emerald-200 border border-emerald-500/30';
            }
            if (normalized === 'running') {
                return 'bg-amber-500/10 text-amber-200 border border-amber-500/30';
            }
            return 'bg-rose-500/10 text-rose-200 border border-rose-500/30';
        }

        function formatDuration(seconds) {
            if (!seconds) {
                return '—';
            }
            return `${Number(seconds).toFixed(2)} s`;
        }

        function formatJobName(name) {
            const parts = String(name ?? '').split('\\');
            return parts[parts.length - 1] || name;
        }

        function renderTags(data) {
            if (!data || typeof data !== 'object') {
                return '';
            }
            const entries = Object.entries(data).slice(0, 4);
            if (!entries.length) {
                return '';
            }
            return entries
                .map(([key, value]) => {
                    const label = key.replace(/_/g, ' ');
                    const display = typeof value === 'object' ? JSON.stringify(value) : value;
                    return `<span class="rounded-full bg-slate-800/80 px-3 py-1 text-xs text-slate-300">${escapeHtml(label)}: ${escapeHtml(display)}</span>`;
                })
                .join('');
        }

        function renderFailureTags(metrics, context) {
            const payload = Object.assign({}, context || {}, metrics || {});
            const entries = Object.entries(payload).slice(0, 4);
            if (!entries.length) {
                return '';
            }
            return entries
                .map(([key, value]) => {
                    const label = key.replace(/_/g, ' ');
                    const display = typeof value === 'object' ? JSON.stringify(value) : value;
                    return `<span class="rounded-full bg-rose-500/10 px-3 py-1 text-xs text-rose-100">${escapeHtml(label)}: ${escapeHtml(display)}</span>`;
                })
                .join('');
        }

        function renderPerJobRows(perJob) {
            if (!perJob.length) {
                return '<tr><td colspan="7" class="px-4 py-6 text-center text-slate-400">Sin ejecuciones registradas para el rango seleccionado.</td></tr>';
            }
            return perJob
                .map((job) => {
                    const rate = Number(job.success_rate || 0);
                    return `
                        <tr>
                            <td class="px-4 py-4 font-mono text-sm text-slate-300">${escapeHtml(formatJobName(job.job_name))}</td>
                            <td class="px-4 py-4">${escapeHtml(job.total)}</td>
                            <td class="px-4 py-4 text-emerald-200">${escapeHtml(job.success)}</td>
                            <td class="px-4 py-4 text-rose-300">${escapeHtml(job.failed)}</td>
                            <td class="px-4 py-4 font-semibold ${successRateColor(rate)}">${rate.toFixed(1)}%</td>
                            <td class="px-4 py-4">${formatDuration(job.avg_duration_seconds)}</td>
                            <td class="px-4 py-4 text-slate-400">${formatRelative(job.last_run_at)}</td>
                        </tr>
                    `;
                })
                .join('');
        }

        function renderRecentRuns(rows) {
            if (!rows.length) {
                return '<tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">No hay ejecuciones registradas todavía.</td></tr>';
            }
            return rows
                .map((run) => `
                    <tr>
                        <td class="px-4 py-4 font-mono text-xs text-slate-400">#${escapeHtml(run.id)}</td>
                        <td class="px-4 py-4 font-semibold text-slate-200">${escapeHtml(formatJobName(run.job_name))}</td>
                        <td class="px-4 py-4">
                            <span class="rounded-full px-3 py-1 text-xs ${statusPill(run.status)}">${escapeHtml(String(run.status).toUpperCase())}</span>
                        </td>
                        <td class="px-4 py-4 text-slate-300">${formatDuration(run.duration_seconds)}</td>
                        <td class="px-4 py-4 text-slate-400">${formatDate(run.started_at)}</td>
                        <td class="px-4 py-4">${renderTags(run.metrics) || '<span class="text-xs text-slate-500">sin métricas</span>'}</td>
                    </tr>
                `)
                .join('');
        }

        function renderFailures(failures) {
            if (!failures.length) {
                return '<p class="py-12 text-center text-sm text-slate-400">Sin errores en el rango seleccionado. 🎉</p>';
            }
            return failures
                .map((failure) => {
                    const tags = renderFailureTags(failure.metrics, failure.context);
                    const tagsBlock = tags
                        ? `<div class="mt-3 flex flex-wrap gap-2">${tags}</div>`
                        : '';

                    return `
                    <div class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="text-sm font-semibold text-rose-200">
                                ${escapeHtml(formatJobName(failure.job_name))} · Run #${escapeHtml(failure.id)}
                            </div>
                            <span class="text-xs text-rose-200">${formatDate(failure.started_at)}</span>
                        </div>
                        <p class="mt-3 text-sm text-rose-100">${escapeHtml(failure.error_message || 'Error no especificado')}</p>
                        ${tagsBlock}
                    </div>
                `;
                })
                .join('');
        }

        function updateSummary(summary) {
            if (!summary) {
                return;
            }
            if (elements.stats.total) elements.stats.total.textContent = summary.total_runs ?? 0;
            if (elements.stats.success) elements.stats.success.textContent = summary.success_count ?? 0;
            if (elements.stats.failures) elements.stats.failures.textContent = summary.failure_count ?? 0;
            if (elements.stats.successRate) elements.stats.successRate.textContent = Number(summary.success_rate ?? 0).toFixed(1);
            if (elements.stats.avgDuration) elements.stats.avgDuration.textContent = `${Number(summary.avg_duration_seconds ?? 0).toFixed(2)} s`;

            if (elements.labels.lastRun) elements.labels.lastRun.textContent = formatRelative(summary.last_run_at);
            if (elements.labels.lastFailure) {
                elements.labels.lastFailure.textContent = summary.last_failure_at ? formatRelative(summary.last_failure_at) : 'sin registros';
            }
        }

        function markSynced() {
            if (elements.labels.lastSync) {
                elements.labels.lastSync.textContent = formatDate(new Date().toISOString());
            }
        }

        function setLoading(isLoading) {
            if (!elements.labels.loading) {
                return;
            }
            elements.labels.loading.hidden = !isLoading;
        }

        function refresh() {
            setLoading(true);
            fetch(`${state.endpoint}?hours=${state.hours}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((response) => response.json())
                .then((payload) => {
                    const data = payload.data || {};
                    updateSummary(data.summary);
                    if (elements.tables.perJob) {
                        elements.tables.perJob.innerHTML = renderPerJobRows(data.per_job || []);
                    }
                    if (elements.tables.recentRuns) {
                        elements.tables.recentRuns.innerHTML = renderRecentRuns(data.recent_runs || []);
                    }
                    if (elements.failures) {
                        elements.failures.innerHTML = renderFailures(data.recent_failures || []);
                    }
                    markSynced();
                })
                .catch((error) => {
                    console.error('No se pudo refrescar el dashboard', error);
                })
                .finally(() => {
                    setLoading(false);
                });
        }

        function stopTimer() {
            if (state.timerId) {
                clearInterval(state.timerId);
                state.timerId = null;
            }
        }

        function startTimer() {
            stopTimer();
            state.timerId = setInterval(refresh, state.refreshDelay);
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

        startTimer();
        refresh();
    });
</script>
@endpush
