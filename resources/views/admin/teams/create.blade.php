<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-gray-400">Equipos</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-100">Nuevo equipo</h2>
            </div>
            <a href="{{ route('admin.teams.index') }}" class="text-sm text-gray-400 hover:text-gray-200">Volver</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-4xl space-y-6 sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-gray-800 bg-gray-950/60 p-6 shadow-xl shadow-black/40">
                @include('admin.teams.partials.form', [
                    'action' => route('admin.teams.store'),
                    'method' => 'POST',
                    'team' => $team,
                    'stadiums' => $stadiums,
                    'buttonLabel' => 'Crear equipo',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
