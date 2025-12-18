@props([
    'group',
    'userRank' => null,
    'hasPending' => false,
    'showMembers' => true
])

<div class="group-card" onclick="window.location.href='{{ route('groups.show', $group) }}'">
    <div class="group-status">
        @if($hasPending)
            <i class="fas fa-exclamation-triangle"></i>
        @else
            <i class="fas fa-check-circle"></i>
        @endif
    </div>
    
    <div class="group-header">
        <div class="group-avatar">
            @if($group->competition && $group->competition->crest_url)
                <img src="{{ asset('images/competitions/' . $group->competition->crest_url) }}" alt="{{ $group->name }}">
            @else
                <i class="fas fa-trophy" style="color: #000;"></i>
            @endif
        </div>
        <div class="group-info">
            <h3>{{ $group->name }}</h3>
            <div class="group-stats">
                @if($showMembers)
                    <span><i class="fas fa-users"></i> {{ $group->users_count ?? $group->users->count() }} miembros</span>
                @endif
                @if($userRank)
                    <div class="ranking-badge">
                        <i class="fas fa-trophy"></i> Ranking: #{{ $userRank }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
