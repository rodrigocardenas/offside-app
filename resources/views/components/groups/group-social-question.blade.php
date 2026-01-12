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
    $buttonBgHover = $buttonBgHover ?? '#2a2a2a';
    $accentColor = $accentColor ?? '#00deb0';
    $accentDark = $accentDark ?? '#003b2f';
@endphp

    {{-- <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h2 style="font-size: 0.875rem; font-weight: bold; color: {{ $textPrimary }};">{{ __('views.groups.question_of_the_day') }}</h2>
    </div> --}}
    <div style="background: {{ $bgPrimary }}; border-radius: 1.2rem; padding: 1.5rem; border: 1px solid {{ $borderColor }}; color: {{ $textPrimary }}; text-align: center;">
        <div class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider mb-3" style="background: {{ $accentColor }}; color: #000;">
            PREGUNTA DEL DÍA
        </div>
        <div style="margin-bottom: 1rem;">
            <h3 style="font-size: 1.25rem; margin-bottom: 0.5rem; color: {{ $accentColor }};">{{ $socialQuestion->title }}</h3>
            @if($socialQuestion->description)
                <p style="font-size: 0.875rem; color: {{ $textSecondary }};">⌛ <span class="countdown" data-time="{{ $socialQuestion->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span></p>
            @endif
        </div>
        @php
            $userHasAnswered = $socialQuestion->answers->where('user_id', auth()->user()->id)->first();
        @endphp
        @if((!$userHasAnswered && $socialQuestion->available_until->addHours(4) > now()) || ($userHasAnswered && $userHasAnswered->updated_at->diffInMinutes(now()) <= 5))
            <form action="{{ route('questions.answer', $socialQuestion) }}" method="POST" style="display: flex; flex-direction: column; gap: 0.75rem;" class="group-social-form">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @foreach($socialQuestion->options as $option)
                        <label class="option-label w-full py-3 px-2 border rounded-xl text-xs transition-all cursor-pointer" style="background: {{ $bgSecondary }}; color: {{ $textSecondary }}; border-color: {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;" onmouseover="this.style.borderColor='{{ $accentColor }}'; this.style.backgroundColor='{{ $buttonBgHover }}'" onmouseout="this.style.borderColor='{{ $borderColor }}'; this.style.backgroundColor='{{ $bgTertiary }}'">
                            <input type="radio" name="question_option_id" value="{{ $option->id }}" style="display: none;" onchange="this.closest('form').submit();">
                            <span style="flex: 1; text-align: center; pointer-events: none;">{{ $option->text }}</span>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                @foreach($socialQuestion->answers->where('question_option_id', $option->id) as $answer)
                                    @if($answer->user->avatar)
                                        <img src="{{ $answer->user->avatar_url }}"
                                             alt="{{ $answer->user->name }}"
                                             class="w-8 h-8 rounded-full border-2 border-white shadow-sm object-cover"
                                             title="{{ $answer->user->name }}">
                                    @else
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
                                    @endif
                                @endforeach
                            </div>
                        </label>
                    @endforeach
                </div>
            </form>
        @else
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                @foreach($socialQuestion->options as $option)
                    @php
                        $optionBg = $bgTertiary;
                        $optionColor = $textPrimary;
                        if ($socialQuestion->available_until->addHours(4) > now()) {
                            if (isset($userAnswers[$socialQuestion->id]) && $userAnswers[$socialQuestion->id] == $option->id) {
                                $optionBg = '#003b2f';
                                $optionColor = $accentColor;
                            }
                        } else {
                            if ($option->is_correct) {
                                $optionBg = '#00c800';
                                $optionColor = '#ffffff';
                            } elseif (isset($userAnswers[$socialQuestion->id]) && $userAnswers[$socialQuestion->id] == $option->id) {
                                $optionBg = '#c80000';
                                $optionColor = '#ffffff';
                            }
                        }
                    @endphp
                    <div class="option-label w-full py-3 px-2 border rounded-xl text-xs transition-all cursor-pointer" style="background: {{ $bgSecondary }}; color: {{ $textSecondary }}; border-color: {{ $borderColor }}; display: flex; justify-content: space-between; align-items: center; gap: 0.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: {{ $optionColor }};">{{ $option->text }}</span>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                @foreach($socialQuestion->answers->where('question_option_id', $option->id) as $answer)
                                    @if($answer->user->avatar)
                                        <img src="{{ $answer->user->avatar_url }}"
                                             alt="{{ $answer->user->name }}"
                                             class="w-8 h-8 rounded-full border-2 border-white shadow-sm object-cover"
                                             title="{{ $answer->user->name }}">
                                    @else
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
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        <!-- Like/Dislike Buttons -->
        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
            <button type="button"
                    style="display: flex; align-items: center; cursor: pointer; color: {{ isset($socialQuestion->templateQuestion) && $socialQuestion->templateQuestion->userReactions->where('id', auth()->id())->where('pivot.reaction', 'like')->isNotEmpty() ? $accentColor : $textSecondary }}; transition: color 0.2s;"
                    class="like-btn"
                    data-question-id="{{ $socialQuestion->id }}"
                    data-template-question-id="{{ $socialQuestion->template_question_id }}"
                    onmouseover="this.style.color='{{ $accentColor }}'"
                    onmouseout="this.style.color='{{ isset($socialQuestion->templateQuestion) && $socialQuestion->templateQuestion->userReactions->where('id', auth()->id())->where('pivot.reaction', 'like')->isNotEmpty() ? $accentColor : $textSecondary }}'">
                <i class="fas fa-thumbs-up" style="margin-right: 0.25rem;"></i>
            </button>
            <button type="button"
                    style="display: flex; align-items: center; cursor: pointer; color: {{ isset($socialQuestion->templateQuestion) && $socialQuestion->templateQuestion->userReactions->where('id', auth()->id())->where('pivot.reaction', 'dislike')->isNotEmpty() ? '#ef4444' : $textSecondary }}; transition: color 0.2s;"
                    class="dislike-btn"
                    data-question-id="{{ $socialQuestion->id }}"
                    data-template-question-id="{{ $socialQuestion->template_question_id }}"
                    onmouseover="this.style.color='#ef4444'"
                    onmouseout="this.style.color='{{ isset($socialQuestion->templateQuestion) && $socialQuestion->templateQuestion->userReactions->where('id', auth()->id())->where('pivot.reaction', 'dislike')->isNotEmpty() ? '#ef4444' : $textSecondary }}'">
                <i class="fas fa-thumbs-down" style="margin-right: 0.25rem;"></i>
            </button>
        </div>
    </div>
