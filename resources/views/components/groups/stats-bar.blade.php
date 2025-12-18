@props([
    'streak' => 0,
    'accuracy' => 0,
    'groupsCount' => 0
])

<div class="stats-bar">
    <div class="stat-item">
        <i class="fas fa-trophy"></i> Racha: <span class="stat-value">{{ $streak }} días</span>
    </div>
    <div class="stat-item">
        <i class="fas fa-bullseye"></i> Aciertos: <span class="stat-value">{{ $accuracy }}%</span>
    </div>
    <div class="stat-item">
        <i class="fas fa-users"></i> Grupos: <span class="stat-value">{{ $groupsCount }}</span>
    </div>
</div>
