@props([
    'question',
    'match' => null,
    'timeLeft' => null,
    'userAnswer' => null,
    'showResults' => false
])

<div class="prediction-section">
    {{-- Header --}}
    <div class="text-center mb-5">
        <div class="prediction-badge">
            {{ __('views.groups.prediction_of_the_day') }}
        </div>

        @if($match)
            <div class="text-xs text-gray-600 mt-3">
                <i class="fas fa-circle text-offside-primary" style="font-size: 6px;"></i>
                {{ $match->competition->name ?? 'Competición' }} •
                {{ __('views.groups.matchday') }} {{ $match->matchday ?? '-' }}
            </div>
        @endif
    </div>

    {{-- Match Info --}}
    @if($match)
        <div class="flex items-center justify-center gap-2 mb-5">
            {{-- Home Team --}}
            <span class="text-sm font-medium text-gray-800">
                {{ $match->homeTeam->name ?? $match->home_team }}
            </span>

            @if($match->homeTeam && $match->homeTeam->crest_url)
                <img src="{{ asset('images/teams/' . $match->homeTeam->crest_url) }}"
                     class="team-logo"
                     alt="{{ $match->homeTeam->name }}">
            @endif

            {{-- Time --}}
            <span class="match-time-inline">
                {{ @userTime($match->date, 'H:i') }}
            </span>

            @if($match->awayTeam && $match->awayTeam->crest_url)
                <img src="{{ asset('images/teams/' . $match->awayTeam->crest_url) }}"
                     class="team-logo"
                     alt="{{ $match->awayTeam->name }}">
            @endif

            {{-- Away Team --}}
            <span class="text-sm font-medium text-gray-800">
                {{ $match->awayTeam->name ?? $match->away_team }}
            </span>
        </div>
    @endif

    {{-- Question --}}
    <div class="prediction-question">
        {{ $question->title ?? $question->description }}
    </div>

    {{-- Options --}}
    <x-predictions.prediction-options
        :question="$question"
        :user-answer="$userAnswer"
        :show-results="$showResults"
    />

    {{-- Timer --}}
    @if($timeLeft && !$showResults)
        <div class="timer" id="prediction-timer-{{ $question->id }}"
             data-end-time="{{ $question->available_until }}">
            <i class="fas fa-clock"></i>
            <span class="timer-text">{{ __('views.groups.calculating') }}</span>
        </div>
    @endif

    {{-- User's Answer (if already answered) --}}
    @if($userAnswer && !$showResults)
        <div class="mt-4 text-center text-sm text-gray-600">
            <i class="fas fa-check-circle text-offside-primary"></i>
            Tu respuesta: <strong>{{ $userAnswer->questionOption->text }}</strong>
        </div>
    @endif

    {{-- Results (if showing results) --}}
    @if($showResults && $question->result_verified_at)
        <div class="mt-4 p-3 rounded-lg {{ $userAnswer && $userAnswer->is_correct ? 'bg-green-100 border border-green-300' : 'bg-red-100 border border-red-300' }}">
            <div class="text-center">
                @if($userAnswer && $userAnswer->is_correct)
                    <i class="fas fa-check-circle text-green-600 text-2xl mb-2"></i>
                    <p class="text-green-800 font-semibold">{{ __('views.groups.correct_answer') }}</p>
                    <p class="text-sm text-green-700">{{ str_replace('{points}', ($userAnswer->points_earned ?? 0), __('views.groups.points_earned')) }}</p>
                @else
                    <i class="fas fa-times-circle text-red-600 text-2xl mb-2"></i>
                    <p class="text-red-800 font-semibold">{{ __('views.groups.incorrect_answer') }}</p>
                @endif
            </div>
        </div>
    @endif
</div>
