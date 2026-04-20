@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-950 py-12 text-white">
    <div class="mx-auto flex max-w-7xl flex-col gap-10 px-6">
        <!-- Header -->
        <header class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-sky-300">Panel de Control</p>
                <h1 class="mt-3 text-4xl font-semibold">Administrador</h1>
                <p class="mt-2 max-w-2xl text-base text-slate-400">
                    Gestiona preguntas, plantillas de preguntas y equipos desde este panel centralizado.
                </p>
            </div>
            <div>
                <a href="{{ route('admin.app-health-dashboard') }}" 
                   class="inline-flex items-center gap-2 rounded-lg bg-emerald-500/90 px-4 py-2 font-semibold text-white hover:bg-emerald-400 transition-colors">
                    <i class="fas fa-heartbeat"></i>
                    Salud de la App
                </a>
            </div>
        </header>

        <!-- Statistics Cards -->
        <section class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <!-- Questions Card -->
            <a href="{{ route('admin.questions.index') }}" class="group">
                <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-sky-900/40 to-sky-500/10 p-6 shadow-lg shadow-sky-500/10 transition-all hover:border-sky-600/50 hover:shadow-sky-500/20">
                    <p class="text-xs uppercase tracking-[0.4em] text-sky-300">Preguntas</p>
                    <h3 class="mt-4 text-4xl font-semibold text-sky-100">{{ $questions_total }}</h3>
                    <p class="mt-1 text-sm text-sky-200">
                        @if($questions_featured > 0)
                            {{ $questions_featured }} destacadas
                        @else
                            Ninguna destacada
                        @endif
                    </p>
                    <div class="mt-4 flex items-center text-sm text-sky-300 group-hover:gap-2 transition-all gap-1">
                        <span>Gestionar</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </div>
                </article>
            </a>

            <!-- Teams Card -->
            <a href="{{ route('admin.teams.index') }}" class="group">
                <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-emerald-900/40 to-emerald-500/10 p-6 shadow-lg shadow-emerald-500/10 transition-all hover:border-emerald-600/50 hover:shadow-emerald-500/20">
                    <p class="text-xs uppercase tracking-[0.4em] text-emerald-300">Equipos</p>
                    <h3 class="mt-4 text-4xl font-semibold text-emerald-100">{{ $teams_total }}</h3>
                    <p class="mt-1 text-sm text-emerald-200">
                        Equipos configurados
                    </p>
                    <div class="mt-4 flex items-center text-sm text-emerald-300 group-hover:gap-2 transition-all gap-1">
                        <span>Gestionar</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </div>
                </article>
            </a>

            <!-- Template Questions Card -->
            <a href="{{ route('admin.template-questions.index') }}" class="group">
                <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-amber-900/40 to-amber-500/10 p-6 shadow-lg shadow-amber-500/10 transition-all hover:border-amber-600/50 hover:shadow-amber-500/20">
                    <p class="text-xs uppercase tracking-[0.4em] text-amber-300">Plantillas</p>
                    <h3 class="mt-4 text-4xl font-semibold text-amber-100">{{ $template_questions_total }}</h3>
                    <p class="mt-1 text-sm text-amber-200">
                        Plantillas de preguntas
                    </p>
                    <div class="mt-4 flex items-center text-sm text-amber-300 group-hover:gap-2 transition-all gap-1">
                        <span>Gestionar</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </div>
                </article>
            </a>

            <!-- Verification Card -->
            <a href="{{ route('admin.verification-dashboard') }}" class="group">
                <article class="rounded-2xl border border-slate-800 bg-gradient-to-br from-fuchsia-900/40 to-fuchsia-500/10 p-6 shadow-lg shadow-fuchsia-500/10 transition-all hover:border-fuchsia-600/50 hover:shadow-fuchsia-500/20">
                    <p class="text-xs uppercase tracking-[0.4em] text-fuchsia-300">Verificación</p>
                    <h3 class="mt-4 text-4xl font-semibold text-fuchsia-100">
                        <i class="fas fa-check-circle text-3xl"></i>
                    </h3>
                    <p class="mt-1 text-sm text-fuchsia-200">
                        Validación de respuestas
                    </p>
                    <div class="mt-4 flex items-center text-sm text-fuchsia-300 group-hover:gap-2 transition-all gap-1">
                        <span>Ver más</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </div>
                </article>
            </a>
        </section>

        <!-- Recent Changes -->
        <section class="grid gap-6 xl:grid-cols-2">
            <!-- Recent Questions -->
            <article class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6">
                <header class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-xl font-semibold">Preguntas Recientes</h3>
                        <p class="text-sm text-slate-400">Últimas preguntas creadas</p>
                    </div>
                    <a href="{{ route('admin.questions.create') }}" 
                       class="text-xs font-semibold text-sky-300 hover:text-sky-200 flex items-center gap-1">
                        <i class="fas fa-plus"></i> Nueva
                    </a>
                </header>
                <div class="space-y-3">
                    @forelse($recent_questions as $question)
                        <div class="rounded-lg border border-slate-700/50 bg-slate-800/30 p-4 hover:bg-slate-800/50 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-white truncate">{{ Str::limit($question->title, 60) }}</p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        <span class="inline-block px-2 py-1 rounded bg-slate-700/50">
                                            {{ $question->type === 'multiple_choice' ? 'Opción múltiple' : ($question->type === 'boolean' ? 'Verdadero/Falso' : 'Texto') }}
                                        </span>
                                    </p>
                                </div>
                                <a href="{{ route('admin.questions.edit', $question) }}" 
                                   class="text-sky-400 hover:text-sky-300">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-slate-400 py-6">No hay preguntas creadas</p>
                    @endforelse
                </div>
            </article>

            <!-- Recent Teams -->
            <article class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6">
                <header class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-xl font-semibold">Equipos Recientes</h3>
                        <p class="text-sm text-slate-400">Últimos equipos agregados</p>
                    </div>
                    <a href="{{ route('admin.teams.create') }}" 
                       class="text-xs font-semibold text-emerald-300 hover:text-emerald-200 flex items-center gap-1">
                        <i class="fas fa-plus"></i> Nuevo
                    </a>
                </header>
                <div class="space-y-3">
                    @forelse($recent_teams as $team)
                        <div class="rounded-lg border border-slate-700/50 bg-slate-800/30 p-4 hover:bg-slate-800/50 transition-colors">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-white truncate">{{ $team->name }}</p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        <span class="inline-block px-2 py-1 rounded bg-slate-700/50">
                                            {{ $team->competition->name ?? 'Sin competencia' }}
                                        </span>
                                    </p>
                                </div>
                                <a href="{{ route('admin.teams.edit', $team) }}" 
                                   class="text-emerald-400 hover:text-emerald-300">
                                    <i class="fas fa-edit text-sm"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-sm text-slate-400 py-6">No hay equipos creados</p>
                    @endforelse
                </div>
            </article>
        </section>

        <!-- Quick Actions -->
        <section class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6">
            <h3 class="text-xl font-semibold mb-5">Acciones Rápidas</h3>
            <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-4">
                <a href="{{ route('admin.questions.create') }}" 
                   class="rounded-lg border border-sky-600/40 bg-sky-500/10 px-4 py-3 text-center text-sm font-semibold text-sky-200 hover:bg-sky-500/20 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Nueva Pregunta
                </a>
                <a href="{{ route('admin.teams.create') }}" 
                   class="rounded-lg border border-emerald-600/40 bg-emerald-500/10 px-4 py-3 text-center text-sm font-semibold text-emerald-200 hover:bg-emerald-500/20 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Nuevo Equipo
                </a>
                <a href="{{ route('admin.template-questions.create') }}" 
                   class="rounded-lg border border-amber-600/40 bg-amber-500/10 px-4 py-3 text-center text-sm font-semibold text-amber-200 hover:bg-amber-500/20 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Nueva Plantilla
                </a>
                <a href="{{ route('admin.app-health-dashboard') }}" 
                   class="rounded-lg border border-slate-600/40 bg-slate-500/10 px-4 py-3 text-center text-sm font-semibold text-slate-200 hover:bg-slate-500/20 transition-colors">
                    <i class="fas fa-chart-line mr-2"></i>
                    Estadísticas
                </a>
            </div>
        </section>
    </div>
</div>
@endsection
