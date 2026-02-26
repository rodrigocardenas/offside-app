<!--
    🎮 QUIZ QUESTION CARD COMPONENT
    Tarjeta para mostrar y responder preguntas de tipo quiz
-->

<div class="rounded-lg shadow-md overflow-hidden mb-6 hover:shadow-lg transition-shadow" id="question{{ $question->id }}" style="background-color: {{ $componentsBackground ?? '#ffffff' }}; border: 1px solid {{ $borderColor ?? '#e5e7eb' }};">
    <div class="p-6">
        <!-- Question Header -->
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-block px-3 py-1 text-xs font-semibold rounded-full" style="background-color: {{ $accentColor ?? '#3b82f6' }}; color: white;">
                        🎮 QUIZ
                    </span>
                    <span class="text-sm" style="color: {{ $textSecondary ?? '#6b7280' }};">
                        {{ $question->points ?? 100 }} puntos
                    </span>
                </div>
                <h3 class="text-xl font-bold" style="color: {{ $textPrimary ?? '#111827' }};">{{ $question->title }}</h3>
            </div>
        </div>

        @if($question->description)
            <p class="mb-6 text-sm" style="color: {{ $textSecondary ?? '#6b7280' }};">{{ $question->description }}</p>
        @endif

        <!-- Question Form -->
        <form action="{{ route('questions.answer', $question) }}" method="POST" class="space-y-4">
            @csrf

            @forelse($question->options as $option)
                <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:opacity-80 transition-all" 
                    style="border-color: {{ $userAnswer && $userAnswer->question_option_id === $option->id ? $accentColor ?? '#3b82f6' : $borderColor ?? '#e5e7eb' }}; background-color: {{ $userAnswer && $userAnswer->question_option_id === $option->id ? 'rgba(59, 130, 246, 0.05)' : $componentsBackground ?? '#ffffff' }};">

                    <input
                        type="radio"
                        name="question_option_id"
                        value="{{ $option->id }}"
                        {{ $userAnswer && $userAnswer->question_option_id === $option->id ? 'checked' : '' }}
                        class="w-4 h-4"
                        style="accent-color: {{ $accentColor ?? '#3b82f6' }};"
                        {{ $question->available_until->addHours(4) < now() ? 'disabled' : '' }}
                    >

                    <div class="ml-4 flex-1">
                        <p class="font-medium" style="color: {{ $textPrimary ?? '#111827' }};">{{ $option->text }}</p>
                    </div>

                    @if($userAnswer)
                        @if($userAnswer->question_option_id === $option->id && $userAnswer->is_correct)
                            <div class="ml-2 flex items-center gap-1" style="color: #22c55e;">
                                <i class="fas fa-check-circle"></i>
                                <span class="text-sm font-semibold">+{{ $userAnswer->points_earned }} pts</span>
                            </div>
                        @elseif($userAnswer->question_option_id === $option->id && !$userAnswer->is_correct)
                            <div class="ml-2 flex items-center gap-1" style="color: #ef4444;">
                                <i class="fas fa-times-circle"></i>
                                <span class="text-sm font-semibold">0 pts</span>
                            </div>
                        @endif
                    @endif
                </label>
            @empty
                <p class="text-center py-4" style="color: {{ $textSecondary ?? '#6b7280' }};">No hay opciones disponibles</p>
            @endforelse

            <!-- Submit Button -->
            @if(!$userAnswer && $question->available_until->addHours(4) > now())
                <button
                    type="submit"
                    class="w-full mt-6 px-4 py-2 text-white font-semibold rounded-lg transition-colors"
                    style="background-color: {{ $accentColor ?? '#3b82f6' }};"
                    onmouseover="this.style.backgroundColor='{{ $buttonBgHover ?? '#1e40af' }}'" 
                    onmouseout="this.style.backgroundColor='{{ $accentColor ?? '#3b82f6' }}'">
                    <i class="fas fa-check mr-2"></i>Enviar Respuesta
                </button>
            @elseif($question->available_until->addHours(4) < now())
                <div class="mt-4 p-4 border rounded-lg text-sm" style="background-color: rgba(202, 138, 4, 0.1); border-color: #ca8a04; color: #78350f;">
                    <i class="fas fa-clock mr-2"></i>Esta pregunta ya no está disponible
                </div>
            @else
                <div class="mt-4 p-4 border rounded-lg text-sm" style="background-color: rgba(34, 197, 94, 0.1); border-color: #22c55e; color: #166534;">
                    <i class="fas fa-check-circle mr-2"></i>Pregunta respondida
                </div>
            @endif
        </form>
    </div>
</div>
