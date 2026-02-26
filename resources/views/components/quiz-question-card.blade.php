<!-- 
    🎮 QUIZ QUESTION CARD COMPONENT
    Tarjeta para mostrar y responder preguntas de tipo quiz
-->

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-6 hover:shadow-lg transition-shadow" id="question{{ $question->id }}">
    <div class="p-6">
        <!-- Question Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs font-semibold rounded-full">
                        🎮 QUIZ
                    </span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $question->points ?? 100 }} puntos
                    </span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $question->title }}</h3>
            </div>
        </div>

        @if($question->description)
            <p class="text-gray-600 dark:text-gray-400 mb-6 text-sm">{{ $question->description }}</p>
        @endif

        <!-- Question Form -->
        <form action="{{ route('questions.answer', $question) }}" method="POST" class="space-y-4">
            @csrf

            @forelse($question->options as $option)
                <label class="flex items-center p-4 border-2 border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:border-blue-400 dark:hover:border-blue-500 transition-colors 
                    {{ $userAnswer && $userAnswer->question_option_id === $option->id ? 'border-blue-500 bg-blue-50 dark:border-blue-500 dark:bg-blue-900/20' : '' }}">
                    
                    <input 
                        type="radio" 
                        name="question_option_id" 
                        value="{{ $option->id }}"
                        {{ $userAnswer && $userAnswer->question_option_id === $option->id ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 dark:text-blue-400"
                        {{ $question->available_until->addHours(4) < now() ? 'disabled' : '' }}
                    >
                    
                    <div class="ml-4 flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $option->text }}</p>
                    </div>

                    @if($userAnswer)
                        @if($userAnswer->question_option_id === $option->id && $userAnswer->is_correct)
                            <div class="ml-2 flex items-center gap-1 text-green-600 dark:text-green-400">
                                <i class="fas fa-check-circle"></i>
                                <span class="text-sm font-semibold">+{{ $userAnswer->points_earned }} pts</span>
                            </div>
                        @elseif($userAnswer->question_option_id === $option->id && !$userAnswer->is_correct)
                            <div class="ml-2 flex items-center gap-1 text-red-600 dark:text-red-400">
                                <i class="fas fa-times-circle"></i>
                                <span class="text-sm font-semibold">0 pts</span>
                            </div>
                        @endif
                    @endif
                </label>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-4">No hay opciones disponibles</p>
            @endforelse

            <!-- Submit Button -->
            @if(!$userAnswer && $question->available_until->addHours(4) > now())
                <button 
                    type="submit" 
                    class="w-full mt-6 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                    <i class="fas fa-check mr-2"></i>Enviar Respuesta
                </button>
            @elseif($question->available_until->addHours(4) < now())
                <div class="mt-4 p-4 bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-lg text-yellow-800 dark:text-yellow-200 text-sm">
                    <i class="fas fa-clock mr-2"></i>Esta pregunta ya no está disponible
                </div>
            @else
                <div class="mt-4 p-4 bg-green-100 dark:bg-green-900/20 border border-green-300 dark:border-green-700 rounded-lg text-green-800 dark:text-green-200 text-sm">
                    <i class="fas fa-check-circle mr-2"></i>Pregunta respondida
                </div>
            @endif
        </form>
    </div>
</div>
