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
        <form action="{{ route('questions.answer', $question) }}" method="POST" class="space-y-4 quiz-answer-form" data-question-id="{{ $question->id }}">
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
            @if(!$userAnswer)
                <button
                    type="submit"
                    class="w-full mt-6 px-4 py-2 text-white font-semibold rounded-lg transition-colors"
                    style="background-color: {{ $accentColor ?? '#3b82f6' }};"
                    onmouseover="this.style.backgroundColor='{{ $buttonBgHover ?? '#1e40af' }}'"
                    onmouseout="this.style.backgroundColor='{{ $accentColor ?? '#3b82f6' }}'">
                    <i class="fas fa-check mr-2"></i>Enviar Respuesta
                </button>
            @else
                <div class="mt-4 p-4 border rounded-lg text-sm" style="background-color: rgba(34, 197, 94, 0.1); border-color: #22c55e; color: #166534;">
                    <i class="fas fa-check-circle mr-2"></i>Pregunta respondida
                </div>
            @endif
        </form>
    </div>

    <!-- Navigation Button -->
    <div class="flex justify-center px-6 pb-6">
        <button type="button" class="text-2xl" onclick="scrollToNextQuestion({{ $question->id }})" title="Ir a la siguiente pregunta">
            <i class="fas fa-chevron-down" style="color: {{ $accentColor ?? '#3b82f6' }};"></i>
        </button>
    </div>
</div>

<script>
// Scroll automático al elemento identificado por el fragment
function scrollToFragment() {
    const fragment = window.location.hash.substring(1);
    if (fragment) {
        const element = document.getElementById(fragment);
        if (element) {
            setTimeout(() => {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
    }
}

// Función para scrollear a la siguiente pregunta
function scrollToNextQuestion(currentQuestionId) {
    // Encontrar el siguiente elemento question
    const currentElement = document.getElementById('question' + currentQuestionId);
    if (!currentElement) return;
    
    let nextElement = currentElement.nextElementSibling;
    while (nextElement && !nextElement.id?.startsWith('question')) {
        nextElement = nextElement.nextElementSibling;
    }
    
    if (nextElement) {
        nextElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Opcionalmente, actualizar el fragment en la URL
        window.history.pushState(null, null, '#' + nextElement.id);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Scroll al fragment si existe
    scrollToFragment();
    
    const form = document.querySelector('form[data-question-id="{{ $question->id }}"]');
    if (!form) return;
    
    // Si el usuario ya respondió, deshabilitar el formulario
    @if($userAnswer)
        const inputs = form.querySelectorAll('input[name="question_option_id"], button[type="submit"]');
        inputs.forEach(input => {
            input.disabled = true;
        });
    @endif
    
    // Agregar event listener para deshabilitar formulario al enviar (evitar doble envío)
    form.addEventListener('submit', function(e) {
        // Solo deshabilitar, dejar que se envíe normalmente
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.style.opacity = '0.6';
        }
    });
});

// Scroll al fragment si se carga desde un link externo
window.addEventListener('hashchange', scrollToFragment);
</script>
