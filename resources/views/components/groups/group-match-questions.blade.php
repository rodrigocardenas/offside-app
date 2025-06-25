<div class="bg-offside-dark rounded-lg p-6 mt-16">
    <h2 class="text-sm font-bold mb-2">
        @if($currentMatchday)
            JORNADA {{ $currentMatchday }}
        @else
            {{-- PREGUNTAS DE LA JORNADA --}}
        @endif
    </h2>
    <!-- Carrusel de preguntas -->
    <div class="relative">
        <div class="overflow-x-auto hide-scrollbar snap-x snap-mandatory flex space-x-4 pb-4" id="predictiveQuestionsCarousel">
            @forelse($matchQuestions as $question)
                <div class="snap-center flex-none w-full text-center" id="question{{ $question->id }}">
                    <div class="bg-offside-primary bg-opacity-20 rounded-lg p-6 {{ $question->is_disabled || $question->available_until->addHours(4) < now() ? 'opacity-50' : '' }}">
                        <div class="mb-4">
                            <p class="text-xl text-offside-light flex items-center justify-center">
                                @if($question->football_match)
                                    @if($question->templateQuestion->homeTeam)
                                        <img src="{{ $question->templateQuestion->homeTeam->crest_url }}" alt="{{ $question->templateQuestion->homeTeam->crest_url }}" class="w-6 h-6 mr-2"> vs <img src="{{ $question->templateQuestion->awayTeam->crest_url }}" alt="{{ $question->templateQuestion->awayTeam->crest_url }}" class="w-6 h-6 ml-2">
                                    @else
                                        <img src="{{ $question->football_match->homeTeam?->crest_url }}" alt="{{ $question->football_match->homeTeam?->name }}" class="w-6 h-6 mr-2"> vs <img src="{{ $question->football_match->awayTeam?->crest_url }}" alt="{{ $question->football_match->awayTeam?->name }}" class="w-6 h-6 ml-2">
                                    @endif
                                @else
                                    {{ $question->title }}
                                @endif
                            </p>
                            <h4 class="text-xl font-bold mb-2">{{ $question->title }}</h4>
                            <p class="text-sm text-offside-light">
                                @if($question->is_disabled)
                                    Pregunta deshabilitada
                                @elseif($question->available_until->addHours(4) > now())
                                    ⌛ <span class="countdown" data-time="{{ $question->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span>
                                @else
                                    Partido finalizado
                                @endif
                            </p>
                            @if($question->can_modify && $userAnswers->where('question_id', $question->id)->first())
                                <div class="mt-2 text-sm text-blue-600">
                                    @php
                                        $remainingTime = $userAnswers->where('question_id', $question->id)->first()->updated_at->addMinutes(5)->diffInSeconds(now());
                                        $minutes = floor($remainingTime / 60);
                                        $seconds = $remainingTime % 60;
                                    @endphp
                                    {{-- Tiempo restante para modificar: {{ $minutes }}m {{ $seconds }}s --}}
                                </div>
                            @elseif($question->is_disabled)
                                <div class="mt-2 text-sm text-red-600">
                                    Esta pregunta ya no está disponible para responder
                                </div>
                            @endif
                        </div>
                        @if((!isset($userHasAnswered) && $question->available_until->addHours(4) > now() && !$question->is_disabled) || (isset($userHasAnswered) && $userHasAnswered->updated_at->diffInMinutes(now()) <= 5))
                            <form action="{{ route('questions.answer', $question) }}" method="POST" class="space-y-3 group-question-form">
                                @csrf
                                <div class="flex flex-col gap-2">
                                    @foreach($question->options as $option)
                                        <label class="w-full flex justify-between items-center bg-offside-primary hover:bg-offside-secondary transition-colors p-4 rounded-lg cursor-pointer option-btn" style="user-select:none;">
                                            <input type="radio" name="question_option_id" value="{{ $option->id }}" class="hidden" required>
                                            <span class="flex-1 text-center">{{ $option->text }}</span>
                                            <div class="flex items-center space-x-2">
                                                @foreach($question->answers->where('question_option_id', $option->id) as $answer)
                                                    @php
                                                        $initials = '';
                                                        $nameParts = explode(' ', $answer->user->name);
                                                        foreach($nameParts as $part) {
                                                            $initials .= strtoupper(substr($part, 0, 1));
                                                        }
                                                        $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500'];
                                                        $color = $colors[array_rand($colors)];
                                                    @endphp
                                                    <div class="w-8 h-8 rounded-full {{ $color }} text-white flex items-center justify-center text-xs font-bold border-2 border-white shadow-sm"
                                                        title="{{ $answer->user->name }}">
                                                        {{ $initials }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </form>
                            <script>
                            document.querySelectorAll('.group-question-form .option-btn input[type=radio]').forEach(radio => {
                                radio.addEventListener('change', function() {
                                    this.form.submit();
                                });
                            });
                            </script>
                        @else
                            <div class="space-y-3">
                                @foreach($question->options as $option)
                                    <div class="p-4 rounded-lg {{
                                        $question->available_until->addHours(4) > now() && !$question->is_disabled
                                            ? ($userHasAnswered->id == $option->id ? 'bg-blue-600' : 'bg-offside-primary bg-opacity-20')
                                            : ($option->is_correct ? 'bg-green-600' : (($userHasAnswered->id ?? null) == $option->id ? 'bg-red-600' : 'bg-offside-primary bg-opacity-20'))
                                    }}">
                                        <div class="flex justify-between items-center">
                                            <span>{{ $option->text }}</span>
                                            <div class="flex items-center space-x-2">
                                                @foreach($question->answers->where('question_option_id', $option->id) as $answer)
                                                    @php
                                                        $initials = '';
                                                        $nameParts = explode(' ', $answer->user->name);
                                                        foreach($nameParts as $part) {
                                                            $initials .= strtoupper(substr($part, 0, 1));
                                                        }
                                                        $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500'];
                                                        $color = $colors[array_rand($colors)];
                                                    @endphp
                                                    <div class="w-8 h-8 rounded-full {{ $color }} text-white flex items-center justify-center text-xs font-bold border-2 border-white shadow-sm"
                                                        title="{{ $answer->user->name }}">
                                                        {{ $initials }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <!-- Like/Dislike Buttons -->
                        <div class="flex justify-end space-x-4 mt-4">
                            <button type="button"
                                    class="like-btn flex items-center {{ isset($question->templateQuestion) && $question->templateQuestion->userReactions->where('reaction', 'like')->isNotEmpty() ? 'text-green-500' : 'text-gray-400' }} hover:text-green-400 transition-colors"
                                    data-question-id="{{ $question->id }}"
                                    data-template-question-id="{{ $question->template_question_id }}">
                                <i class="fas fa-thumbs-up mr-1"></i>
                            </button>
                            <button type="button"
                                    class="dislike-btn flex items-center {{ isset($question->templateQuestion) && $question->templateQuestion->userReactions->where('reaction', 'dislike')->isNotEmpty() ? 'text-red-500' : 'text-gray-400' }} hover:text-red-400 transition-colors"
                                    data-question-id="{{ $question->id }}"
                                    data-template-question-id="{{ $question->template_question_id }}">
                                <i class="fas fa-thumbs-down mr-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-400 py-8">
                    No hay preguntas disponibles para los próximos partidos.
                </div>
            @endforelse
        </div>
        <!-- Indicadores de navegación -->
        <div class="flex justify-center mt-1 space-x-2">
            @foreach($matchQuestions as $index => $question)
                <button class="w-2 h-2 rounded-full bg-offside-light question-indicator" data-index="{{ $index }}"></button>
            @endforeach
        </div>
    </div>
</div>
