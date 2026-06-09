<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>⚽ Mi predicción · {{ $match->home_team }} vs {{ $match->away_team }}</title>

    {{-- OG --}}
    <meta property="og:title" content="⚽ Predije {{ $votedOption }} en {{ $match->home_team }} vs {{ $match->away_team }}">
    <meta property="og:description" content="Juega en Offside Club y predice los partidos del Mundial 2026.">
    <meta property="og:url" content="{{ url()->current() }}">
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
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px 60px;
        }

        /* ── Share card (también sirve como imagen mental) ── */
        .share-card {
            width: 100%;
            max-width: 420px;
            background: var(--navy-mid);
            border: 2px solid rgba(232,193,26,.4);
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 32px;
        }

        .card-header {
            background: linear-gradient(135deg, #0b1e3a 0%, #162e52 100%);
            padding: 24px 24px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(232,193,26,.2);
        }

        .card-wc-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--gold);
            margin-bottom: 12px;
        }

        .card-teams {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .card-team {
            font-size: 15px;
            font-weight: 800;
            color: var(--white);
            text-transform: uppercase;
        }

        .card-vs {
            font-size: 13px;
            color: var(--muted);
            font-weight: 700;
        }

        .card-date {
            margin-top: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .card-body {
            padding: 28px 24px 32px;
            text-align: center;
        }

        .my-pick-label {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 16px;
        }

        .my-pick-value {
            font-size: 26px;
            font-weight: 900;
            color: var(--gold);
            background: rgba(232,193,26,.12);
            border: 2px solid rgba(232,193,26,.4);
            border-radius: 14px;
            padding: 16px 24px;
            display: inline-block;
            width: 100%;
            margin-bottom: 20px;
        }

        .card-branding {
            font-size: 12px;
            color: var(--muted);
        }

        .card-branding span { color: var(--gold); font-weight: 700; }

        /* ── Botones de acción ── */
        .actions {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 16px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-share {
            background: var(--gold);
            color: var(--navy);
        }
        .btn-share:hover { background: var(--gold-dark); }

        .btn-outline {
            background: transparent;
            border: 2px solid rgba(232,193,26,.4);
            color: var(--white);
        }
        .btn-outline:hover { border-color: var(--gold); color: var(--gold); }

        .btn-ghost {
            background: transparent;
            color: var(--muted);
            font-size: 14px;
        }
        .btn-ghost:hover { color: var(--white); }

        .divider {
            border: none;
            border-top: 1px solid rgba(255,255,255,.08);
            margin: 4px 0;
        }

        .toast {
            position: fixed;
            bottom: 32px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: #1e3e6a;
            color: var(--white);
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            transition: transform .3s;
            z-index: 100;
            white-space: nowrap;
        }

        .toast.show { transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body>

    {{-- ── Share Card ── --}}
    <div class="share-card" id="shareCard">
        <div class="card-header">
            <div class="card-wc-label">⚽ FIFA World Cup 2026</div>
            <div class="card-teams">
                <div class="card-team">{{ $match->home_team }}</div>
                <div class="card-vs">vs</div>
                <div class="card-team">{{ $match->away_team }}</div>
            </div>
            <div class="card-date">
                {{ \Carbon\Carbon::parse($match->date)->timezone(auth()->user()?->timezone ?? 'UTC')->format('d M Y · H:i') }}
            </div>
        </div>

        <div class="card-body">
            <div class="my-pick-label">🏅 Mi predicción</div>
            <div class="my-pick-value">{{ $votedOption }}</div>
            <div class="card-branding">
                Jugado en <span>Offside Club</span> · offsideclub.es
            </div>
        </div>
    </div>

    {{-- ── Acciones ── --}}
    <div class="actions">

        {{-- Compartir nativo (mobile) con fallback --}}
        <button class="btn btn-share" id="shareBtn" onclick="shareResult()">
            <i class="fas fa-share-alt"></i>
            Compartir mi predicción
        </button>

        <hr class="divider">

        <a href="{{ route('wc.hoy') }}" class="btn btn-outline">
            <i class="fas fa-futbol"></i>
            Predecir más partidos
        </a>

        @if($wcGroup)
        <a href="{{ route('groups.show', $wcGroup->code) }}" class="btn btn-ghost">
            <i class="fas fa-trophy"></i>
            Ver ranking del Mundial
        </a>
        @endif

        <a href="{{ route('wc.match', $match->id) }}" class="btn btn-ghost">
            ← Volver al partido
        </a>

    </div>

    <div class="toast" id="toast">✓ Enlace copiado</div>

    <script>
        const matchUrl   = "{{ route('wc.match', $match->id) }}";
        const shareText  = "⚽ Predije «{{ $votedOption }}» en {{ $match->home_team }} vs {{ $match->away_team }} — Mundial 2026.\n¿Y tú? Predice en Offside Club:";

        async function shareResult() {
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: '⚽ Mi predicción — Mundial 2026',
                        text: shareText,
                        url: matchUrl,
                    });
                    return;
                } catch (e) {
                    if (e.name === 'AbortError') return;
                }
            }
            // Fallback: copiar al portapapeles
            copyLink();
        }

        function copyLink() {
            const text = shareText + '\n' + matchUrl;
            navigator.clipboard?.writeText(text).then(() => showToast('✓ Texto copiado'))
                .catch(() => {
                    // Fallback para iOS WebView
                    const el = document.createElement('textarea');
                    el.value = text;
                    document.body.appendChild(el);
                    el.select();
                    document.execCommand('copy');
                    document.body.removeChild(el);
                    showToast('✓ Texto copiado');
                });
        }

        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 2500);
        }
    </script>
</body>
</html>
