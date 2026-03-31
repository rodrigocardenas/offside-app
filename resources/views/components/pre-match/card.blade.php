<!-- Pre Match Card Component -->
<div class="pre-match-card border-l-4 border-l-purple-600 dark:border-l-purple-400 bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow-md hover:shadow-lg transition-shadow mb-4">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-purple-700 dark:from-purple-900 dark:to-purple-800 px-6 py-4">
        <div class="flex justify-between items-center">
            <div>
                <span class="text-white font-bold text-sm flex items-center gap-2">
                    <span class="text-lg">🔥</span> PRE MATCH CHALLENGE
                </span>
                <h3 class="text-xl font-bold text-white mt-2">
                    {{ $match->home_team->name ?? 'Home' }} vs {{ $match->away_team->name ?? 'Away' }}
                </h3>

                <p class="text-purple-200 text-sm mt-1">
                    ⏰ {{ $match->scheduled_date?->format('d/m/Y H:i') ?? 'TBD' }}
                </p>
            </div>
            <span class="px-4 py-2 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs rounded-full font-bold whitespace-nowrap">
                {{ $preMatch->status }}
            </span>
        </div>
    </div>

    <!-- Content -->
    <div class="px-6 py-4 space-y-4">
        <!-- Penalty Badge -->
        <div class="p-4 rounded-lg"
            @switch($preMatch->penalty_type)
                @case('POINTS')
                    style="background-color: rgba(239, 68, 68, 0.1); border-left: 4px solid rgb(239, 68, 68);"
                @break
                @case('SOCIAL')
                    style="background-color: rgba(249, 115, 22, 0.1); border-left: 4px solid rgb(249, 115, 22);"
                @break
                @case('REVENGE')
                    style="background-color: rgba(168, 85, 247, 0.1); border-left: 4px solid rgb(168, 85, 247);"
                @endswitch
        >
            @switch($preMatch->penalty_type)
                @case('POINTS')
                    <p class="font-bold text-red-800 dark:text-red-200">
                        💔 CASTIGO: Restar {{ $preMatch->penalty_points }} puntos
                    </p>
                @break
                @case('SOCIAL')
                    <p class="font-bold text-orange-800 dark:text-orange-200">
                        🍽️ CASTIGO: {{ $preMatch->penalty }}
                    </p>
                @break
                @case('REVENGE')
                    <p class="font-bold text-purple-800 dark:text-purple-200">
                        ⚔️ CASTIGO: {{ $preMatch->penalty }}
                    </p>
                @endswitch
        </div>

        <!-- Propositions Progress -->
        <div>
            <div class="flex justify-between items-center mb-2">
                <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
                    {{ $preMatch->propositions->count() }} propuestas
                </p>
                <span class="text-xs font-bold text-green-600 dark:text-green-400">
                    ✅ {{ $preMatch->propositions->where('validation_status', 'ACCEPTED')->count() }} aceptadas
                </span>
            </div>
            <div class="w-full bg-gray-300 dark:bg-gray-600 rounded-full h-2.5">
                <div
                    class="bg-green-500 h-2.5 rounded-full transition-all duration-300"
                    style="width: {{ $preMatch->propositions->count() > 0 ? ($preMatch->propositions->where('validation_status', 'ACCEPTED')->count() / $preMatch->propositions->count() * 100) : 0 }}%"
                ></div>
            </div>
        </div>

        <!-- Action Buttons -->
        @if($preMatch->status === 'OPEN' || $preMatch->status === 'DRAFT')
            <div class="flex gap-2 pt-2">
                @if($preMatch->status === 'OPEN')
                    <button
                        onclick="openProposalModal({{ $preMatch->id }})"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded font-bold text-sm transition"
                    >
                        ➕ Nueva Acción
                    </button>
                @endif
            </div>
        @elseif($preMatch->status === 'RESOLVED')
            <button
                onclick="viewPenalties({{ $preMatch->id }})"
                class="w-full bg-purple-600 hover:bg-purple-700 text-white py-2 rounded font-bold transition"
            >
                🏆 Ver Castigos
            </button>
        @endif
    </div>
</div>
