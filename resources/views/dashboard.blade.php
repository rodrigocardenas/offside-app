<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Grupos Activos -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h3 class="text-lg font-semibold mb-4">Grupos Activos</h3>
                            @if(auth()->user()->groups->count() > 0)
                                <ul class="space-y-2">
                                    @foreach(auth()->user()->groups as $group)
                                        <li>
                                            <a href="{{ route('groups.show', $group) }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $group->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-gray-500">No perteneces a ningún grupo.</p>
                                <a href="{{ route('groups.create') }}" class="mt-4 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                    Crear Grupo
                                </a>
                            @endif
                        </div>

                        <!-- Preguntas Disponibles -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h3 class="text-lg font-semibold mb-4">Preguntas Disponibles</h3>
                            @if($questions->count() > 0)
                                <ul class="space-y-2">
                                    @foreach($questions as $question)
                                        <li>
                                            <a href="{{ route('questions.show', $question) }}" class="text-blue-600 hover:text-blue-800">
                                                {{ $question->title }}
                                            </a>
                                            <span class="text-sm text-gray-500">
                                                (Disponible hasta: {{ $question->available_until->format('d/m/Y H:i') }})
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-gray-500">No hay preguntas disponibles en este momento.</p>
                            @endif
                        </div>

                        <!-- Ranking Diario -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <h3 class="text-lg font-semibold mb-4">Ranking Diario</h3>
                            @if($rankings->count() > 0)
                                <ul class="space-y-2">
                                    @foreach($rankings->take(5) as $ranking)
                                        <li class="flex justify-between">
                                            <span>{{ $ranking->name }}</span>
                                            <span class="font-semibold">{{ $ranking->total_points }} puntos</span>
                                        </li>
                                    @endforeach
                                </ul>
                                <a href="{{ route('rankings.daily') }}" class="mt-4 inline-block text-blue-600 hover:text-blue-800">
                                    Ver ranking completo
                                </a>
                            @else
                                <p class="text-gray-500">No hay datos de ranking disponibles.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
