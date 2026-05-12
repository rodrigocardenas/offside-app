<x-app-layout
    :logo-url="asset('images/logo_alone.png')"
    alt-text="Offside Club"
>
    @section('navigation-title', 'Resumen del Grupo: ' . $group->name)

    @php
        $themeMode = auth()->user()->theme_mode ?? 'light';
        $isDark = $themeMode === 'dark';

        // Colores dinámicos
        $bgPrimary = $isDark ? '#0a2e2c' : '#f5f5f5';
        $bgSecondary = $isDark ? '#0f3d3a' : '#f5f5f5';
        $bgTertiary = $isDark ? '#1a524e' : '#ffffff';
        $textPrimary = $isDark ? '#ffffff' : '#333333';
        $textSecondary = $isDark ? '#b0b0b0' : '#999999';
        $borderColor = $isDark ? '#2a4a47' : '#e0e0e0';
        $accentColor = '#00deb0';
        $accentDark = '#17b796';
    @endphp

    <div class="min-h-screen p-2 md:p-6 pb-24" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }}; margin-top: 3.75rem;">

        <!-- Header con imagen y botones -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <img src="{{ $group->getCoverImageUrl('small') }}"
                     alt="{{ $group->name }}"
                     style="width: 56px; height: 56px; border-radius: 12px; object-fit: cover; border: 2px solid {{ $accentColor }}; box-shadow: 0 4px 12px rgba(0, 222, 176, 0.2); flex-shrink: 0;">
                <div>
                    <h1 style="font-size: 18px; font-weight: 700; margin: 0; color: {{ $textPrimary }};">{{ $group->name }}</h1>
                    <p style="font-size: 12px; color: {{ $textSecondary }}; margin: 4px 0 0 0;">
                        @if($group->category === 'quiz') 🎮 Quiz @else ⚽ Predictivo @endif
                        @if($group->competition) · {{ $group->competition->name }} @endif
                    </p>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('groups.show', $group) }}"
                   style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border: 1px solid {{ $borderColor }}; border-radius: 10px; background: {{ $bgTertiary }}; color: {{ $textPrimary }}; text-decoration: none; font-weight: 600; font-size: 13px;">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                @if(auth()->user()->id === $group->created_by)
                <a href="{{ route('groups.edit', $group) }}"
                   style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; border: 1px solid {{ $accentColor }}; border-radius: 10px; background: {{ $isDark ? '#1a3a2a' : '#e5f9f4' }}; color: {{ $accentColor }}; text-decoration: none; font-weight: 600; font-size: 13px;">
                    <i class="fas fa-edit"></i> Editar
                </a>
                @endif
            </div>
        </div>

        <!-- KPIs principales: 2x2 en mobile, 4 columnas en desktop -->
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px;">

            <!-- Puntos totales -->
            <div style="background: {{ $bgTertiary }}; padding: 16px; border-radius: 12px; border-left: 4px solid {{ $accentColor }};">
                <div style="font-size: 11px; color: {{ $textSecondary }}; text-transform: uppercase; font-weight: 600; margin-bottom: 6px;">💰 Puntos</div>
                <div style="font-size: 26px; font-weight: 700; color: {{ $accentColor }}; line-height: 1.1;">{{ number_format($stats['total_points'] ?? 0, 0, ',', '.') }}</div>
                <div style="font-size: 11px; color: {{ $textSecondary }}; margin-top: 4px;">Prom: {{ number_format($stats['member_stats']['avg_points'], 0) }} pts</div>
            </div>

            <!-- Jugadores -->
            <div style="background: {{ $bgTertiary }}; padding: 16px; border-radius: 12px; border-left: 4px solid #ff6b6b;">
                <div style="font-size: 11px; color: {{ $textSecondary }}; text-transform: uppercase; font-weight: 600; margin-bottom: 6px;">👥 Jugadores</div>
                <div style="font-size: 26px; font-weight: 700; color: #ff6b6b; line-height: 1.1;">{{ $stats['member_count'] }}</div>
                <div style="font-size: 11px; color: {{ $textSecondary }}; margin-top: 4px;">Mejor: {{ number_format($stats['member_stats']['max_points'], 0) }} pts</div>
            </div>

            <!-- Tasa de aciertos -->
            <div style="background: {{ $bgTertiary }}; padding: 16px; border-radius: 12px; border-left: 4px solid #ffd93d;">
                <div style="font-size: 11px; color: {{ $textSecondary }}; text-transform: uppercase; font-weight: 600; margin-bottom: 6px;">🎯 Aciertos</div>
                <div style="font-size: 26px; font-weight: 700; color: #ffd93d; line-height: 1.1;">{{ $stats['accuracy_rate'] }}%</div>
                <div style="font-size: 11px; color: {{ $textSecondary }}; margin-top: 4px;">{{ $stats['correct_count'] }} de {{ $stats['answered_count'] }}</div>
            </div>

            <!-- Participación -->
            <div style="background: {{ $bgTertiary }}; padding: 16px; border-radius: 12px; border-left: 4px solid #17b796;">
                <div style="font-size: 11px; color: {{ $textSecondary }}; text-transform: uppercase; font-weight: 600; margin-bottom: 6px;">📋 Participación</div>
                <div style="font-size: 26px; font-weight: 700; color: #17b796; line-height: 1.1;">{{ $stats['participation_rate'] }}%</div>
                <div style="font-size: 11px; color: {{ $textSecondary }}; margin-top: 4px;">{{ $stats['question_count'] }} preguntas</div>
            </div>

        </div>

        <!-- Ranking -->
        <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border: 1px solid {{ $borderColor }}; margin-bottom: 20px;">
            <h2 style="font-size: 16px; font-weight: 700; margin: 0 0 14px 0; color: {{ $textPrimary }};">🏆 Ranking del Grupo</h2>

            @if($stats['top_members']->count() > 0)
            @php $maxPts = max($stats['member_stats']['max_points'], 1); @endphp
            <div style="display: flex; flex-direction: column; gap: 8px;">
                @foreach($stats['top_members'] as $index => $member)
                @php
                    $isFirst = $index === 0;
                    $medal = match(true) { $index === 0 => '🥇', $index === 1 => '🥈', $index === 2 => '🥉', default => null };
                    $barWidth = $maxPts > 0 ? round(($member->total_points / $maxPts) * 100) : 0;
                @endphp
                <div style="display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; background: {{ $isFirst ? ($isDark ? '#0d2e25' : '#e5f9f4') : $bgPrimary }}; border: 1px solid {{ $isFirst ? $accentColor : $borderColor }};">
                    <div style="width: 26px; text-align: center; font-weight: 700; font-size: 13px; flex-shrink: 0; color: {{ $accentColor }};">
                        {{ $medal ?? '#' . ($index + 1) }}
                    </div>
                    <img src="{{ $member->getAvatarUrl('small') }}" alt="{{ $member->name }}"
                         style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; border: 1px solid {{ $borderColor }};">
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 13px; font-weight: 600; color: {{ $textPrimary }}; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $member->name }}</div>
                        <div style="height: 3px; background: {{ $borderColor }}; border-radius: 2px; margin-top: 4px; overflow: hidden;">
                            <div style="height: 100%; width: {{ $barWidth }}%; background: {{ $isFirst ? $accentColor : '#17b796' }}; border-radius: 2px;"></div>
                        </div>
                    </div>
                    <div style="font-size: 14px; font-weight: 700; color: {{ $isFirst ? $accentColor : $textPrimary }}; flex-shrink: 0; min-width: 48px; text-align: right;">
                        {{ number_format($member->total_points, 0, ',', '.') }}
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p style="color: {{ $textSecondary }}; text-align: center; padding: 20px 0; font-size: 14px;">No hay miembros con puntos aún.</p>
            @endif
        </div>

        <!-- Info del grupo -->
        <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border: 1px solid {{ $borderColor }};">
            <h2 style="font-size: 16px; font-weight: 700; margin: 0 0 14px 0; color: {{ $textPrimary }};">ℹ️ Información del Grupo</h2>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                <div>
                    <div style="font-size: 11px; color: {{ $textSecondary }}; font-weight: 600; text-transform: uppercase;">Código</div>
                    <div style="margin-top: 4px; color: {{ $textPrimary }}; font-family: monospace; font-size: 14px; font-weight: 600;">{{ $group->code }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: {{ $textSecondary }}; font-weight: 600; text-transform: uppercase;">Creado por</div>
                    <div style="margin-top: 4px; color: {{ $textPrimary }}; font-weight: 500; font-size: 14px;">{{ $group->creator->name ?? 'N/A' }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: {{ $textSecondary }}; font-weight: 600; text-transform: uppercase;">Creado el</div>
                    <div style="margin-top: 4px; color: {{ $textPrimary }}; font-weight: 500; font-size: 14px;">{{ $group->created_at->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div style="font-size: 11px; color: {{ $textSecondary }}; font-weight: 600; text-transform: uppercase;">Última actualización</div>
                    <div style="margin-top: 4px; color: {{ $textPrimary }}; font-weight: 500; font-size: 14px;">{{ $group->total_points_updated_at?->diffForHumans() ?? 'Pendiente' }}</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Menú inferior fijo -->
    <x-layout.bottom-navigation active-item="grupo" />

</x-app-layout>
