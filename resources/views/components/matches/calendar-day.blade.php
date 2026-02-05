<div class="calendar-day-group">
    <div class="day-header">
        <span class="day-date">
            @php
                $date = \Carbon\Carbon::parse($date);
                $today = \Carbon\Carbon::today();
                
                if ($date->isToday()) {
                    echo '<span class="day-badge today">HOY</span>';
                } elseif ($date->isTomorrow()) {
                    echo '<span class="day-badge tomorrow">MAÑANA</span>';
                } else {
                    echo $date->format('d M');
                }
            @endphp
        </span>
        <span class="day-name">
            {{ $date ? \Carbon\Carbon::parse($date)->format('l') : '' }}
        </span>
    </div>

    <div class="matches-container">
        @foreach($matches as $match)
            <x-matches.match-card :match="$match" />
        @endforeach
    </div>
</div>

<style>
    .calendar-day-group {
        margin-bottom: 16px;
    }

    .day-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        background: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color);
    }

    .day-date {
        font-weight: 700;
        font-size: 14px;
        color: var(--text-primary);
        min-width: 50px;
    }

    .day-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .day-badge.today {
        background: #ff6b6b;
        color: white;
    }

    .day-badge.tomorrow {
        background: #ffd93d;
        color: #333;
    }

    .day-name {
        margin-left: auto;
        font-size: 13px;
        color: var(--text-secondary);
        text-transform: capitalize;
    }

    .matches-container {
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding: 8px 8px;
    }
</style>
