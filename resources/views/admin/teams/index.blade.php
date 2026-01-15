@php
    use Illuminate\Support\Str;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-gray-400">Catálogo</p>
                <h2 class="mt-1 text-2xl font-semibold text-gray-100">Equipos</h2>
                <p class="text-sm text-gray-400">Gestiona clubes y selecciones disponibles para las preguntas y fixtures.</p>
            </div>
            <a href="{{ route('admin.teams.create') }}"
               class="inline-flex items-center justify-center rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-400">
                <i class="fas fa-plus mr-2"></i>
                Nuevo equipo
            </a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-800 bg-gray-900/60 p-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-500">Total</p>
                    <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($stats['total']) }}</p>
                    <p class="text-xs text-gray-500">Equipos activos</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Clubes</p>
                    <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($stats['clubs']) }}</p>
                    <p class="text-xs text-slate-400">Registrados</p>
                </div>
                <div class="rounded-2xl border border-sky-800 bg-sky-900/50 p-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-sky-300">Selecciones</p>
                    <p class="mt-2 text-3xl font-semibold text-white">{{ number_format($stats['national']) }}</p>
                    <p class="text-xs text-sky-300">Registradas</p>
                </div>
            </div>

            <form method="GET" class="rounded-2xl border border-gray-800 bg-gray-900/50 p-4 text-sm text-gray-200">
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="flex flex-col gap-1">
                        <span class="text-xs uppercase tracking-[0.3em] text-gray-500">Búsqueda</span>
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Nombre, país, alias"
                               class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-sm text-white focus:border-emerald-400 focus:outline-none">
                    </label>
                    <label class="flex flex-col gap-1">
                        <span class="text-xs uppercase tracking-[0.3em] text-gray-500">Tipo</span>
                        <select name="type" class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none">
                            <option value="">Todos</option>
                            <option value="club" @selected($filters['type'] === 'club')>Club</option>
                            <option value="national" @selected($filters['type'] === 'national')>Selección</option>
                        </select>
                    </label>
                    <label class="flex flex-col gap-1">
                        <span class="text-xs uppercase tracking-[0.3em] text-gray-500">País</span>
                        <select name="country" class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-sm focus:border-emerald-400 focus:outline-none">
                            <option value="">Todos</option>
                            @foreach ($countries as $countryOption)
                                <option value="{{ $countryOption }}" @selected($filters['country'] === $countryOption)>{{ $countryOption }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="mt-4 flex gap-3">
                    <button type="submit" class="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-400">
                        Aplicar filtros
                    </button>
                    <a href="{{ route('admin.teams.index') }}" class="rounded-lg border border-gray-700 px-4 py-2 text-sm text-gray-300 hover:border-gray-500">
                        Limpiar
                    </a>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-gray-800 shadow-xl shadow-black/40">
                <table class="min-w-full divide-y divide-gray-800">
                    <thead class="bg-black/40 text-xs uppercase tracking-[0.3em] text-gray-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Equipo</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">País</th>
                            <th class="px-4 py-3 text-left">Fundado</th>
                            <th class="px-4 py-3 text-left">Estadio</th>
                            <th class="px-4 py-3 text-left">ID externo</th>
                            <th class="px-4 py-3 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-900 bg-gray-950/80 text-sm text-gray-200">
                        @forelse ($teams as $team)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($team->crest_url)
                                            <img src="{{ $team->crest_url }}" alt="{{ $team->name }}" class="h-8 w-8 rounded-full border border-gray-800 object-contain">
                                        @else
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full border border-gray-800 text-xs text-gray-500">
                                                {{ strtoupper(Str::substr($team->short_name ?: $team->name, 0, 2)) }}
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-semibold text-white">{{ $team->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $team->short_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $team->type === 'club' ? 'bg-emerald-500/10 text-emerald-200' : 'bg-sky-500/10 text-sky-200' }}">
                                        {{ $team->type === 'club' ? 'Club' : 'Selección' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-400">{{ $team->country ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-400">{{ $team->founded_year ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-400">{{ $team->stadium->name ?? $team->venue ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500 font-mono">{{ $team->external_id ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.teams.edit', $team) }}" class="text-sky-300 hover:text-sky-100" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.teams.destroy', $team) }}" onsubmit="return confirm('¿Eliminar este equipo? Esta acción es reversible desde papelera.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-200" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                    No hay equipos registrados con los filtros actuales.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $teams->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
