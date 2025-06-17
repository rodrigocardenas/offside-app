<div class="bg-offside-dark rounded-lg p-6 mt-1">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-bold">PREGUNTA DEL DÍA</h2>
    </div>
    <div class="bg-offside-primary bg-opacity-20 rounded-lg p-6">
        <div class="mb-4">
            <h3 class="text-xl mb-2">{{ $socialQuestion->title }}</h3>
            @if($socialQuestion->description)
                <p class="text-sm text-offside-light">Finaliza en: <span class="countdown" data-time="{{ $socialQuestion->available_until->addHours(4)->timezone('Europe/Madrid')->format('Y-m-d H:i:s') }}"></span></p>
            @endif
        </div>
        @php
            $userHasAnswered = $socialQuestion->answers->where('user_id', auth()->user()->id)->first();
        @endphp
        @if((!$userHasAnswered && $socialQuestion->available_until->addHours(4) > now()) || ($userHasAnswered && $userHasAnswered->updated_at->diffInMinutes(now()) <= 5))
            <form action="{{ route('questions.answer', $socialQuestion) }}" method="POST" class="space-y-3">
                @csrf
                @foreach($socialQuestion->options as $option)
                    <button type="submit"
                            name="question_option_id"
                            value="{{ $option->id }}"
                            class="w-full text-left bg-offside-primary hover:bg-offside-primary transition-colors p-4 rounded-lg">
                        <div class="flex justify-between items-center">
                            <span>{{ $option->text }}</span>
                            <div class="flex items-center space-x-2">
                                @foreach($socialQuestion->answers->where('question_option_id', $option->id) as $answer)
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
                    </button>
                @endforeach
            </form>
        @else
            <div class="space-y-3">
                @foreach($socialQuestion->options as $option)
                    <div class="p-4 rounded-lg {{
                        $socialQuestion->available_until->addHours(4) > now()
                            ? (isset($userAnswers[$socialQuestion->id]) && $userAnswers[$socialQuestion->id] == $option->id ? 'bg-blue-600' : 'bg-offside-primary bg-opacity-20')
                            : ($option->is_correct ? 'bg-green-600' : (isset($userAnswers[$socialQuestion->id]) && $userAnswers[$socialQuestion->id] == $option->id ? 'bg-red-600' : 'bg-offside-primary bg-opacity-20'))
                    }}">
                        <div class="flex justify-between items-center">
                            <span>{{ $option->text }}</span>
                            <div class="flex items-center space-x-2">
                                @foreach($socialQuestion->answers->where('question_option_id', $option->id) as $answer)
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
                    class="like-btn flex items-center {{ isset($socialQuestion->templateQuestion) && $socialQuestion->templateQuestion->userReactions->where('reaction', 'like')->isNotEmpty() ? 'text-green-500' : 'text-gray-400' }} hover:text-green-400 transition-colors"
                    data-question-id="{{ $socialQuestion->id }}"
                    data-template-question-id="{{ $socialQuestion->template_question_id }}">
                <i class="fas fa-thumbs-up mr-1"></i>
            </button>
            <button type="button"
                    class="dislike-btn flex items-center {{ isset($socialQuestion->templateQuestion) && $socialQuestion->templateQuestion->userReactions->where('reaction', 'dislike')->isNotEmpty() ? 'text-red-500' : 'text-gray-400' }} hover:text-red-400 transition-colors"
                    data-question-id="{{ $socialQuestion->id }}"
                    data-template-question-id="{{ $socialQuestion->template_question_id }}">
                <i class="fas fa-thumbs-down mr-1"></i>
            </button>
        </div>
    </div>
</div>
