<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Crear Competición') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('competitions.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="name" :value="__('Nombre')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="type" :value="__('Tipo')" />
                            <select id="type" name="type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">Selecciona un tipo</option>
                                <option value="champions" {{ old('type') == 'champions' ? 'selected' : '' }}>Champions League</option>
                                <option value="laliga" {{ old('type') == 'laliga' ? 'selected' : '' }}>La Liga</option>
                                <option value="premier" {{ old('type') == 'premier' ? 'selected' : '' }}>Premier League</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('type')" />
                        </div>

                        <div>
                            <x-input-label for="country" :value="__('País')" />
                            <x-text-input id="country" name="country" type="text" class="mt-1 block w-full" :value="old('country')" />
                            <x-input-error class="mt-2" :messages="$errors->get('country')" />
                        </div>

                        <div class="flex items-center justify-end">
                            <a href="{{ route('competitions.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
                                Cancelar
                            </a>
                            <x-primary-button>
                                {{ __('Crear Competición') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
