@php
    // Las variables de tema ya están compartidas globalmente por el middleware
    // Solo validar que existan en caso de que se use el componente sin middleware
    $themeColors = $themeColors ?? [];
    $isDark = $themeColors['isDark'] ?? ($isDark ?? true);
    $bgPrimary = $themeColors['bgPrimary'] ?? ($bgPrimary ?? ($isDark ? '#0a2e2c' : '#f5f5f5'));
    $bgSecondary = $themeColors['bgSecondary'] ?? ($bgSecondary ?? ($isDark ? '#0f3d3a' : '#f5f5f5'));
    $bgTertiary = $themeColors['bgTertiary'] ?? ($bgTertiary ?? ($isDark ? '#1a524e' : '#ffffff'));
    $textPrimary = $themeColors['textPrimary'] ?? ($textPrimary ?? ($isDark ? '#ffffff' : '#333333'));
    $textSecondary = $themeColors['textSecondary'] ?? ($textSecondary ?? ($isDark ? '#b0b0b0' : '#999999'));
    $borderColor = $themeColors['borderColor'] ?? ($borderColor ?? ($isDark ? '#2a4a47' : '#e0e0e0'));
    $componentsBackground = $themeColors['componentsBackground'] ?? ($componentsBackground ?? ($isDark ? '#1a524e' : '#ffffff'));
    $buttonBgHover = $themeColors['buttonBgHover'] ?? ($buttonBgHover ?? ($isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0, 0, 0, 0.04)'));
    $accentColor = $themeColors['accentColor'] ?? ($accentColor ?? '#00deb0');
    $accentDark = $themeColors['accentDark'] ?? ($accentDark ?? '#003b2f');
@endphp

<div class="mt-1">
    <!-- Título de Preguntas -->
    {{-- <div class="flex items-center gap-2 mb-6 px-4">
        <i class="fas fa-star" style="color: {{ $accentColor }};"></i>
        <h2 class="text-base font-semibold" style="color: {{ $textPrimary }};">{{ __('views.groups.available_questions') }}</h2>
    </div> --}}

    <!-- Carrusel de preguntas -->
    <div class="relative flex items-center">
        <!-- Carrusel -->
        <div class="overflow-x-auto hide-scrollbar snap-x snap-mandatory flex space-x-4 flex-1 px-1 pb-4" id="predictiveQuestionsCarousel">
            @forelse($matchQuestions->where('type', 'predictive') as $question)
                <!-- Prediction Section (Similar to HTML design) -->
                <div class="snap-center flex-none w-full rounded-2xl p-5 border shadow-sm" id="question{{ $question->id }}" style="background: {{ $componentsBackground }}; border-color: {{ $borderColor }}; min-width: 300px; {{ $question->is_disabled || $question->available_until->addHours(4) < now() ? 'opacity-60;' : '' }}">

                <!-- Prediction Header -->
                <div class="text-center mb-5">
                    <div class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-3" style="background: {{ $accentColor }}; color: #000;">
                        {{ __('views.groups.prediction_of_the_day') }}
                    </div>
                    <div class="text-xs mb-4" style="color: {{ $textSecondary }};">
                        <i class="fas fa-circle" style="color: {{ $accentColor }}; font-size: 3px;"></i>
                        {{ $group->competition?->name }} • {{ __('views.groups.matchday') }} {{ $question->football_match->matchday ?? 'TBD' }}

                    </div>
                </div>

                <!-- Match Info -->
                @if($question->football_match)
                    @if($question->templateQuestion->homeTeam)
                        <div class="flex items-center justify-center gap-4 mb-5">
                            <div class="flex flex-col items-center gap-1">
                                <img src="{{ $question->templateQuestion->homeTeam->crest_url }}" alt="{{ $question->templateQuestion->homeTeam->name }}" class="w-16 h-16 object-contain" title="{{ $question->templateQuestion->homeTeam?->name }}">
                                <span class="text-xs font-medium" style="color: {{ $textPrimary }};">{{ Str::limit($question->templateQuestion->homeTeam->name, 10, '') }}</span>
                            </div>
                                <span class="text-sm font-bold" style="color: {{ $accentDark }};">@userTime($question->football_match->date, 'H:i')</span>
                            <div class="flex flex-col items-center gap-1">
                                <img src="{{ $question->templateQuestion->awayTeam?->crest_url }}" alt="{{ $question->templateQuestion->awayTeam->name }}" class="w-16 h-16 object-contain" title="{{ $question->templateQuestion->awayTeam->name }}">
                                <span class="text-xs font-medium" style="color: {{ $textPrimary }};">{{ Str::limit($question->templateQuestion->awayTeam->name, 10, '') }}</span>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center justify-center gap-4 mb-5">
                            <div class="flex flex-col items-center gap-1">
                                <img src="{{ $question->football_match->homeTeam?->crest_url }}" alt="{{ $question->football_match->homeTeam?->name }}" class="w-16 h-16 object-contain" title="{{ $question->football_match->homeTeam?->name }}">
                                <span class="text-xs font-medium" style="color: {{ $textPrimary }};">{{ Str::limit($question->football_match->homeTeam?->name, 10, '') }}</span>
                            </div>
                            <span class="text-sm font-bold" style="color: {{ $accentDark }};">@userTime($question->football_match->date, 'H:i')</span>
                            <div class="flex flex-col items-center gap-1">
                                <img src="{{ $question->football_match->awayTeam?->crest_url }}" alt="{{ $question->football_match->awayTeam?->name }}" class="w-16 h-16 object-contain" title="{{ $question->football_match->awayTeam?->name }}">
                                <span class="text-xs font-medium" style="color: {{ $textPrimary }};">{{ Str::limit($question->football_match->awayTeam?->name, 10, '') }}</span>
                            </div>
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
                                <label class="option-label w-full py-3 px-2 border rounded-xl text-xs transition-all cursor-pointer" style="background: {{ $bgSecondary }}; color: {{ $textPrimary }}; border-color: {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;" onmouseover="this.style.borderColor='{{ $accentColor }}'; this.style.backgroundColor='{{ $buttonBgHover }}'" onmouseout="this.style.borderColor='{{ $borderColor }}'; this.style.backgroundColor='{{ $bgSecondary }}'">
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
                            {{ __('views.groups.question_disabled') }}
                        @elseif($question->available_until->addHours(4) > now())
                            <span class="countdown" data-time="{{ $question->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span>
                        @else
                            {{ __('views.groups.match_finished') }}
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
                                $optionColor = $textPrimary;
                                if ($question->available_until->addHours(4) > now() && !$question->is_disabled) {
                                    if ($userHasAnswered && $userHasAnswered->id == $option->id) {
                                        $optionBg = $accentDark;
                                        $optionColor = '#ffffff';
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
                            {{ __('views.groups.question_disabled') }}
                        @elseif($question->available_until->addHours(4) > now())
                            <span class="countdown" data-time="{{ $question->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span>
                        @else
                            {{ __('views.groups.match_finished') }}
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
                <p class="text-sm">{{ __('views.groups.no_available_questions') }}</p>
            </div>
        @endforelse

            <!-- Pregunta Social o Invitación de Miembros -->
            @if($group->users->count() >= 2)
                @if($socialQuestion ?? false)
                    <div class="snap-center flex-none w-full" style="min-width: 300px;">
                        <x-groups.group-social-question :social-question="$socialQuestion" :user-answers="$userAnswers" :theme-colors="compact('isDark', 'bgPrimary', 'bgSecondary', 'bgTertiary', 'textPrimary', 'textSecondary', 'borderColor', 'accentColor', 'accentDark', 'componentsBackground', 'buttonBgHover')" />
                    </div>
                @endif
            @else
                <!-- Slide de Invitación para Agregar Miembros -->
                <div class="snap-center flex-none w-full rounded-2xl p-5 border shadow-sm" style="background: {{ $componentsBackground }}; border-color: {{ $borderColor }}; min-width: 300px;">
                    <div class="text-center mb-5">
                        <div class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-3" style="background: {{ $accentColor }}; color: #000;">
                            {{ __('views.groups.social_questions') }}
                        </div>
                        <div class="text-xs mb-4" style="color: {{ $textSecondary }};">
                            <i class="fas fa-circle" style="color: {{ $accentColor }}; font-size: 3px;"></i>
                            {{ __('views.groups.coming_soon') }}
                        </div>
                    </div>

                    <div class="flex flex-col items-center justify-center gap-4 py-8">
                        <div class="text-5xl" style="color: {{ $accentColor }};">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-center mb-2" style="color: {{ $textPrimary }};">
                                {{ __('views.groups.add_more_members') }}
                            </h3>
                            <p class="text-xs text-center" style="color: {{ $textSecondary }};">
                                {{ __('views.groups.social_questions_description') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 p-3 rounded-lg text-xs font-medium text-center" style="background: {{ $bgSecondary }}; color: {{ $textSecondary }}; border: 1px solid {{ $borderColor }};">
                        {{ __('views.groups.current_members') }}: <span style="color: {{ $accentColor }}; font-bold;">{{ $group->users->count() }}/2</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Flecha Izquierda -->
        <button class="absolute left-0 z-10 rounded-full p-2 shadow-md hover:shadow-lg transition-all" style="background: {{ $bgTertiary }}; color: {{ $accentColor }}; top: 50%; transform: translateY(-50%);" onclick="document.getElementById('predictiveQuestionsCarousel').scrollBy({left: -300, behavior: 'smooth'})">
            <i class="fas fa-chevron-left text-lg"></i>
        </button>

        <!-- Flecha Derecha -->
        <button class="absolute right-0 z-10 rounded-full p-2 shadow-md hover:shadow-lg transition-all" style="background: {{ $bgTertiary }}; color: {{ $accentColor }}; top: 50%; transform: translateY(-50%);" onclick="document.getElementById('predictiveQuestionsCarousel').scrollBy({left: 300, behavior: 'smooth'})">
            <i class="fas fa-chevron-right text-lg"></i>
        </button>
    </div>

        <!-- Indicadores de navegación -->
        <div class="flex justify-center mt-4 gap-1">
            @foreach($matchQuestions as $index => $question)
                <button class="w-2 h-2 rounded-full question-indicator transition-all" style="background: {{ $borderColor }};" data-index="{{ $index }}"></button>
            @endforeach
            <!-- Indicador para la pregunta social -->
            <button class="w-2 h-2 rounded-full question-indicator transition-all" style="background: {{ $borderColor }};" data-index="{{ $matchQuestions->count() }}"></button>
        </div>
    </div>
</div>
