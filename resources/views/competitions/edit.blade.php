<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Competición') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('competitions.update', $competition) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="name" :value="__('Nombre')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $competition->name)" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="type" :value="__('Tipo')" />
                            <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Selecciona un tipo</option>
                                <option value="champions" {{ old('type', $competition->type) == 'champions' ? 'selected' : '' }}>Champions League</option>
                                <option value="laliga" {{ old('type', $competition->type) == 'laliga' ? 'selected' : '' }}>La Liga</option>
                                <option value="premier" {{ old('type', $competition->type) == 'premier' ? 'selected' : '' }}>Premier League</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        </div>

                        <div>
                            <x-input-label for="country" :value="__('País')" />
                            <x-text-input id="country" name="country" type="text" class="mt-1 block w-full" :value="old('country', $competition->country)" />
                            <x-input-error class="mt-2" :messages="$errors->get('country')" />
                        </div>

                        <div class="flex items-center justify-end">
                            <a href="{{ route('competitions.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
                                Cancelar
                            </a>
                            <x-primary-button>
                                {{ __('Actualizar Competición') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
