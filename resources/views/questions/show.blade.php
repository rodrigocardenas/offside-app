<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $question->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Detalles de la Pregunta -->
                    <div class="mb-8">
                        <div class="prose max-w-none">
                            <p class="text-gray-600">{{ $question->description }}</p>
                        </div>
                        <div class="mt-4 text-sm text-gray-500">
                            <p>Disponible hasta: {{ $question->available_until->format('d/m/Y H:i') }}</p>
                            <p>Puntos: {{ $question->points }}</p>
                        </div>
                    </div>

                    <!-- Formulario de Respuesta -->
                    @if(!$userAnswer)
                        <form action="{{ route('questions.answer', $question) }}" method="POST" class="space-y-6">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tu respuesta</label>
                                <div class="mt-2 space-y-4">
                                    @foreach($question->options as $option)
                                        <div class="flex items-center">
                                            <input type="radio" name="question_option_id" value="{{ $option->id }}"
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                                                required>
                                            <label class="ml-3 block text-sm font-medium text-gray-700">
                                                {{ $option->text }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex items-center justify-end">
                                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                    Enviar Respuesta
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-semibold mb-2">Tu Respuesta</h3>
                            <p class="text-gray-600">{{ $userAnswer->option->text }}</p>
                            @if($question->type === 'predictive' && $question->available_until > now())
                                <p class="mt-2 text-sm text-blue-600">
                                    Respuesta pendiente de verificación
                                </p>
                                <p class="text-xs text-gray-500">
                                    El resultado se conocerá después del partido
                                </p>
                            @else
                                <p class="mt-2 text-sm {{ $userAnswer->is_correct ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $userAnswer->is_correct ? '¡Correcta!' : 'Incorrecta' }}
                                </p>
                            @endif
                            <p class="text-sm text-gray-500">
                                @if($question->type === 'predictive' && $question->available_until > now())
                                    Puntos posibles: {{ $question->points }}
                                @else
                                    Puntos obtenidos: {{ $userAnswer->points_earned }}
                                @endif
                            </p>
                        </div>

                        <div class="mt-8">
                            <a href="{{ route('questions.results', $question) }}" class="text-blue-600 hover:text-blue-800">
                                Ver resultados cuando estén disponibles
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
