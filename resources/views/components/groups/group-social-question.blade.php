@php
    // Las variables de tema ya están compartidas globalmente por el middleware
    // Solo validar que existan en caso de que se use el componente sin middleware
    $themeColors = $themeColors ?? [];
    $isDark = $themeColors['isDark'] ?? ($isDark ?? true);
    $bgPrimary = $themeColors['bgPrimary'] ?? ($bgPrimary ?? ($isDark ? '#0a2e2c' : '#f5f5f5'));
    $bgSecondary = $themeColors['bgSecondary'] ?? ($bgSecondary ?? ($isDark ? '#0f3d3a' : '#f5f5f5'));
    $bgTertiary = $themeColors['bgTertiary'] ?? ($bgTertiary ?? ($isDark ? '#1a524e' : '#ffffff'));
    $componentsBackground = $themeColors['componentsBackground'] ?? ($componentsBackground ?? ($isDark ? '#1a524e' : '#ffffff'));
    $textPrimary = $themeColors['textPrimary'] ?? ($textPrimary ?? ($isDark ? '#ffffff' : '#333333'));
    $textSecondary = $themeColors['textSecondary'] ?? ($textSecondary ?? ($isDark ? '#b0b0b0' : '#999999'));
    $borderColor = $themeColors['borderColor'] ?? ($borderColor ?? ($isDark ? '#2a4a47' : '#e0e0e0'));
    $buttonBgHover = $themeColors['buttonBgHover'] ?? ($buttonBgHover ?? ($isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0, 0, 0, 0.04)'));
    $accentColor = $themeColors['accentColor'] ?? ($accentColor ?? '#00deb0');
    $accentDark = $themeColors['accentDark'] ?? ($accentDark ?? '#003b2f');

    $userHasAnswered = $socialQuestion->answers->firstWhere('user_id', auth()->id());
    $recentAnswer = $userHasAnswered && $userHasAnswered->updated_at->diffInMinutes(now()) <= 5;
    $canAnswer = (!$userHasAnswered && $socialQuestion->available_until->addHours(4) > now()) || $recentAnswer;
    $userAnswerOptionId = $userAnswers[$socialQuestion->id] ?? optional($userHasAnswered)->question_option_id;
    $userLiked = isset($socialQuestion->templateQuestion) && $socialQuestion->templateQuestion->userReactions->where('id', auth()->id())->where('pivot.reaction', 'like')->isNotEmpty();
    $userDisliked = isset($socialQuestion->templateQuestion) && $socialQuestion->templateQuestion->userReactions->where('id', auth()->id())->where('pivot.reaction', 'dislike')->isNotEmpty();
@endphp

<div class="snap-center flex-none w-full rounded-2xl p-5 border shadow-sm text-center"
     style="background: {{ $componentsBackground }}; border-color: {{ $borderColor }}; min-width: 300px;">
    <div class="text-center mb-5">
        <div class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-3"
             style="background: {{ $accentColor }}; color: #000;">
            pregunta social del día
        </div>
        <div class="text-base font-bold mb-2" style="color: {{ $accentColor }};">
            {{ $socialQuestion->title }}
        </div>

    </div>

    @if($canAnswer)
        <form action="{{ route('questions.answer', $socialQuestion) }}" method="POST" class="group-social-form">
            @csrf
            <div class="grid grid-cols-2 gap-3 mb-5">
                @foreach($socialQuestion->options as $option)
                    @php
                        $answers = $socialQuestion->answers->where('question_option_id', $option->id);
                        $isStacked = $answers->count() > 2;
                        $allNames = $answers->pluck('user.name')->implode(', ');
                    @endphp
                    <label class="option-label w-full py-3 px-2 border rounded-xl text-xs transition-all cursor-pointer"
                           style="background: {{ $bgSecondary }}; color: {{ $textPrimary }}; border-color: {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;"
                           onmouseover="this.style.borderColor='{{ $accentColor }}'; this.style.backgroundColor='{{ $buttonBgHover }}'"
                           onmouseout="this.style.borderColor='{{ $borderColor }}'; this.style.backgroundColor='{{ $bgSecondary }}'">
                        <input type="radio" name="question_option_id" value="{{ $option->id }}" style="display: none;"
                               onchange="this.closest('form').submit();">
                        <span style="pointer-events: none; flex: 1; text-align: center;">{{ $option->text }}</span>
                        <div style="display: flex; align-items: center;">
                            @if($answers->count() > 0)
                                <div class="flex items-center {{ $isStacked ? '-space-x-4' : 'space-x-1' }}" @if($isStacked) title="Votaron: {{ $allNames }}" @endif>
                                    @foreach($answers->take(3) as $answer)
                                        @if($answer->user->avatar)
                                            <img src="{{ $answer->user->avatar_url }}" alt="{{ $answer->user->name }}"
                                                 class="w-5 h-5 rounded-full border border-white shadow-sm object-cover {{ $isStacked ? 'ring-1 ring-white' : '' }}"
                                                 title="{{ $answer->user->name }}" style="pointer-events: none;">
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
    @else
        <div class="grid grid-cols-2 gap-3 mb-5">
            @foreach($socialQuestion->options as $option)
                @php
                    $answers = $socialQuestion->answers->where('question_option_id', $option->id);
                    $isStacked = $answers->count() > 2;
                    $allNames = $answers->pluck('user.name')->implode(', ');
                    $optionBg = $bgSecondary;
                    $optionColor = $textPrimary;
                    if ($socialQuestion->available_until->addHours(4) > now()) {
                        if ($userAnswerOptionId == $option->id) {
                            $optionBg = $accentDark;
                            $optionColor = '#ffffff';
                        }
                    } else {
                        if ($option->is_correct) {
                            $optionBg = '#28a745';
                            $optionColor = '#ffffff';
                        } elseif ($userAnswerOptionId == $option->id) {
                            $optionBg = '#dc3545';
                            $optionColor = '#ffffff';
                        }
                    }
                @endphp
                <div class="w-full py-3 px-2 border rounded-xl text-xs font-medium"
                     style="background: {{ $optionBg }}; color: {{ $optionColor }}; border-color: {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center;">
                    <span>{{ $option->text }}</span>
                    <div style="display: flex; align-items: center;">
                        @if($answers->count() > 0)
                            <div class="flex items-center {{ $isStacked ? '-space-x-4' : 'space-x-1' }}" @if($isStacked) title="Votaron: {{ $allNames }}" @endif>
                                @foreach($answers->take(3) as $answer)
                                    @if($answer->user->avatar)
                                        <img src="{{ $answer->user->avatar_url }}" alt="{{ $answer->user->name }}"
                                             class="w-5 h-5 rounded-full border border-white shadow-sm object-cover {{ $isStacked ? 'ring-1 ring-white' : '' }}"
                                             title="{{ $answer->user->name }}" style="pointer-events: none;">
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
    @endif

    @php
        $isSocialQuestionActive = $socialQuestion->available_until->addHours(4)->greaterThan(now());
    @endphp
    <div class="text-center text-sm font-semibold" style="color: {{ $accentColor }};">
        <i class="fas fa-clock"></i>
        @if($socialQuestion->is_disabled)
            {{ __('views.groups.question_disabled') }}
        @elseif($isSocialQuestionActive)
            <span class="countdown" data-time="{{ $socialQuestion->available_until->addHours(4)->format('Y-m-d H:i:s') }}"></span>
        @else
            {{ __('views.groups.match_finished') }}
        @endif
    </div>

    <div class="flex justify-end gap-3 mt-4">
        <button type="button" class="like-btn text-sm transition-colors"
                data-question-id="{{ $socialQuestion->id }}"
                data-template-question-id="{{ $socialQuestion->template_question_id }}"
                data-default-color="{{ $textSecondary }}"
                data-active-color="{{ $accentColor }}"
                style="color: {{ $userLiked ? $accentColor : $textSecondary }};"
                onmouseover="this.style.color='{{ $accentColor }}'"
                onmouseout="this.style.color='{{ $userLiked ? $accentColor : $textSecondary }}'">
            <i class="fas fa-thumbs-up"></i>
        </button>
        <button type="button" class="dislike-btn text-sm transition-colors"
                data-question-id="{{ $socialQuestion->id }}"
                data-template-question-id="{{ $socialQuestion->template_question_id }}"
                data-default-color="{{ $textSecondary }}"
                data-active-color="#ef4444"
                style="color: {{ $userDisliked ? '#ef4444' : $textSecondary }};"
                onmouseover="this.style.color='#ef4444'"
                onmouseout="this.style.color='{{ $userDisliked ? '#ef4444' : $textSecondary }}'">
            <i class="fas fa-thumbs-down"></i>
        </button>
    </div>
</div>
