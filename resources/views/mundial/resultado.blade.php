<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>⚽ Mi predicción · {{ $match->home_team }} vs {{ $match->away_team }}</title>
    <meta property="og:title" content="Predije {{ $votedOption }} en {{ $match->home_team }} vs {{ $match->away_team }}">
    <meta property="og:description" content="Juega en Offside Club y predice los partidos del Mundial 2026.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/estadio.avif') }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        :root{--gold:#e8c11a;--gold-dk:#c5a215;--navy:#0b1e3a;--navy-mid:#102545;--navy-light:#162e52;--white:#fff;--muted:#9ab0cc;--border:rgba(232,193,26,.22)}
        html,body{min-height:100%;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--navy);color:var(--white);overflow-x:hidden}
        .bg-stadium{position:fixed;inset:0;background:linear-gradient(to bottom,rgba(11,30,58,.78) 0%,rgba(11,30,58,.12) 60%,rgba(11,30,58,.99) 100%),url('{{ asset("images/estadio.avif") }}') center/cover no-repeat;z-index:0}
        .page{position:relative;z-index:1;min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:20px 16px 64px}
        .corner{position:fixed;z-index:20;opacity:.90}
        .corner.tl{top:14px;left:14px;animation:float 4s ease-in-out infinite}
        .corner.tr{top:14px;right:14px;animation:float 4s ease-in-out infinite reverse}
        .corner img{height:38px;width:auto;filter:drop-shadow(0 2px 8px rgba(0,0,0,.5))}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
        /* share card */
        .share-card{width:100%;max-width:420px;background:rgba(16,37,69,.90);border:1.5px solid var(--border);border-radius:22px;overflow:hidden;margin:64px 0 24px;backdrop-filter:blur(14px);box-shadow:0 8px 40px rgba(0,0,0,.45)}
        .card-head{background:linear-gradient(135deg,rgba(11,30,58,1) 0%,rgba(22,46,82,1) 100%);padding:22px 22px 18px;text-align:center;border-bottom:1px solid rgba(232,193,26,.18)}
        .card-wc{font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--gold);margin-bottom:12px}
        /* teams in card */
        .card-teams-row{display:flex;align-items:center;justify-content:center;gap:10px;margin-bottom:8px}
        .card-team{display:flex;flex-direction:column;align-items:center;gap:6px;flex:1}
        .card-crest{width:52px;height:52px;object-fit:contain;background:rgba(255,255,255,.06);border-radius:50%;padding:6px;border:1.5px solid rgba(232,193,26,.2)}
        .card-tname{font-size:12px;font-weight:700;color:var(--white);text-transform:uppercase;letter-spacing:.4px;text-align:center}
        .card-vs{font-size:11px;font-weight:900;color:var(--muted);letter-spacing:2px;flex-shrink:0;padding:0 4px}
        .card-date{font-size:12px;color:var(--muted)}
        /* body */
        .card-body{padding:24px 22px 28px;text-align:center}
        .pick-label{font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:12px}
        .pick-value{font-size:24px;font-weight:900;color:var(--gold);background:rgba(232,193,26,.1);border:1.5px solid rgba(232,193,26,.38);border-radius:13px;padding:14px 20px;display:block;margin-bottom:18px;animation:pop .4s cubic-bezier(.34,1.56,.64,1)}
        @keyframes pop{from{transform:scale(.85);opacity:0}to{transform:scale(1);opacity:1}}
        .branding{font-size:12px;color:var(--muted)}
        .branding span{color:var(--gold);font-weight:700}
        /* actions */
        .actions{width:100%;max-width:420px;display:flex;flex-direction:column;gap:10px}
        .btn-share{display:flex;align-items:center;justify-content:center;gap:9px;padding:15px;background:linear-gradient(135deg,var(--gold),var(--gold-dk));color:var(--navy);font-size:15px;font-weight:800;border-radius:13px;border:none;cursor:pointer;transition:all .2s;box-shadow:0 4px 18px rgba(232,193,26,.32)}
        .btn-share:hover{transform:translateY(-2px);box-shadow:0 7px 24px rgba(232,193,26,.42)}
        .btn-outline{display:flex;align-items:center;justify-content:center;gap:8px;padding:14px;background:transparent;border:1.5px solid rgba(232,193,26,.32);color:var(--white);font-size:14px;font-weight:600;border-radius:13px;cursor:pointer;text-decoration:none;transition:all .2s}
        .btn-outline:hover{border-color:var(--gold);color:var(--gold);background:rgba(232,193,26,.06)}
        .btn-ghost{display:flex;align-items:center;justify-content:center;gap:7px;padding:12px;background:transparent;color:var(--muted);font-size:13px;font-weight:600;border-radius:13px;border:none;cursor:pointer;text-decoration:none;transition:color .2s}
        .btn-ghost:hover{color:var(--white)}
        .divider{border:none;border-top:1px solid rgba(255,255,255,.07);margin:2px 0}
        /* toast */
        .toast{position:fixed;bottom:30px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--navy-light);border:1px solid var(--border);color:var(--white);padding:11px 22px;border-radius:50px;font-size:13px;font-weight:600;transition:transform .3s;z-index:100;white-space:nowrap;box-shadow:0 4px 16px rgba(0,0,0,.3)}
        .toast.show{transform:translateX(-50%) translateY(0)}
    </style>
