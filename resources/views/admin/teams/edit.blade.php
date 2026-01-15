<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-gray-400">Equipos</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-100">Editar {{ $team->name }}</h2>
                <p class="text-sm text-gray-500">Actualiza datos del club o selección.</p>
            </div>
            <a href="{{ route('admin.teams.index') }}" class="text-sm text-gray-400 hover:text-gray-200">Volver al listado</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-3xl border border-gray-800 bg-gray-950/60 p-6 shadow-xl shadow-black/40">
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
</x-app-layout>
