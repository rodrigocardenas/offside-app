<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>⚽ {{ $match->home_team }} vs {{ $match->away_team }} · Mundial 2026</title>

    {{-- OG Meta Tags --}}
    <meta property="og:title" content="⚽ {{ $match->home_team }} vs {{ $match->away_team }} · Predice ahora">
    <meta property="og:description" content="¿Quién ganará? Predice el resultado y compite en Offside Club.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('images/wc2026-og.jpg') }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --gold:      #e8c11a;
            --gold-dark: #c5a215;
            --navy:      #0b1e3a;
            --navy-mid:  #102545;
            --navy-light:#162e52;
            --white:     #ffffff;
            --text-muted:#b0bec5;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--navy);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Hero ───────────────────────────────────── */
        .hero {
            position: relative;
            min-height: 55vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 20px 32px;
            background:
                linear-gradient(to bottom, rgba(11,30,58,.85) 0%, rgba(11,30,58,.97) 100%),
                url('{{ asset("images/wc2026-bg.jpg") }}') center/cover no-repeat;
            text-align: center;
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
            margin-bottom: 24px;
        }

        .match-stage {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 4px;
            letter-spacing: .5px;
        }

        .match-date {
            font-size: 15px;
            color: var(--gold);
            font-weight: 600;
            margin-bottom: 32px;
        }

        .teams-row {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 8px;
        }

        .team-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            min-width: 110px;
        }

        .team-flag {
            font-size: 48px;
            line-height: 1;
        }

        .team-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--white);
            text-align: center;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .vs-label {
            font-size: 22px;
            font-weight: 900;
            color: var(--text-muted);
            flex-shrink: 0;
        }

        /* ── Card de acción ─────────────────────────── */
        .action-card {
            flex: 1;
            background: var(--navy-mid);
            border-radius: 24px 24px 0 0;
            padding: 32px 20px 40px;
            margin-top: -16px;
            max-width: 480px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
            text-align: center;
            margin-bottom: 20px;
        }

        /* ── Botones de voto ─────────────────────────── */
        .vote-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .vote-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            background: var(--navy-light);
            border: 2px solid rgba(232,193,26,.2);
            border-radius: 14px;
            color: var(--white);
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            width: 100%;
            text-align: left;
        }

        .vote-btn:hover {
            background: rgba(232,193,26,.12);
            border-color: var(--gold);
            color: var(--gold);
            transform: translateX(4px);
        }

        .vote-btn.selected {
            background: rgba(232,193,26,.18);
            border-color: var(--gold);
            color: var(--gold);
        }

        .vote-btn .icon {
            font-size: 20px;
        }

        /* ── Auth inline ─────────────────────────────── */
        .auth-teaser {
            text-align: center;
        }

        .cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 18px 36px;
            background: var(--gold);
            color: var(--navy);
            font-size: 17px;
            font-weight: 800;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            transition: all .2s;
            width: 100%;
            justify-content: center;
        }

        .cta-btn:hover { background: var(--gold-dark); transform: scale(1.02); }

        .auth-hint {
            margin-top: 12px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .auth-form-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 4px;
        }

        .auth-form-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .input-username {
            padding: 15px 18px;
            background: var(--navy-light);
            border: 2px solid rgba(232,193,26,.3);
            border-radius: 12px;
            color: var(--white);
            font-size: 16px;
            outline: none;
            width: 100%;
            transition: border-color .2s;
        }

        .input-username:focus {
            border-color: var(--gold);
        }

        .input-username::placeholder { color: var(--text-muted); }

        .submit-btn {
            padding: 16px;
            background: var(--gold);
            color: var(--navy);
            font-size: 16px;
            font-weight: 800;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: background .2s;
        }

        .submit-btn:hover { background: var(--gold-dark); }

        .back-link {
            text-align: center;
            margin-top: 8px;
        }

        .back-link a {
            font-size: 13px;
            color: var(--text-muted);
            text-decoration: none;
        }

        /* ── Ya votó ─────────────────────────────────── */
        .voted-badge {
            background: rgba(232,193,26,.12);
            border: 2px solid rgba(232,193,26,.4);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .voted-label {
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .voted-value {
            font-size: 20px;
            font-weight: 800;
            color: var(--gold);
        }

        .share-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 15px;
            background: transparent;
            border: 2px solid var(--gold);
            color: var(--gold);
            font-size: 15px;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: all .2s;
            width: 100%;
            text-decoration: none;
        }

        .share-btn:hover { background: rgba(232,193,26,.1); }

        /* ── Footer branding ─────────────────────────── */
        .wc-footer {
            text-align: center;
            padding: 16px 20px 32px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .wc-footer a { color: var(--gold); text-decoration: none; }

        /* ── Partido terminado ───────────────────────── */
        .match-closed {
            background: rgba(255,255,255,.06);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            color: var(--text-muted);
            font-size: 15px;
        }

        @media (min-width: 480px) {
            .action-card { padding: 40px 40px 48px; }
        }
    </style>
</head>
<body>

    {{-- ── Hero ── --}}
    <div class="hero">
        <div class="wc-badge">
            <i class="fas fa-globe-americas"></i>
            FIFA World Cup 2026
        </div>

        <div class="match-stage">
            {{ $match->stage ?? 'Fase de grupos' }}
            @if($match->group) · {{ str_replace('GROUP_', 'Grupo ', $match->group) }}@endif
        </div>

        <div class="match-date">
            <i class="far fa-calendar-alt" style="margin-right:6px"></i>
            {{ \Carbon\Carbon::parse($match->date)->timezone(auth()->user()?->timezone ?? 'UTC')->format('d M Y · H:i') }}
        </div>

        <div class="teams-row">
            <div class="team-block">
                <div class="team-flag">{{ $match->home_team_flag ?? '🏳️' }}</div>
                <div class="team-name">{{ $match->home_team }}</div>
            </div>
            <div class="vs-label">VS</div>
            <div class="team-block">
                <div class="team-flag">{{ $match->away_team_flag ?? '🏳️' }}</div>
                <div class="team-name">{{ $match->away_team }}</div>
            </div>
        </div>
    </div>

    {{-- ── Action card ── --}}
    <div class="action-card">

        @if(session('error'))
            <div style="background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.4);color:#fca5a5;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:14px;">
                <i class="fas fa-exclamation-circle" style="margin-right:6px"></i>{{ session('error') }}
            </div>
        @endif

        @php $matchStarted = $match->date->lte(now()); @endphp

        @if($matchStarted && !$userAnswer)
            {{-- Partido ya comenzó y no votó --}}
            <div class="match-closed">
                <i class="fas fa-lock" style="font-size:32px;margin-bottom:12px;display:block;color:#e8c11a"></i>
                <strong style="color:#fff;display:block;margin-bottom:8px;">Este partido ya comenzó</strong>
                Las predicciones estaban abiertas antes del pitido inicial.
                <div style="margin-top:20px">
                    <a href="{{ route('wc.hoy') }}" style="color:#e8c11a;font-weight:700;text-decoration:none;">
                        ⚽ Ver otros partidos
                    </a>
                </div>
            </div>

        @elseif($userAnswer)
            {{-- Ya votó --}}
            <div class="voted-badge">
                <div class="voted-label">Tu predicción</div>
                <div class="voted-value">{{ $userAnswer->questionOption->text }}</div>
            </div>

            <a href="{{ route('wc.resultado', $match->id) }}" class="share-btn">
                <i class="fas fa-share-alt"></i> Compartir tu predicción
            </a>

            <div style="text-align:center;margin-top:16px">
                <a href="{{ route('wc.hoy') }}" style="font-size:14px;color:var(--text-muted);text-decoration:none;">
                    ⚽ Predecir más partidos
                </a>
            </div>

        @elseif(auth()->check() && $question)
            {{-- Autenticado, no ha votado --}}
            <div class="section-title">¿Quién ganará?</div>

            <form action="{{ route('wc.votar', $match->id) }}" method="POST" class="vote-options" id="voteForm">
                @csrf
                @foreach($question->options as $option)
                    <button type="submit" name="question_option_id" value="{{ $option->id }}" class="vote-btn"
                            onclick="document.getElementById('voteForm').submit()">
                        <span>{{ $option->text }}</span>
                        <span class="icon">→</span>
                    </button>
                @endforeach
            </form>

        @else
            {{-- Guest — CTA + formulario inline con Alpine.js --}}
            <div x-data="{ showForm: false }">

                <div x-show="!showForm" class="auth-teaser">
                    <div class="section-title" style="margin-bottom:10px;">¿Quién ganará?</div>
                    <p style="color:var(--text-muted);font-size:14px;margin-bottom:24px;">
                        Únete gratis y predice el resultado. Solo necesitas un nickname.
                    </p>
                    <button @click="showForm = true; $nextTick(() => $refs.nameInput.focus())" class="cta-btn">
                        <i class="fas fa-futbol"></i>
                        Predice el resultado
                    </button>
                    <p class="auth-hint">Gratis · Sin contraseña · Solo un nickname</p>
                </div>

                <div x-show="showForm" x-transition>
                    <form action="{{ route('wc.auth', $match->id) }}" method="POST" class="auth-form">
                        @csrf
                        <input type="hidden" name="timezone" id="timezoneInput" value="UTC">

                        <div class="auth-form-title">¿Cómo te llaman?</div>
                        <div class="auth-form-sub">Ingresa tu nickname para votar y guardar tu predicción.</div>

                        <input
                            type="text"
                            name="name"
                            x-ref="nameInput"
                            class="input-username"
                            placeholder="Tu nickname (ej: RodriGoal10)"
                            maxlength="30"
                            autocomplete="off"
                            required
                        >

                        @error('name')
                            <span style="color:#fca5a5;font-size:13px;">{{ $message }}</span>
                        @enderror

                        <button type="submit" class="submit-btn">
                            <i class="fas fa-futbol" style="margin-right:8px"></i>Entrar y predecir
                        </button>

                        <div class="back-link">
                            <a href="#" @click.prevent="showForm = false">← Volver</a>
                        </div>
                    </form>
                </div>

            </div>
        @endif

    </div>

    {{-- ── Footer ── --}}
    <div class="wc-footer">
        <a href="{{ route('wc.hoy') }}">⚽ Ver todos los partidos de hoy</a>
        &nbsp;·&nbsp;
        <a href="{{ config('app.url') }}">Offside Club</a>
    </div>

    <script>
        // Capturar timezone del navegador
        document.addEventListener('DOMContentLoaded', function () {
            const tz = document.getElementById('timezoneInput');
            if (tz) {
                try { tz.value = Intl.DateTimeFormat().resolvedOptions().timeZone; } catch(e) {}
            }
        });
    </script>
</body>
</html>