</head>
<body>
<div class="bg-stadium"></div>
<div class="corner tl"><img src="{{ asset('images/logo-offside.png') }}" alt="Offside Club"></div>
<div class="corner tr"><img src="{{ asset('images/2026_FIFA_World_Cup_emblem.svg.png') }}" alt="FIFA World Cup 2026"></div>

<div class="page">

    {{-- Share Card --}}
    <div class="share-card">
        <div class="card-head">
            <div class="card-wc">⚽ FIFA World Cup 2026</div>
            <div class="card-teams-row">
                <div class="card-team">
                    <img class="card-crest"
                         src="{{ $match->homeTeam?->crest_url ?? asset('images/default-crest.png') }}"
                         alt="{{ $match->home_team }}"
                         onerror="this.src='{{ asset('images/default-crest.png') }}'">
                    <div class="card-tname">{{ $match->home_team }}</div>
                </div>
                <div class="card-vs">VS</div>
                <div class="card-team">
                    <img class="card-crest"
                         src="{{ $match->awayTeam?->crest_url ?? asset('images/default-crest.png') }}"
                         alt="{{ $match->away_team }}"
                         onerror="this.src='{{ asset('images/default-crest.png') }}'">
                    <div class="card-tname">{{ $match->away_team }}</div>
                </div>
            </div>
            <div class="card-date">
                <i class="far fa-calendar-alt" style="margin-right:5px"></i>
                {{ \Carbon\Carbon::parse($match->date)->timezone(auth()->user()?->timezone ?? 'UTC')->isoFormat('D [de] MMMM · HH:mm') }}
            </div>
        </div>
        <div class="card-body">
            <div class="pick-label">🏅 Mi predicción</div>
            <div class="pick-value">{{ $votedOption }}</div>
            <div class="branding">Jugado en <span>Offside Club</span> · app.offsideclub.es</div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="actions">
        <button class="btn-share" onclick="shareResult()">
            <i class="fas fa-share-alt"></i> Compartir mi predicción
        </button>
        <hr class="divider">
        <a href="{{ route('wc.hoy') }}" class="btn-outline">
            <i class="fas fa-futbol"></i> Predecir más partidos
        </a>
        @if($wcGroup)
        <a href="{{ route('groups.show', $wcGroup->code) }}" class="btn-ghost">
            <i class="fas fa-trophy"></i> Ver ranking del Mundial
        </a>
        @endif
        <a href="{{ route('wc.match', $match->id) }}" class="btn-ghost">
            <i class="fas fa-arrow-left"></i> Volver al partido
        </a>
    </div>

</div>

<div class="toast" id="toast">✓ Copiado</div>

<script>
    const matchUrl = "{{ route('wc.match', $match->id) }}";
    const shareText = "⚽ Predije \u00ab{{ $votedOption }}\u00bb en {{ $match->home_team }} vs {{ $match->away_team }} \u2014 Mundial 2026.\n\u00bfY t\u00fa? Predice en Offside Club:";
    async function shareResult(){
        if(navigator.share){try{await navigator.share({title:'⚽ Mi predicci\u00f3n \u2014 Mundial 2026',text:shareText,url:matchUrl});return}catch(e){if(e.name==='AbortError')return}}
        copyLink();
    }
    function copyLink(){
        const t=shareText+'\n'+matchUrl;
        navigator.clipboard?.writeText(t).then(()=>showToast('✓ Texto copiado')).catch(()=>{const el=document.createElement('textarea');el.value=t;document.body.appendChild(el);el.select();document.execCommand('copy');document.body.removeChild(el);showToast('✓ Texto copiado')});
    }
    function showToast(m){const t=document.getElementById('toast');t.textContent=m;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2500);}
</script>
</body>
</html>
