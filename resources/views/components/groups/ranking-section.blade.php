@props([
    'players' => collect(),
    'currentUserId' => null,
    'showExpandButton' => true,
    'title' => 'Ranking'
])

<div class="mx-4 mt-4 bg-white rounded-2xl p-4 border border-gray-300 shadow-card">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2 text-base font-semibold text-gray-800">
            <i class="fas fa-trophy text-yellow-500"></i>
            {{ $title }}
        </div>

        @if($showExpandButton)
            <button
                onclick="expandRanking()"
                class="text-xs px-3 py-1.5 rounded-full bg-gray-100 text-gray-700 border border-gray-300 hover:bg-gray-200 hover:border-offside-primary transition-all cursor-pointer">
                {{ __('messages.view_all') }}
            </button>
        @endif
    </div>

    @if($players->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <i class="fas fa-users text-3xl mb-2"></i>
            <p class="text-sm">{{ __('views.rankings.no_players_yet') }}</p>
        </div>
    @else
        <div class="ranking-list">
            @foreach($players->take(10) as $index => $player)
                <x-groups.player-rank-card
                    :player="$player"
                    :rank="$index + 1"
                    :is-current-user="$player->id == $currentUserId"
                />
            @endforeach
        </div>
    @endif
</div>
