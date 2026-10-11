<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Partidos de hoy · Offside Club</title>
    <meta property="og:title" content="Predice partidos en Offside Club">
    <meta property="og:description" content="Todos los partidos de hoy y mañana. Predice los resultados en Offside Club.">
    <meta property="og:image" content="{{ asset('images/estadio.avif') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        :root{--gold:#00deb0;--gold-dk:#17b796;--navy:#0a2e2c;--navy-mid:#0f3d3a;--navy-light:#1a524e;--white:#fff;--muted:#b0b0b0;--border:rgba(0,222,176,.2)}
        html,body{min-height:100%;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--navy);color:var(--white);overflow-x:hidden}
        .bg-stadium{position:fixed;inset:0;background:linear-gradient(to bottom,rgba(10,46,44,.80) 0%,rgba(10,46,44,.12) 50%,rgba(10,46,44,1) 100%),url('{{ asset("images/estadio.avif") }}') center/cover no-repeat;z-index:0}
        .page{position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column}
        .corner{position:fixed;z-index:20;opacity:.90}
        .corner.tl{top:14px;left:14px;animation:float 4s ease-in-out infinite}
        .corner.tr{top:14px;right:14px;animation:float 4s ease-in-out infinite reverse}
        .corner img{height:38px;width:auto;filter:drop-shadow(0 2px 8px rgba(0,0,0,.5))}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
        /* header */
        .page-header{padding:72px 20px 28px;text-align:center;border-bottom:1px solid rgba(255,255,255,.1)}
        .wc-badge{display:inline-flex;align-items:center;gap:7px;background:rgba(0,222,176,.1);border:1px solid rgba(0,222,176,.38);color:var(--gold);font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;padding:5px 13px;border-radius:20px;margin-bottom:14px}
        .page-title{font-size:24px;font-weight:900;color:var(--white);margin-bottom:4px}
        .page-sub{font-size:13px;color:var(--muted)}
        /* matches */
        .matches-wrap{max-width:520px;margin:0 auto;padding:20px 16px 60px;flex:1}
        .day-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--gold);margin:22px 0 10px;padding-left:4px;display:flex;align-items:center;gap:8px}
        .day-label::after{content:'';flex:1;height:1px;background:rgba(0,222,176,.18)}
        /* match card */
        .match-card{display:flex;align-items:center;gap:12px;background:rgba(16,37,69,.82);border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:14px 16px;margin-bottom:9px;text-decoration:none;transition:all .2s;backdrop-filter:blur(8px)}
        .match-card:hover{border-color:rgba(0,222,176,.38);background:rgba(26,82,78,.90);transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.3)}
        .match-time{font-size:13px;font-weight:700;color:var(--gold);min-width:42px;text-align:center;flex-shrink:0}
        /* crests row inside card */
        .card-crests{display:flex;align-items:center;gap:6px;flex:1;min-width:0}
        .card-crest{width:32px;height:32px;object-fit:contain;background:rgba(255,255,255,.07);border-radius:50%;padding:4px;border:1px solid rgba(0,222,176,.18);flex-shrink:0}
        .card-names{display:flex;flex-direction:column;gap:1px;flex:1;min-width:0}
        .card-match-name{font-size:14px;font-weight:700;color:var(--white);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .card-meta{font-size:11px;color:var(--muted)}
        .card-arrow{color:var(--muted);font-size:14px;flex-shrink:0;transition:color .2s,transform .2s}
        .match-card:hover .card-arrow{color:var(--gold);transform:translateX(3px)}
        /* empty */
        .empty{text-align:center;padding:60px 20px;color:var(--muted)}
        .empty i{font-size:44px;margin-bottom:14px;display:block;color:rgba(0,222,176,.28)}
        .empty strong{display:block;color:var(--white);font-size:17px;margin-bottom:6px}
        /* footer */
        .wc-foot{text-align:center;padding:20px 20px 32px;font-size:12px;color:var(--muted)}
        .wc-foot a{color:var(--gold);text-decoration:none}
    </style>
</head>
<body>
<div class="bg-stadium"></div>
<div class="corner tl"><img src="{{ asset('images/logo-offside.png') }}" alt="Offside Club"></div>

<div class="page">
    <div class="page-header">
        <div class="wc-badge"><i class="fas fa-futbol"></i> Offside Club</div>
        <div class="page-title">Partidos del día</div>
        <div class="page-sub">Predice el resultado antes del pitido inicial</div>
    </div>

    <div class="matches-wrap">
        @if($matches->isEmpty())
            <div class="empty">
                <i class="fas fa-calendar-times"></i>
                <strong>No hay partidos hoy ni mañana</strong>
                Vuelve más tarde para consultar los próximos partidos.
            </div>
        @else
            @php
                $today    = \Carbon\Carbon::now()->utc()->startOfDay();
                $tomorrow = \Carbon\Carbon::now()->utc()->addDay()->startOfDay();
                $byDay    = $matches->groupBy(function($m) use ($today,$tomorrow){
                    $d = \Carbon\Carbon::parse($m->date)->utc()->startOfDay();
                    if($d->eq($today))    return 'Hoy';
                    if($d->eq($tomorrow)) return 'Mañana';
                    return $d->isoFormat('D [de] MMMM');
                });
            @endphp
            @foreach($byDay as $dayLabel => $dayMatches)
                <div class="day-label">{{ $dayLabel }}</div>
                @foreach($dayMatches as $m)
                    <a href="{{ route('wc.match', $m->id) }}" class="match-card">
                        <div class="match-time">
                            {{ \Carbon\Carbon::parse($m->date)->timezone(auth()->user()?->timezone ?? 'UTC')->format('H:i') }}
                        </div>
                        <div class="card-crests">
                            <img class="card-crest"
                                 src="{{ $m->homeTeam?->crest_url ?? asset('images/default-crest.png') }}"
                                 alt="{{ $m->homeTeam?->name ?? $m->home_team }}"
                                 onerror="this.src='{{ asset('images/default-crest.png') }}'">
                            <div class="card-names">
                                <div class="card-match-name">{{ $m->homeTeam?->name ?? $m->home_team }} vs {{ $m->awayTeam?->name ?? $m->away_team }}</div>
                                <div class="card-meta">@if($m->group){{ str_replace('GROUP_','Grupo ',$m->group) }} · @endif{{ \Carbon\Carbon::parse($m->date)->format('d M') }}</div>
                            </div>
                            <img class="card-crest"
                                 src="{{ $m->awayTeam?->crest_url ?? asset('images/default-crest.png') }}"
                                 alt="{{ $m->awayTeam?->name ?? $m->away_team }}"
                                 onerror="this.src='{{ asset('images/default-crest.png') }}'">
                        </div>
                        <div class="card-arrow"><i class="fas fa-chevron-right"></i></div>
                    </a>
                @endforeach
            @endforeach
        @endif
    </div>

    <div class="wc-foot"><a href="{{ config('app.url') }}">Offside Club</a></div>
</div>
</body>
</html>
