<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>⚽ {{ $match->home_team }} vs {{ $match->away_team }} · Mundial 2026</title>
    <meta property="og:title" content="⚽ {{ $match->home_team }} vs {{ $match->away_team }} · Predice ahora">
    <meta property="og:description" content="¿Quién ganará? Predice el resultado y compite en Offside Club.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('images/estadio.avif') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        :root{
            --gold:#e8c11a;--gold-dk:#c5a215;
            --navy:#0b1e3a;--navy-mid:#102545;--navy-light:#162e52;
            --white:#fff;--muted:#9ab0cc;--border:rgba(232,193,26,.22);
        }
        html,body{min-height:100%;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--navy);color:var(--white);overflow-x:hidden}
        /* stadium bg */
        .bg-stadium{position:fixed;inset:0;background:linear-gradient(to bottom,rgba(11,30,58,.72) 0%,rgba(11,30,58,.90) 50%,rgba(11,30,58,.98) 100%),url('{{ asset("images/estadio.avif") }}') center/cover no-repeat;z-index:0}
        .page{position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column}
        /* corner logos */
        .corner{position:fixed;z-index:20;opacity:0}
        .corner.tl{top:14px;left:14px;animation:enter-left .6s cubic-bezier(.22,1,.36,1) .1s forwards}
        .corner.tr{top:14px;right:14px;animation:enter-right .6s cubic-bezier(.22,1,.36,1) .25s forwards}
        .corner img{height:40px;width:auto;filter:drop-shadow(0 2px 8px rgba(0,0,0,.5))}
        @keyframes enter-left{from{opacity:0;transform:translateX(-18px) scale(.85)}to{opacity:.92;transform:translateX(0) scale(1)}}
        @keyframes enter-right{from{opacity:0;transform:translateX(18px) scale(.85)}to{opacity:.92;transform:translateX(0) scale(1)}}
        /* hero */
        .hero{padding:80px 20px 0;text-align:center;display:flex;flex-direction:column;align-items:center}
        /* match info badge (unified) */
        .match-info-badge{display:inline-flex;flex-direction:column;align-items:center;gap:5px;background:rgba(232,193,26,.07);border:1.5px solid rgba(232,193,26,.38);border-radius:16px;padding:12px 20px;margin-bottom:26px;min-width:220px}
        .badge-wc{display:flex;align-items:center;gap:7px;color:var(--gold);font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase}
        .badge-sep{width:100%;height:1px;background:rgba(232,193,26,.2)}
        .badge-meta{font-size:12px;color:var(--muted);letter-spacing:.3px}
        .badge-date{font-size:14px;font-weight:700;color:var(--gold)}
        /* teams */
        .teams-row{display:flex;align-items:center;justify-content:center;gap:8px;max-width:360px;width:100%;margin-bottom:4px}
        .team-block{flex:1;display:flex;flex-direction:column;align-items:center;gap:10px}
        .crest-ring{width:76px;height:76px;background:rgba(255,255,255,.07);border:2px solid rgba(232,193,26,.25);border-radius:50%;display:flex;align-items:center;justify-content:center;padding:9px;transition:border-color .3s,box-shadow .3s}
        .crest-ring:hover{border-color:var(--gold);box-shadow:0 0 16px rgba(232,193,26,.3)}
        .crest-ring img{width:48px;height:48px;object-fit:contain}
        .team-name{font-size:12px;font-weight:700;color:var(--white);text-align:center;text-transform:uppercase;letter-spacing:.5px;line-height:1.2}
        .vs-col{display:flex;flex-direction:column;align-items:center;gap:5px;flex-shrink:0;padding-top:8px}
        .vs-txt{font-size:11px;font-weight:900;color:var(--muted);letter-spacing:2px}
        .vs-dot{width:4px;height:4px;background:var(--gold);border-radius:50%}
        /* card */
        .action-wrap{flex:1;padding:24px 16px 48px;display:flex;flex-direction:column;align-items:center;margin-top:18px}
        .card{width:100%;max-width:440px;background:rgba(16,37,69,.88);border:1px solid var(--border);border-radius:22px;padding:26px 20px;backdrop-filter:blur(14px);box-shadow:0 8px 40px rgba(0,0,0,.4)}
        .card-title{font-size:16px;font-weight:700;color:var(--white);text-align:center;margin-bottom:16px}
        /* vote btns */
        .vote-list{display:flex;flex-direction:column;gap:10px}
        .vote-btn{display:flex;align-items:center;justify-content:space-between;padding:15px 18px;background:rgba(255,255,255,.045);border:1.5px solid rgba(232,193,26,.18);border-radius:13px;color:var(--white);font-size:15px;font-weight:600;cursor:pointer;width:100%;text-align:left;transition:all .2s;position:relative;overflow:hidden}
        .vote-btn::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(232,193,26,.07),transparent);transform:translateX(-100%);transition:transform .4s}
        .vote-btn:hover::after{transform:translateX(100%)}
        .vote-btn:hover{background:rgba(232,193,26,.10);border-color:var(--gold);color:var(--gold);transform:translateX(3px)}
        .vote-arrow{opacity:.45;font-size:12px;transition:opacity .2s,transform .2s}
        .vote-btn:hover .vote-arrow{opacity:1;transform:translateX(4px)}
        /* cta */
        .cta{display:flex;align-items:center;justify-content:center;gap:9px;padding:16px 24px;background:linear-gradient(135deg,var(--gold),var(--gold-dk));color:var(--navy);font-size:16px;font-weight:800;border-radius:50px;border:none;cursor:pointer;width:100%;box-shadow:0 4px 20px rgba(232,193,26,.35);transition:all .25s;letter-spacing:.3px}
        .cta:hover{transform:translateY(-2px) scale(1.02);box-shadow:0 8px 28px rgba(232,193,26,.45)}
        .cta-hint{text-align:center;margin-top:9px;font-size:12px;color:var(--muted)}
        /* input */
        .field{padding:14px 17px;background:rgba(255,255,255,.07);border:1.5px solid rgba(232,193,26,.28);border-radius:12px;color:var(--white);font-size:16px;outline:none;width:100%;transition:border-color .2s,background .2s}
        .field:focus{border-color:var(--gold);background:rgba(232,193,26,.05)}
        .field::placeholder{color:var(--muted)}
        .submit{padding:15px;background:linear-gradient(135deg,var(--gold),var(--gold-dk));color:var(--navy);font-size:16px;font-weight:800;border-radius:12px;border:none;cursor:pointer;width:100%;transition:all .2s;box-shadow:0 4px 16px rgba(232,193,26,.3)}
        .submit:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(232,193,26,.4)}
        .form-title{font-size:17px;font-weight:700;color:var(--white);margin-bottom:3px}
        .form-sub{font-size:13px;color:var(--muted);margin-bottom:14px}
        .back-lnk{text-align:center;margin-top:10px}
        .back-lnk a{font-size:13px;color:var(--muted);text-decoration:none}
        .back-lnk a:hover{color:var(--white)}
        /* voted */
        .voted-box{background:rgba(232,193,26,.07);border:1.5px solid rgba(232,193,26,.38);border-radius:14px;padding:18px;text-align:center;margin-bottom:14px}
        .voted-lbl{font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
        .voted-val{font-size:20px;font-weight:800;color:var(--gold)}
        .outline-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:transparent;border:1.5px solid rgba(232,193,26,.35);color:var(--white);font-size:14px;font-weight:600;border-radius:12px;cursor:pointer;width:100%;text-decoration:none;transition:all .2s;margin-bottom:9px}
        .outline-btn:hover{border-color:var(--gold);color:var(--gold);background:rgba(232,193,26,.06)}
        .ghost{display:block;text-align:center;font-size:13px;color:var(--muted);text-decoration:none;margin-top:4px}
        .ghost:hover{color:var(--white)}
        /* closed */
        .closed-box{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:26px 18px;text-align:center}
        /* alert */
        .alert-err{background:rgba(239,68,68,.11);border:1px solid rgba(239,68,68,.33);color:#fca5a5;padding:11px 15px;border-radius:10px;margin-bottom:16px;font-size:13px}
        /* footer */
        .wc-foot{text-align:center;padding:16px 20px 32px;font-size:12px;color:var(--muted);position:relative;z-index:1}
        .wc-foot a{color:var(--gold);text-decoration:none}
        @media(min-width:480px){.card{padding:34px 30px}}
    </style>
</head>
<body>
<div class="bg-stadium"></div>

{{-- Logos en esquinas --}}
<div class="corner tl"><img src="{{ asset('images/logo-offside-192x192.png') }}" alt="Offside Club"></div>
<div class="corner tr"><img src="{{ asset('images/2026_FIFA_World_Cup_emblem.svg.png') }}" alt="FIFA World Cup 2026"></div>

<div class="page">

    {{-- HERO --}}
    <div class="hero">
        <div class="match-info-badge">
            <div class="badge-wc"><i class="fas fa-globe-americas"></i> FIFA World Cup 2026</div>
            <div class="badge-sep"></div>
            <div class="badge-meta">
                @if($match->group){{ str_replace('GROUP_', 'Grupo ', $match->group) }} ·@endif
                {{ $match->stage ? ucwords(strtolower(str_replace('_', ' ', $match->stage))) : 'Fase de grupos' }}
            </div>
            <div class="badge-date">
                <i class="far fa-calendar-alt" style="margin-right:5px"></i>
                {{ \Carbon\Carbon::parse($match->date)->timezone(auth()->user()?->timezone ?? 'UTC')->isoFormat('D [de] MMMM · HH:mm') }}
            </div>
        </div>

        <div class="teams-row">
            <div class="team-block">
                <div class="crest-ring">
                    <img src="{{ $match->homeTeam?->crest_url ?? asset('images/default-crest.png') }}"
                         alt="{{ $match->home_team }}"
                         onerror="this.src='{{ asset('images/default-crest.png') }}'">
                </div>
                <div class="team-name">{{ $match->home_team }}</div>
            </div>
            <div class="vs-col">
                <div class="vs-dot"></div>
                <div class="vs-txt">VS</div>
                <div class="vs-dot"></div>
            </div>
            <div class="team-block">
                <div class="crest-ring">
                    <img src="{{ $match->awayTeam?->crest_url ?? asset('images/default-crest.png') }}"
                         alt="{{ $match->away_team }}"
                         onerror="this.src='{{ asset('images/default-crest.png') }}'">
                </div>
                <div class="team-name">{{ $match->away_team }}</div>
            </div>
        </div>
    </div>

    {{-- ACTION CARD --}}
    <div class="action-wrap">
        <div class="card">

            @if(session('error'))
                <div class="alert-err"><i class="fas fa-exclamation-circle" style="margin-right:6px"></i>{{ session('error') }}</div>
            @endif

            @php $matchStarted = $match->date->lte(now()); @endphp

            @if($matchStarted && !$userAnswer)
                <div class="closed-box">
                    <span style="font-size:34px;color:var(--gold);display:block;margin-bottom:10px"><i class="fas fa-lock"></i></span>
                    <strong style="display:block;font-size:15px;margin-bottom:7px">Este partido ya comenzó</strong>
                    <span style="font-size:13px;color:var(--muted)">Las predicciones cerraron antes del pitido inicial.</span>
                    <div style="margin-top:18px"><a href="{{ route('wc.hoy') }}" style="color:var(--gold);font-weight:700;text-decoration:none;font-size:14px">⚽ Ver otros partidos →</a></div>
                </div>

            @elseif($userAnswer)
                <div class="voted-box">
                    <div class="voted-lbl">🏅 Tu predicción</div>
                    <div class="voted-val">{{ $userAnswer->questionOption->text }}</div>
                </div>
                <a href="{{ route('wc.resultado', $match->id) }}" class="outline-btn">
                    <i class="fas fa-share-alt"></i> Compartir tu predicción
                </a>
                <a href="{{ route('wc.hoy') }}" class="ghost">⚽ Predecir más partidos</a>

            @elseif(auth()->check() && $question)
                <div class="card-title">⚽ ¿Quién ganará?</div>
                <form action="{{ route('wc.votar', $match->id) }}" method="POST" class="vote-list">
                    @csrf
                    @foreach($question->options as $option)
                        <button type="submit" name="question_option_id" value="{{ $option->id }}" class="vote-btn">
                            <span>{{ $option->text }}</span>
                            <span class="vote-arrow"><i class="fas fa-chevron-right"></i></span>
                        </button>
                    @endforeach
                </form>

            @else
                <div x-data="{ showForm: false }">
                    <div x-show="!showForm">
                        <div class="card-title">⚽ ¿Quién ganará?</div>
                        <p style="color:var(--muted);font-size:13px;text-align:center;margin-bottom:20px;line-height:1.55">Únete gratis y predice el resultado.<br>Solo necesitas un nickname.</p>
                        <button @click="showForm=true;$nextTick(()=>$refs.ni.focus())" class="cta">
                            <i class="fas fa-futbol"></i> Predice el resultado
                        </button>
                        <p class="cta-hint">Gratis · Sin contraseña · 30 segundos</p>
                    </div>
                    <div x-show="showForm" x-transition.duration.200ms>
                        <form action="{{ route('wc.auth', $match->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="timezone" id="tzInput" value="UTC">
                            <div class="form-title">¿Cómo te llaman?</div>
                            <div class="form-sub">Ingresa tu nickname para votar.</div>
                            <div style="display:flex;flex-direction:column;gap:11px">
                                <input type="text" name="name" x-ref="ni" class="field"
                                    placeholder="Tu nickname (ej: RodriGoal10)"
                                    maxlength="30" autocomplete="off" required>
                                @error('name')<span style="color:#fca5a5;font-size:12px">{{ $message }}</span>@enderror
                                <button type="submit" class="submit"><i class="fas fa-futbol" style="margin-right:7px"></i>Entrar y predecir</button>
                                <div class="back-lnk"><a href="#" @click.prevent="showForm=false">← Volver</a></div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <div class="wc-foot"><a href="{{ route('wc.hoy') }}">⚽ Ver todos los partidos del día</a></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded',function(){
        const tz=document.getElementById('tzInput');
        if(tz){try{tz.value=Intl.DateTimeFormat().resolvedOptions().timeZone}catch(e){}}
    });
</script>
</body>
</html>
