<!-- Pre Match Proposition Item Component -->
<div class="proposition-item border-l-4 border-l-blue-500 dark:border-l-blue-400 pl-4 py-4 mb-3 bg-gray-50 dark:bg-gray-800 rounded">
    <!-- Propositor Header -->
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            @if($proposition->user->avatar)
                <img src="{{ $proposition->user->avatar }}" alt="{{ $proposition->user->name }}" class="w-6 h-6 rounded-full object-cover">
            @else
                <div class="w-6 h-6 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold">
                    {{ substr($proposition->user->name, 0, 1) }}
                </div>
            @endif
            <span class="font-bold text-sm text-gray-900 dark:text-white">{{ $proposition->user->name }}</span>
        </div>

        <!-- Probability Badge -->
        @php
            $probabilityClass = match(true) {
                $proposition->probability < 0.30 => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                $proposition->probability >= 0.30 && $proposition->probability < 0.60 => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                default => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            };
        @endphp

        <span class="text-xs font-bold px-2 py-1 rounded {{ $probabilityClass }}">
            {{ number_format($proposition->probability * 100, 0) }}% probable
        </span>
    </div>

    <!-- Action Text -->
    <p class="text-lg font-bold text-gray-900 dark:text-white mb-1">
        "{{ $proposition->action }}"
    </p>

    @if($proposition->description)
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 italic">
            {{ $proposition->description }}
        </p>
    @endif

    <!-- Validation Progress -->
    <div class="flex items-center gap-2 mb-3">
        <div class="flex-1 bg-gray-300 dark:bg-gray-600 rounded-full h-2.5">
            <div
                class="h-2.5 rounded-full transition-all"
                @switch($proposition->validation_status)
                    @case('ACCEPTED')
                        style="background-color: rgb(34, 197, 94); width: {{ $proposition->approval_percentage }}%;"
                    @break
                    @case('REJECTED')
                        style="background-color: rgb(239, 68, 68); width: {{ $proposition->approval_percentage }}%;"
                    @break
                    @default
                        style="background-color: rgb(234, 179, 8); width: {{ $proposition->approval_percentage }}%;"
                @endswitch
            ></div>
        </div>
        <span
            class="text-xs font-bold px-2 py-1 rounded whitespace-nowrap"
            @switch($proposition->validation_status)
                @case('ACCEPTED')
                    style="background-color: rgba(34, 197, 94, 0.2); color: rgb(20, 124, 39);"
                    class="dark:bg-green-900 dark:text-green-200"
                @break
                @case('REJECTED')
                    style="background-color: rgba(239, 68, 68, 0.2); color: rgb(153, 27, 27);"
                    class="dark:bg-red-900 dark:text-red-200"
                @break
                @default
                    style="background-color: rgba(234, 179, 8, 0.2); color: rgb(120, 53, 15);"
                    class="dark:bg-yellow-900 dark:text-yellow-200"
                @endswitch
        >
            {{ $proposition->validation_status }} ({{ $proposition->approval_percentage }}%)
        </span>
    </div>

    <!-- Vote Count -->
    <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
        {{ $proposition->votes->count() }} votos registrados
    </p>

    <!-- Vote Buttons (only if PENDING and hasn't voted and is not proposer) -->
    @if($proposition->validation_status === 'PENDING' && Auth::check() && Auth::id() !== $proposition->user_id)
        @php
            $userVoted = $proposition->votes->where('user_id', Auth::id())->first();
        @endphp

        @if(!$userVoted)
            <div class="flex gap-2">
                <button
                    onclick="voteProposition({{ $proposition->id }}, true)"
                    class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 rounded font-bold text-sm transition"
                >
                    ✅ Es posible
                </button>
                <button
                    onclick="voteProposition({{ $proposition->id }}, false)"
                    class="flex-1 bg-red-500 hover:bg-red-600 text-white py-2 rounded font-bold text-sm transition"
                >
                    ❌ Muy extremo
                </button>
            </div>
        @else
            <div class="text-xs text-gray-600 dark:text-gray-400 mt-2 p-2 bg-gray-200 dark:bg-gray-700 rounded">
                ✓ Tu voto: <strong>{{ $userVoted->approved ? 'SÍ ✅' : 'NO ❌' }}</strong>
            </div>
        @endif
    @elseif($proposition->validation_status === 'PENDING' && Auth::check() && Auth::id() === $proposition->user_id)
        <div class="text-xs text-gray-600 dark:text-gray-400 mt-2 p-2 bg-gray-200 dark:bg-gray-700 rounded">
            ⚠️ No puedes votar tu propia propuesta
        </div>
    @endif
</div>
