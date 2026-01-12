@props([
    'streak' => 0,
    'accuracy' => 0,
    'groupsCount' => 0
])

<div class="stats-bar">
    <div class="stat-item">
        <i class="fas fa-trophy"></i> {{ __('views.groups.streak') }}: <span class="stat-value">{{ $streak }} {{ __('messages.days') }}</span>
    </div>
    <div class="stat-item">
        <i class="fas fa-bullseye"></i> {{ __('views.rankings.accuracy') }}: <span class="stat-value">{{ $accuracy }}%</span>
    </div>
    <div class="stat-item">
        <i class="fas fa-users"></i> {{ __('views.groups.groups_count') }}: <span class="stat-value">{{ $groupsCount }}</span>
    </div>
</div>
