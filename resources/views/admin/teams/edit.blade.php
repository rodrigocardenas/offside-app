@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-950 py-12 text-white">
    <div class="mx-auto max-w-4xl space-y-6 px-6">
        <header class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Equipos</p>
                <h1 class="mt-1 text-3xl font-semibold text-white">Editar {{ $team->name }}</h1>
                <p class="text-sm text-slate-400">Actualiza datos del club o selección.</p>
            </div>
            <a href="{{ route('admin.teams.index') }}" class="text-sm text-slate-400 hover:text-slate-200">Volver al listado</a>
        </header>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-3xl border border-slate-800 bg-slate-950/70 p-6 shadow-2xl shadow-black/40">
            @include('admin.teams.partials.form', [
                'action' => route('admin.teams.update', $team),
                'method' => 'PUT',
                'team' => $team,
                'stadiums' => $stadiums,
                'buttonLabel' => 'Actualizar equipo',
            ])
        </div>
    </div>
</div>
@endsection
