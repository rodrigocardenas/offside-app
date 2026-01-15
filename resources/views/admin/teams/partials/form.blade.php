@php
    $method = $method ?? 'POST';
    $buttonLabel = $buttonLabel ?? 'Guardar';
@endphp

@if ($errors->any())
    <div class="rounded-lg border border-red-500/40 bg-red-500/10 px-4 py-3 text-red-200">
        <p class="font-semibold">Revisa el formulario</p>
        <ul class="mt-2 list-disc pl-5 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="space-y-8" autocomplete="off">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-6 md:grid-cols-2">
        <label class="flex flex-col gap-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">Nombre *</span>
            <input type="text" name="name" value="{{ old('name', $team->name) }}" required
                   class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none">
        </label>
        <label class="flex flex-col gap-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">Tipo *</span>
            <select name="type" class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none" required>
                <option value="club" @selected(old('type', $team->type) === 'club')>Club</option>
                <option value="national" @selected(old('type', $team->type) === 'national')>Selección</option>
            </select>
        </label>
        <label class="flex flex-col gap-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">Short name</span>
            <input type="text" name="short_name" value="{{ old('short_name', $team->short_name) }}"
                   class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none">
        </label>
        <label class="flex flex-col gap-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">TLA</span>
            <input type="text" name="tla" value="{{ old('tla', $team->tla) }}"
                   class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none">
        </label>
        <label class="flex flex-col gap-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">País</span>
            <input type="text" name="country" value="{{ old('country', $team->country) }}"
                   class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none">
        </label>
        <label class="flex flex-col gap-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">Año de fundación</span>
            <input type="number" name="founded_year" value="{{ old('founded_year', $team->founded_year) }}" min="1800" max="{{ now()->year + 1 }}"
                   class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none">
        </label>
        <label class="flex flex-col gap-2 md:col-span-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">Colores</span>
            <input type="text" name="club_colors" value="{{ old('club_colors', $team->club_colors) }}"
                   class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none"
                   placeholder="Rojo / Negro">
        </label>
        <label class="flex flex-col gap-2 md:col-span-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">Estadio (texto)</span>
            <input type="text" name="venue" value="{{ old('venue', $team->venue) }}"
                   class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none"
                   placeholder="Camp Nou">
        </label>
        <label class="flex flex-col gap-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">Relacionar estadio</span>
            <select name="stadium_id" class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none">
                <option value="">Sin asignar</option>
                @foreach ($stadiums as $id => $name)
                    <option value="{{ $id }}" @selected((string) old('stadium_id', $team->stadium_id) === (string) $id)>{{ $name }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">URL del escudo</span>
            <input type="url" name="crest_url" value="{{ old('crest_url', $team->crest_url) }}"
                   class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none"
                   placeholder="https://...">
        </label>
        <label class="flex flex-col gap-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">Sitio web</span>
            <input type="url" name="website" value="{{ old('website', $team->website) }}"
                   class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none"
                   placeholder="https://...">
        </label>
        <label class="flex flex-col gap-2">
            <span class="text-xs uppercase tracking-[0.3em] text-gray-500">ID externo</span>
            <input type="text" name="external_id" value="{{ old('external_id', $team->external_id) }}"
                   class="rounded-lg border border-gray-700 bg-black/40 px-3 py-2 text-white focus:border-emerald-400 focus:outline-none"
                   placeholder="api_12345">
        </label>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-emerald-500 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-400">
            {{ $buttonLabel }}
        </button>
        <a href="{{ route('admin.teams.index') }}" class="text-sm text-gray-400 hover:text-gray-200">Cancelar</a>
    </div>
</form>
