@php
    // Detectar tema: por defecto light (sin usuario autenticado)
    $isDark = false;

    // Colores basados en groups/index
    $bgPrimary = $isDark ? '#0a2e2c' : '#f5f5f5';
    $bgSecondary = $isDark ? '#0f3d3a' : '#ffffff';
    $textPrimary = $isDark ? '#ffffff' : '#333333';
    $textSecondary = $isDark ? '#b0b0b0' : '#666666';
    $accentColor = '#00deb0';
    $accentDark = '#00b890';
    $borderColor = $isDark ? '#1a524e' : '#e0e0e0';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.errors.503.title') }} - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Figtree', sans-serif;
            background: {{ $bgPrimary }};
            color: {{ $textPrimary }};
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            width: 100%;
        }
        .card {
            background: {{ $bgSecondary }};
            border-radius: 16px;
            padding: 48px 32px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            color: {{ $accentColor }};
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: {{ $accentColor }};
            margin-bottom: 16px;
            line-height: 1;
        }
        h1 {
            font-size: 28px;
            font-weight: 600;
            color: {{ $textPrimary }};
            margin-bottom: 16px;
        }
        p {
            font-size: 16px;
            color: {{ $textSecondary }};
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            background: {{ $accentColor }};
            color: #ffffff;
        }
        .btn:hover {
            background: {{ $accentDark }};
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 222, 176, 0.3);
        }
        svg {
            width: 20px;
            height: 20px;
        }
        @media (max-width: 640px) {
            .card {
                padding: 32px 24px;
            }
            .error-code {
                font-size: 56px;
            }
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <!-- Icono engranaje -->
            <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>

            <!-- Error 503 -->
            <div class="error-code">503</div>

            <!-- Mensaje principal -->
            <h1>{{ __('messages.errors.503.title') }}</h1>

            <p>
                {{ __('messages.errors.503.message') }}
                <br>{{ __('messages.errors.503.submessage') }}
            </p>

            <!-- Botón de acción -->
            <button onclick="window.location.reload()" class="btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                {{ __('messages.errors.503.reload') }}
            </button>
        </div>
    </div>
</body>
</html>
