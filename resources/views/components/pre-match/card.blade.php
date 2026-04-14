<!-- Pre Match Card Component -->
@php
    // Determinar gradient según estado
    $headerGradient = 'linear-gradient(135deg, #ff6b6b, #ff8787)'; // pending - rojo
    $statusIcon = '⏳';
    $statusLabel = 'Pendiente';

    if ($preMatch->status === 'active') {
        $headerGradient = 'linear-gradient(135deg, #ffa726, #ffb74d)'; // active - naranja
        $statusIcon = '🔴';
        $statusLabel = 'Activo';
    } elseif ($preMatch->status === 'completed') {
        $headerGradient = 'linear-gradient(135deg, #66bb6a, #81c784)'; // completed - verde
        $statusIcon = '✅';
        $statusLabel = 'Completado';
    } elseif ($preMatch->status === 'cancelled') {
        $headerGradient = 'linear-gradient(135deg, #bdbdbd, #9e9e9e)'; // cancelled - gris
        $statusIcon = '✕';
        $statusLabel = 'Cancelado';
    }
@endphp

<div style="background: {{ $bgTertiary }}; border-radius: 12px; border: 1px solid {{ $borderColor }}; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: all 0.3s ease; cursor: pointer;"
     onclick="window.location.href = '/groups/{{ $preMatch->group_id }}/pre-matches/{{ $preMatch->id }}'"
     onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.2)'; this.style.transform='translateY(-2px)'"
     onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'; this.style.transform='translateY(0)'">

    <!-- Header with Gradient -->
    <div style="background: {{ $headerGradient }}; padding: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: start; gap: 12px;">
            <div style="flex: 1; min-width: 0;">
                <span style="color: #fff; font-weight: 700; font-size: 12px; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="font-size: 16px;">🔥</span> PRE MATCH
                </span>
                <h3 style="color: #fff; font-weight: 700; font-size: 16px; margin: 8px 0; word-break: break-word;">
                    {{ $match->home_team ?? 'Home' }} vs {{ $match->away_team ?? 'Away' }}
                </h3>
                <p style="color: rgba(255,255,255,0.9); font-size: 12px; margin: 4px 0 0 0;">
                    ⏰ {{ $match->date?->format('d/m H:i') ?? 'TBD' }}
                </p>
            </div>
            <span style="display: inline-block; padding: 6px 10px; background: rgba(255,255,255,0.2); color: #fff; font-size: 11px; border-radius: 20px; font-weight: 700; white-space: nowrap; flex-shrink: 0;">
                {{ $statusIcon }} {{ $statusLabel }}
            </span>
        </div>
    </div>

    <!-- Content -->
    <div style="padding: 20px;">

        <!-- Penalty Badge -->
        <div style="padding: 12px; border-radius: 8px; margin-bottom: 16px;
            @if($preMatch->penalty_type === 'POINTS')
                background: {{ $redLight }}; border-left: 4px solid {{ $redAccent }};
            @else
                background: rgba(255, 149, 0, 0.1); border-left: 4px solid {{ $orangeAccent }};
            @endif
        ">
            @if($preMatch->penalty_type === 'POINTS')
                <p style="color: {{ $redAccent }}; font-weight: 700; margin: 0;">
                    💔 CASTIGO: Restar {{ $preMatch->penalty_points }} puntos
                </p>
            @else
                <p style="color: {{ $orangeAccent }}; font-weight: 700; margin: 0;">
                    📝 CASTIGO: {{ $preMatch->penalty_description }}
                </p>
            @endif
        </div>

        <!-- Propositions Progress (si las hay) -->
        @if($preMatch->propositions->count() > 0)
        <div style="margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <p style="font-size: 12px; font-weight: 700; color: {{ $textPrimary }}; margin: 0;">
                    {{ $preMatch->propositions->count() }} propuestas
                </p>
                <span style="font-size: 11px; font-weight: 700; color: #4CAF50;">
                    ✅ {{ $preMatch->propositions->where('validation_status', 'approved')->count() }} aceptadas
                </span>
            </div>
            <div style="width: 100%; height: 6px; background: {{ $borderColor }}; border-radius: 3px; overflow: hidden;">
                <div style="height: 100%; background: #4CAF50; transition: all 0.3s ease;
                    width: {{ $preMatch->propositions->count() > 0 ? ($preMatch->propositions->where('validation_status', 'approved')->count() / $preMatch->propositions->count() * 100) : 0 }}%;">
                </div>
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div style="display: flex; gap: 8px; margin-top: 16px;">
            @if($preMatch->status === 'active' || $preMatch->status === 'pending')
                <button onclick="event.stopPropagation(); window.location.href = '/groups/{{ $preMatch->group_id }}/pre-matches/{{ $preMatch->id }}'"
                        style="flex: 1; padding: 10px; border: none; border-radius: 6px; background: {{ $accentColor }}; color: #003b2f; font-weight: 700; font-size: 12px; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.backgroundColor='{{ $accentDark }}'"
                        onmouseout="this.style.backgroundColor='{{ $accentColor }}';">
                     Ver Detalles
                </button>
            @elseif($preMatch->status === 'completed')
                <button onclick="event.stopPropagation(); window.location.href = '/groups/{{ $preMatch->group_id }}/pre-matches/{{ $preMatch->id }}'"
                        style="width: 100%; padding: 10px; border: none; border-radius: 6px; background: #4CAF50; color: #fff; font-weight: 700; font-size: 12px; cursor: pointer; transition: all 0.2s ease;"
                        onmouseover="this.style.opacity='0.8'"
                        onmouseout="this.style.opacity='1';">
                    ✅ Ver Resultados
                </button>
            @endif
        </div>

    </div>

</div>
