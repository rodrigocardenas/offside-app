@php
    // Las variables de tema ya están compartidas globalmente por el middleware
    // Solo validar que existan en caso de que se use el componente sin middleware
    $isDark = $isDark ?? true;
    $bgPrimary = $bgPrimary ?? '#1a1a1a';
    $bgSecondary = $bgSecondary ?? '#2a2a2a';
    $bgTertiary = $bgTertiary ?? '#333333';
    $textPrimary = $textPrimary ?? '#ffffff';
    $textSecondary = $textSecondary ?? '#b0b0b0';
    $borderColor = $borderColor ?? '#333333';
    $componentsBackground = $componentsBackground ?? '#1a524e';
    $buttonBgHover = $buttonBgHover ?? '#2a2a2a';
    $accentColor = $accentColor ?? '#00deb0';
    $accentDark = $accentDark ?? '#003b2f';
@endphp

<div class="mt-1">
    <!-- Título de Preguntas -->
    {{-- <div class="flex items-center gap-2 mb-6 px-4">
        <i class="fas fa-star" style="color: {{ $accentColor }};"></i>
        <h2 class="text-base font-semibold" style="color: {{ $textPrimary }};">Preguntas Disponibles</h2>
    </div> --}}

    <!-- Carrusel de preguntas -->
    <div class="relative flex items-center">
        <!-- Flecha Izquierda -->
        <button class="flex-shrink-0 z-10 rounded-full p-2 shadow-md hover:shadow-lg transition-all mr-2" style="background: {{ $bgTertiary }}; color: {{ $accentColor }};" onclick="document.getElementById('predictiveQuestionsCarousel').scrollBy({left: -300, behavior: 'smooth'})">
            <i class="fas fa-chevron-left text-lg"></i>
        </button>

        <!-- Carrusel -->
        <div class="overflow-x-auto hide-scrollbar snap-x snap-mandatory flex space-x-4 flex-1 px-1 pb-4" id="predictiveQuestionsCarousel">
            @forelse($matchQuestions->where('type', 'predictive') as $question)
                <!-- Prediction Section (Similar to HTML design) -->
                <div class="snap-center flex-none w-full rounded-2xl p-5 border shadow-sm" id="question{{ $question->id }}" style="background: {{ $componentsBackground }}; border-color: {{ $borderColor }}; min-width: 300px; {{ $question->is_disabled || $question->available_until->addHours(4) < now() ? 'opacity-60;' : '' }}">

                <!-- Prediction Header -->
                <div class="text-center mb-5">
                    <div class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-3" style="background: {{ $accentColor }}; color: #000;">
                        Predicción del Día
                    </div>
                    <div class="text-xs mb-4" style="color: {{ $textSecondary }};">
                        <i class="fas fa-circle" style="color: {{ $accentColor }}; font-size: 3px;"></i>
                        {{ $group->competition->name }} • Jornada {{ $question->football_match->matchday }}

                    </div>
                </div>

                <!-- Match Info -->
                @if($question->football_match)
                    @if($question->templateQuestion->homeTeam)
                        <div class="flex items-center justify-center gap-2 mb-5">
                            <span class="text-sm font-medium" style="color: {{ $textPrimary }};">{{ $question->templateQuestion->homeTeam->name }}</span>
                            <img src="{{ $question->templateQuestion->homeTeam->crest_url }}" alt="{{ $question->templateQuestion->homeTeam->name }}" class="w-6 h-6 object-contain" title="{{ $question->templateQuestion->homeTeam?->name }}">
                            <span class="text-sm font-bold mx-2" style="color: {{ $accentDark }};">{{ $question->available_until->format('H:i') }}</span>
                            <img src="{{ $question->templateQuestion->awayTeam?->crest_url }}" alt="{{ $question->templateQuestion->awayTeam->name }}" class="w-6 h-6 object-contain" title="{{ $question->templateQuestion->awayTeam->name }}">
                            <span class="text-sm font-medium" style="color: {{ $textPrimary }};">{{ $question->templateQuestion->awayTeam->name }}</span>
                        </div>
                    @else
                        <div class="flex items-center justify-center gap-2 mb-5">
                            <span class="text-sm font-medium" style="color: {{ $textPrimary }};">{{ $question->football_match->homeTeam?->name }}</span>
                            <img src="{{ $question->football_match->homeTeam?->crest_url }}" alt="{{ $question->football_match->homeTeam?->name }}" class="w-6 h-6 object-contain" title="{{ $question->football_match->homeTeam?->name }}">
                            <span class="text-sm font-bold mx-2" style="color: {{ $accentDark }};">{{ $question->available_until->format('H:i') }}</span>
                            <img src="{{ $question->football_match->awayTeam?->crest_url }}" alt="{{ $question->football_match->awayTeam?->name }}" class="w-6 h-6 object-contain" title="{{ $question->football_match->awayTeam?->name }}">
                            <span class="text-sm font-medium" style="color: {{ $textPrimary }};">{{ $question->football_match->awayTeam?->name }}</span>
                        </div>
                    @endif
                @endif

                <!-- Question Title -->
                <div class="text-base font-bold text-center mb-5" style="color: {{ $accentColor }};">
                    {{ $question->title }}
                </div>

                <!-- Options (Answers) -->
                @if((!isset($userHasAnswered) && $question->available_until->addHours(4) > now() && !$question->is_disabled) || (isset($userHasAnswered) && $userHasAnswered->updated_at->diffInMinutes(now()) <= 5 && $question->can_modify))
                    <form action="{{ route('questions.answer', $question) }}" method="POST" class="group-question-form">
                        @csrf
                        <div class="grid grid-cols-2 gap-3 mb-5">
                            @foreach($question->options->sortBy('text') as $option)
                                @php
                                    $answers = $question->answers->where('question_option_id', $option->id);
                                    $isStacked = $answers->count() > 2;
                                    $allNames = $answers->pluck('user.name')->implode(', ');
                                @endphp
                                <label class="option-label w-full py-3 px-2 border rounded-xl text-xs transition-all cursor-pointer" style="background: {{ $bgSecondary }}; color: {{ $textSecondary }}; border-color: {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;" onmouseover="this.style.borderColor='{{ $accentColor }}'; this.style.backgroundColor='{{ $buttonBgHover }}'" onmouseout="this.style.borderColor='{{ $borderColor }}'; this.style.backgroundColor='{{ $bgSecondary }}'">
                                    <input type="radio" name="question_option_id" value="{{ $option->id }}" style="display: none;" onchange="this.closest('form').submit();">
                                    <span style="pointer-events: none; flex: 1; text-align: center;">{{ $option->text }}</span>
                                    <div style="display: flex; align-items: center;">
                                        @if($answers->count() > 0)
                                            <div class="flex items-center {{ $isStacked ? '-space-x-4' : 'space-x-1' }}" @if($isStacked) title="Votaron: {{ $allNames }}" @endif>
                                                @foreach($answers->take(3) as $answer)
                                                    @if($answer->user->avatar)
                                                        <img src="{{ $answer->user->avatar_url }}"
                                                             alt="{{ $answer->user->name }}"
                                                             class="w-5 h-5 rounded-full border border-white shadow-sm object-cover {{ $isStacked ? 'ring-1 ring-white' : '' }}"
                                                             title="{{ $answer->user->name }}"
                                                             style="pointer-events: none;">
                                                    @else
                                                        @php
                                                            $initials = '';
                                                            $nameParts = explode(' ', $answer->user->name);
                                                            foreach($nameParts as $part) {
                                                                $initials .= strtoupper(substr($part, 0, 1));
                                                            }
                                                            $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
                                                            $color = $colors[array_rand($colors)];
                                                        @endphp
                                                        <div class="w-5 h-5 rounded-full text-white flex items-center justify-center text-xs font-bold border border-white shadow-sm {{ $isStacked ? 'ring-1 ring-white' : '' }}"
                                                             style="background: {{ $color }}; pointer-events: none;"
                                                             title="{{ $answer->user->name }}">
                                                            {{ substr($initials, 0, 1) }}
                                                        </div>
                                                    @endif
                                                @endforeach
                                                @if($isStacked && $answers->count() > 3)
                                                    <span class="text-xs font-bold ml-1" style="color: {{ $textSecondary }}; pointer-events: none;">+{{ $answers->count() - 3 }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </form>

                    <!-- Timer -->
                    <div class="text-center text-sm font-semibold" style="color: {{ $accentColor }};">
                        <i class="fas fa-clock"></i>
                        @if($question->is_disabled)
                            Pregunta deshabilitada
                        @elseif($question->available_until->addHours(4) > now())
                            <span class="countdown" data-time="{{ $question->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span>
                        @else
                            Partido finalizado
                        @endif
                    </div>
                @else
                    <!-- Display Results -->
                    <div class="grid grid-cols-2 gap-3 mb-5">
                        @foreach($question->options->sortBy('text') as $option)
                            @php
                                $answers = $question->answers->where('question_option_id', $option->id);
                                $isStacked = $answers->count() > 2;
                                $allNames = $answers->pluck('user.name')->implode(', ');
                                $optionBg = $bgSecondary;
                                $optionColor = $textSecondary;
                                if ($question->available_until->addHours(4) > now() && !$question->is_disabled) {
                                    if ($userHasAnswered && $userHasAnswered->id == $option->id) {
                                        $optionBg = '#003b2f';
                                        $optionColor = '#c1ff72';
                                    }
                                } else {
                                    if ($option->is_correct) {
                                        $optionBg = '#28a745';
                                        $optionColor = '#ffffff';
                                    } elseif ($userHasAnswered && $userHasAnswered->id == $option->id) {
                                        $optionBg = '#dc3545';
                                        $optionColor = '#ffffff';
                                    }
                                }
                            @endphp
                            <div class="w-full py-3 px-2 border rounded-xl text-xs font-medium" style="background: {{ $optionBg }}; color: {{ $optionColor }}; border-color: {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center;">
                                <span>{{ $option->text }}</span>
                                <div style="display: flex; align-items: center;">
                                    @if($answers->count() > 0)
                                        <div class="flex items-center {{ $isStacked ? '-space-x-4' : 'space-x-1' }}" @if($isStacked) title="Votaron: {{ $allNames }}" @endif>
                                            @foreach($answers->take(3) as $answer)
                                                @if($answer->user->avatar)
                                                    <img src="{{ $answer->user->avatar_url }}"
                                                         alt="{{ $answer->user->name }}"
                                                         class="w-5 h-5 rounded-full border border-white shadow-sm object-cover {{ $isStacked ? 'ring-1 ring-white' : '' }}"
                                                         title="{{ $answer->user->name }}"
                                                         style="pointer-events: none;">
                                                @else
                                                    @php
                                                        $initials = '';
                                                        $nameParts = explode(' ', $answer->user->name);
                                                        foreach($nameParts as $part) {
                                                            $initials .= strtoupper(substr($part, 0, 1));
                                                        }
                                                        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
                                                        $color = $colors[array_rand($colors)];
                                                    @endphp
                                                    <div class="w-5 h-5 rounded-full text-white flex items-center justify-center text-xs font-bold border border-white shadow-sm {{ $isStacked ? 'ring-1 ring-white' : '' }}"
                                                         style="background: {{ $color }}; pointer-events: none;"
                                                         title="{{ $answer->user->name }}">
                                                        {{ substr($initials, 0, 1) }}
                                                    </div>
                                                @endif
                                            @endforeach
                                            @if($isStacked && $answers->count() > 3)
                                                <span class="text-xs font-bold ml-1" style="color: {{ $textSecondary }}; pointer-events: none;">+{{ $answers->count() - 3 }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Timer -->
                    <div class="text-center text-sm font-semibold" style="color: {{ $accentColor }};">
                        <i class="fas fa-clock"></i>
                        @if($question->is_disabled)
                            Pregunta deshabilitada
                        @elseif($question->available_until->addHours(4) > now())
                            <span class="countdown" data-time="{{ $question->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span>
                        @else
                            Partido finalizado
                        @endif
                    </div>
                @endif

                <!-- Like/Dislike Buttons -->
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" class="like-btn text-sm transition-colors" style="color: {{ $textSecondary }};" data-question-id="{{ $question->id }}" data-template-question-id="{{ $question->template_question_id }}">
                        <i class="fas fa-thumbs-up"></i>
                    </button>
                    <button type="button" class="dislike-btn text-sm transition-colors" style="color: {{ $textSecondary }};" data-question-id="{{ $question->id }}" data-template-question-id="{{ $question->template_question_id }}">
                        <i class="fas fa-thumbs-down"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="text-center py-12 px-4" style="color: {{ $textSecondary }};">
                <i class="fas fa-inbox text-4xl mb-3" style="color: {{ $borderColor }};"></i>
                <p class="text-sm">No hay preguntas disponibles para los próximos partidos</p>
            </div>
        @endforelse
        </div>

        <!-- Flecha Derecha -->
        <button class="flex-shrink-0 z-10 rounded-full p-2 shadow-md hover:shadow-lg transition-all ml-2" style="background: {{ $bgTertiary }}; color: {{ $accentColor }};" onclick="document.getElementById('predictiveQuestionsCarousel').scrollBy({left: 300, behavior: 'smooth'})">
            <i class="fas fa-chevron-right text-lg"></i>
        </button>
    </div>

        <!-- Indicadores de navegación -->
        <div class="flex justify-center mt-4 gap-1">
            @foreach($matchQuestions as $index => $question)
                <button class="w-2 h-2 rounded-full question-indicator transition-all" style="background: {{ $borderColor }};" data-index="{{ $index }}"></button>
            @endforeach
        </div>
    </div>
</div>
