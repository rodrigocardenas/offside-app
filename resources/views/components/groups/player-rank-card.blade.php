@props([
    'player',
    'rank',
    'isCurrentUser' => false
])

@php
    $rankClass = match($rank) {
        1 => 'rank-gold',
        2 => 'rank-silver',
        3 => 'rank-bronze',
        default => 'rank-other'
    };
@endphp

<div class="player-item {{ $isCurrentUser ? 'border-offside-primary bg-offside-primary bg-opacity-10' : '' }}">
    {{-- Rank Badge --}}
    <div class="w-5 h-5 rounded-full {{ $rankClass }} flex items-center justify-center text-[10px] mb-1">
        {{ $rank }}
    </div>

    {{-- Player Info --}}
    <div class="text-center">
        <div class="text-xs font-semibold text-gray-800 mb-0.5 truncate max-w-[70px]" title="{{ $player->name }}">
            {{ $isCurrentUser ? __('messages.you') : $player->name }}
        </div>
        <div class="text-xs font-bold text-offside-primary">
            {{ $player->total_points ?? 0 }}
        </div>
    </div>

    {{-- Current User Indicator --}}
    @if($isCurrentUser)
        <div class="absolute -top-1 -right-1 w-3 h-3 bg-offside-primary rounded-full border-2 border-white"></div>
    @endif
</div>
