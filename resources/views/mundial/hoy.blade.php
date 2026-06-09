<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>⚽ Partidos de hoy · Mundial 2026</title>

    <meta property="og:title" content="⚽ Predice los partidos del Mundial 2026">
    <meta property="og:description" content="Todos los partidos de hoy y mañana. Predice los resultados en Offside Club.">
    <meta property="og:image" content="{{ asset('images/wc2026-og.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold: #e8c11a; --gold-dark: #c5a215;
            --navy: #0b1e3a; --navy-mid: #102545; --navy-light: #162e52;
            --white: #fff; --muted: #b0bec5;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--navy);
            min-height: 100vh;
            padding: 0 0 80px;
            color: var(--white);
        }

        .page-header {
            background: linear-gradient(135deg, #0b1e3a 0%, #162e52 100%);
            padding: 48px 20px 32px;
            text-align: center;
            border-bottom: 1px solid rgba(232,193,26,.2);
        }

        .wc-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(232,193,26,.15);
            border: 1px solid rgba(232,193,26,.4);
            color: var(--gold);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 6px 14px;
            border-radius: 20px;
            margin-bottom: 16px;
        }

        .page-title {
            font-size: 26px;
            font-weight: 900;
            color: var(--white);
            margin-bottom: 6px;
        }

        .page-sub {
            font-size: 14px;
            color: var(--muted);
        }

        .matches-container {
            max-width: 520px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .day-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--gold);
            margin: 24px 0 12px;
            padding-left: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .day-label::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(232,193,26,.2);
        }

        .match-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--navy-mid);
            border: 1px solid rgba(255,255,255,.06);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 10px;
            text-decoration: none;
            transition: all .2s;
        }

        .match-card:hover {
            border-color: rgba(232,193,26,.4);
            background: var(--navy-light);
            transform: translateY(-1px);
        }

        .match-time {
            font-size: 13px;
            font-weight: 700;
            color: var(--gold);
            min-width: 42px;
            text-align: center;
            flex-shrink: 0;
        }

        .match-info {
            flex: 1;
        }

        .match-teams {
            font-size: 15px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 3px;
        }

        .match-meta {
            font-size: 12px;
            color: var(--muted);
        }

        .match-arrow {
            color: var(--muted);
            font-size: 16px;
            flex-shrink: 0;
            transition: color .2s;
        }

        .match-card:hover .match-arrow { color: var(--gold); }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
            color: rgba(232,193,26,.3);
        }

        .empty-state strong {
            display: block;
            color: var(--white);
            font-size: 18px;
            margin-bottom: 8px;
        }

        .wc-footer {
            text-align: center;
            padding: 24px 20px;
            font-size: 13px;
            color: var(--muted);
        }

        .wc-footer a { color: var(--gold); text-decoration: none; }
    </style>
</head>
<body>

    <div class="page-header">
        <div class="wc-badge">
            <i class="fas fa-globe-americas"></i>
            FIFA World Cup 2026
        </div>
        <div class="page-title">Partidos del día</div>
        <div class="page-sub">Predice el resultado antes del pitido inicial</div>
    </div>

    <div class="matches-container">

        @if($matches->isEmpty())
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <strong>No hay partidos hoy ni mañana</strong>
                Vuelve cuando se acerque la próxima jornada del Mundial.
            </div>
        @else
            @php
                $today    = \Carbon\Carbon::now()->utc()->startOfDay();
                $tomorrow = \Carbon\Carbon::now()->utc()->addDay()->startOfDay();

                $byDay = $matches->groupBy(function($m) use ($today, $tomorrow) {
                    $date = \Carbon\Carbon::parse($m->date)->utc()->startOfDay();
                    if ($date->eq($today))    return 'Hoy';
                    if ($date->eq($tomorrow)) return 'Mañana';
                    return $date->format('d M');
                });
            @endphp

            @foreach($byDay as $dayLabel => $dayMatches)
                <div class="day-label">{{ $dayLabel }}</div>

                @foreach($dayMatches as $match)
                    <a href="{{ route('wc.match', $match->id) }}" class="match-card">
                        <div class="match-time">
                            {{ \Carbon\Carbon::parse($match->date)->timezone(auth()->user()?->timezone ?? 'UTC')->format('H:i') }}
                        </div>
                        <div class="match-info">
                            <div class="match-teams">
                                {{ $match->home_team }} vs {{ $match->away_team }}
                            </div>
                            <div class="match-meta">
                                @if($match->group){{ str_replace('GROUP_', 'Grupo ', $match->group) }} · @endif
                                {{ \Carbon\Carbon::parse($match->date)->format('d M') }}
                            </div>
                        </div>
                        <div class="match-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                @endforeach
            @endforeach
        @endif

    </div>

    <div class="wc-footer">
        <a href="{{ config('app.url') }}">Offside Club</a>
        &nbsp;·&nbsp; Copa del Mundo 2026
    </div>

</body>
</html>
