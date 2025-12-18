@props([
    'question',
    'userAnswer' => null,
    'showResults' => false
])

<div class="prediction-options" id="options-{{ $question->id }}">
    @foreach($question->options as $option)
        @php
            $isSelected = $userAnswer && $userAnswer->question_option_id == $option->id;
            $isCorrect = $showResults && $option->is_correct;
            $isDisabled = $userAnswer || $showResults;

            $classes = 'option-btn';
            if ($isSelected) {
                $classes .= ' selected';
            }
            if ($isDisabled) {
                $classes .= ' cursor-not-allowed opacity-75';
            }
            if ($showResults && $isCorrect) {
                $classes .= ' !border-green-500 !bg-green-100';
            }
        @endphp

        <button
            type="button"
            class="{{ $classes }}"
            data-question-id="{{ $question->id }}"
            data-option-id="{{ $option->id }}"
            onclick="selectPredictionOption(this, {{ $question->id }}, {{ $option->id }})"
            {{ $isDisabled ? 'disabled' : '' }}>

            {{ $option->text }}

            @if($isSelected)
                <i class="fas fa-check-circle ml-2"></i>
            @endif

            @if($showResults && $isCorrect)
                <i class="fas fa-star ml-2 text-green-600"></i>
            @endif

            {{-- Show percentage if results are visible --}}
            @if($showResults)
                @php
                    $totalAnswers = $question->answers->count();
                    $optionAnswers = $question->answers->where('question_option_id', $option->id)->count();
                    $percentage = $totalAnswers > 0 ? round(($optionAnswers / $totalAnswers) * 100) : 0;
                @endphp
                <div class="text-xs text-gray-600 mt-1">
                    {{ $percentage }}% ({{ $optionAnswers }})
                </div>
            @endif
        </button>
    @endforeach
</div>

{{-- Feedback container --}}
<div id="prediction-feedback-{{ $question->id }}" class="mt-3 text-center hidden">
    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-offside-primary text-white text-sm font-semibold">
        <i class="fas fa-check-circle"></i>
        <span>¡Predicción guardada!</span>
    </div>
</div>
