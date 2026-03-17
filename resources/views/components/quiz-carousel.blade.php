<!--
    🎮 QUIZ CAROUSEL COMPONENT
    Carrusel horizontal para mostrar y responder preguntas de quiz
    Similar a group-match-questions pero optimizado para quiz
-->

<div class="mt-8">
    <!-- Título de Quiz -->
    <div style="display: flex; align-items: center; justify-content: flex-start; gap: 8px; margin-bottom: 12px; font-size: 18px; font-weight: 700; color: {{ $textPrimary }};">
        🎮 Quiz
        <a href="{{ route('groups.ranking-quiz', $group) }}" style="margin-left: auto; font-size: 12px; color: {{ $textSecondary }}; cursor: pointer; padding: 6px 12px; border-radius: 12px; background: {{ $bgSecondary }}; border: 1px solid {{ $borderColor }}; transition: all 0.2s ease; text-decoration: none; display: inline-block;"
            onmouseover="this.style.background='{{ $isDark ? '#2a4a47' : '#f0f0f0' }}'; this.style.color='{{ $textPrimary }}';"
            onmouseout="this.style.background='{{ $bgSecondary }}'; this.style.color='{{ $textSecondary }}';">
            <i class="fas fa-chart-bar mr-1"></i>Ver Ranking
        </a>
    </div>

    <!-- Carrusel de preguntas -->
    <div class="relative flex items-center">
        <!-- Carrusel -->
        <div class="overflow-x-auto hide-scrollbar snap-x snap-mandatory flex space-x-4 flex-1 px-1 pb-4" id="quizQuestionsCarousel">
            @forelse($quizQuestions as $question)
                @php
                    $userAnswer = $question->answers->firstWhere('user_id', auth()->id());
                @endphp
                <!-- Quiz Question Card (Slider Format) -->
                <div class="snap-center flex-none w-full rounded-2xl p-5 border shadow-sm" id="question{{ $question->id }}" style="background: {{ $componentsBackground }}; border-color: {{ $borderColor }}; border-width: 1px; min-width: 300px;">

                    <!-- Question Header -->
                    <div class="text-center mb-5">
                        <div class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-3" style="background: {{ $accentColor }}; color: #000;">
                            🎮 PREGUNTA #{{ $loop->iteration }}
                        </div>
                        <div class="text-xs mb-4" style="color: {{ $textSecondary }};">
                            <i class="fas fa-circle" style="color: {{ $accentColor }}; font-size: 3px;"></i>
                            <span style="color: {{ $textSecondary }}; margin-left: 4px;">{{ $question->points ?? 100 }} pts</span>
                        </div>
                    </div>

                    <!-- Question Title -->
                    <h3 class="text-base font-bold text-center mb-5" style="color: {{ $textPrimary }};">{{ $question->title }}</h3>

                    {{-- @if($question->description)
                        <p class="text-sm text-center mb-5" style="color: {{ $textSecondary }};">{{ $question->description }}</p>
                    @endif --}}

                    <!-- Question Form / Options -->
                    @if(!$userAnswer)
                        <form action="{{ route('questions.answer', $question) }}" method="POST" class="quiz-answer-form" data-question-id="{{ $question->id }}">
                            @csrf

                            <div class="space-y-3 mb-5">
                                @forelse($question->options as $option)
                                    <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:opacity-80 transition-all"
                                        style="border-color: {{ $borderColor }}; background-color: {{ $componentsBackground }};"
                                        onmouseover="this.style.borderColor='{{ $accentColor }}'; this.style.backgroundColor='rgba(59, 130, 246, 0.05)'"
                                        onmouseout="this.style.borderColor='{{ $borderColor }}'; this.style.backgroundColor='{{ $componentsBackground }}'">

                                        <input
                                            type="radio"
                                            name="question_option_id"
                                            value="{{ $option->id }}"
                                            class="w-4 h-4"
                                            style="accent-color: {{ $accentColor }};"
                                        >

                                        <div class="ml-4 flex-1">
                                            <p class="font-medium text-sm" style="color: {{ $textPrimary }};">{{ $option->text }}</p>
                                        </div>
                                    </label>
                                @empty
                                    <p class="text-center py-4 text-sm" style="color: {{ $textSecondary }};">No hay opciones disponibles</p>
                                @endforelse
                            </div>

                            <!-- Submit Button -->
                            <button
                                type="submit"
                                class="w-full px-4 py-2 text-white font-semibold rounded-lg transition-colors"
                                style="background-color: {{ $accentColor }};"
                                onmouseover="this.style.backgroundColor='{{ $buttonBgHover ?? '#1e40af' }}'"
                                onmouseout="this.style.backgroundColor='{{ $accentColor }}'">
                                <i class="fas fa-check mr-2"></i>Enviar Respuesta
                            </button>
                        </form>
                    @else
                        <!-- Display Result -->
                        <div class="space-y-3">
                            @foreach($question->options as $option)
                                @php
                                    $optionBg = $bgSecondary;
                                    $optionColor = $textPrimary;
                                    $optionBorder = $borderColor;

                                    if ($userAnswer->question_option_id === $option->id) {
                                        if ($userAnswer->is_correct) {
                                            $optionBg = '#28a745';
                                            $optionColor = '#ffffff';
                                            $optionBorder = '#22c55e';
                                        } else {
                                            $optionBg = '#dc3545';
                                            $optionColor = '#ffffff';
                                            $optionBorder = '#ef4444';
                                        }
                                    }
                                @endphp
                                <div class="p-4 border-2 rounded-lg font-medium text-sm" style="background: {{ $optionBg }}; color: {{ $optionColor }}; border-color: {{ $optionBorder }}; display: flex; justify-content: space-between; align-items: center;">
                                    <span>{{ $option->text }}</span>
                                    @if($userAnswer->question_option_id === $option->id)
                                        <div class="flex items-center gap-1">
                                            @if($userAnswer->is_correct)
                                                <i class="fas fa-check-circle"></i>
                                                <span style="margin-left: 4px;">+{{ $userAnswer->points_earned }} pts</span>
                                            @else
                                                <i class="fas fa-times-circle"></i>
                                                <span style="margin-left: 4px;">0 pts</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <!-- Answered Badge -->
                        <div class="mt-4 p-4 border rounded-lg text-sm" style="background-color: rgba(34, 197, 94, 0.1); border-color: #22c55e; color: #166534;">
                            <i class="fas fa-check-circle mr-2"></i>Pregunta respondida
                        </div>
                    @endif
                </div>
            @empty
                <div class="snap-center flex-none w-full text-center py-12 px-4" style="color: {{ $textSecondary }}; min-width: 300px;">
                    <i class="fas fa-inbox text-4xl mb-3" style="color: {{ $borderColor }};"></i>
                    <p class="text-sm">No hay preguntas de quiz disponibles</p>
                </div>
            @endforelse
        </div>

        <!-- Flecha Izquierda -->
        <button class="absolute left-0 z-10 rounded-full p-2 shadow-md hover:shadow-lg transition-all" style="background: {{ $bgTertiary }}; color: {{ $accentColor }}; top: 50%; transform: translateY(-50%);" onclick="document.getElementById('quizQuestionsCarousel').scrollBy({left: -300, behavior: 'smooth'})">
            <i class="fas fa-chevron-left text-lg"></i>
        </button>

        <!-- Flecha Derecha -->
        <button class="absolute right-0 z-10 rounded-full p-2 shadow-md hover:shadow-lg transition-all" style="background: {{ $bgTertiary }}; color: {{ $accentColor }}; top: 50%; transform: translateY(-50%);" onclick="document.getElementById('quizQuestionsCarousel').scrollBy({left: 300, behavior: 'smooth'})">
            <i class="fas fa-chevron-right text-lg"></i>
        </button>
    </div>

    <!-- Navigation Indicators (Optional) -->
    <div class="flex justify-center gap-1 mt-4">
        @for($i = 0; $i < count($quizQuestions); $i++)
            <button class="w-2 h-2 rounded-full transition-colors" style="background: {{ $i === 0 ? $accentColor : $borderColor }};" onclick="document.getElementById('quizQuestionsCarousel').scrollBy({left: 300 * ($i - 0), behavior: 'smooth'})"></button>
        @endfor
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form[data-question-id]');

    forms.forEach(form => {
        // Si el usuario ya respondió, deshabilitar el formulario
        const questionId = form.getAttribute('data-question-id');
        const questionCard = document.getElementById('question' + questionId);

        if (questionCard && questionCard.querySelector('.mt-4.p-4.border.rounded-lg')) {
            const inputs = form.querySelectorAll('input[name="question_option_id"], button[type="submit"]');
            inputs.forEach(input => {
                input.disabled = true;
            });
        }

        // Agregar event listener para deshabilitar formulario al enviar
        form.addEventListener('submit', function(e) {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                button.style.opacity = '0.6';
            }
        });
    });
});
</script>
