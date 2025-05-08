<x-app-layout>
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white bg-opacity-10 rounded-xl p-6 backdrop-blur-sm">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-offside-light">Crear nuevo grupo</h2>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>

                <form method="POST" action="{{ route('groups.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-400 mb-2">Nombre del grupo</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-offside-primary @error('name') border-red-500 @enderror"
                            placeholder="Ej: Grupo de amigos">
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="competition_id" class="block text-sm font-medium text-gray-400 mb-2">Competición</label>
                        <select id="competition_id" name="competition_id" required
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-offside-primary @error('competition_id') border-red-500 @enderror">
                            <option value="">Selecciona una competición</option>
                            @foreach($competitions as $competition)
                                <option value="{{ $competition->id }}" {{ old('competition_id') == $competition->id ? 'selected' : '' }}>
                                    {{ $competition->name }} ({{ $competition->type }})
                                </option>
                            @endforeach
                        </select>
                        @error('competition_id')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('groups.index') }}"
                           class="text-sm text-offside-light hover:text-white transition-colors">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="bg-gradient-to-r from-orange-500 to-orange-400 text-white px-6 py-2 rounded-lg font-semibold hover:from-orange-600 hover:to-orange-500 transition-all">
                            Crear grupo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
