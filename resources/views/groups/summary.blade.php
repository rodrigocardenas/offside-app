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

    <div class="min-h-screen p-1 md:p-6 pb-24" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }}; margin-top: 3.75rem;">

        <!-- Header con Botones de Acción -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; gap: 12px; flex-wrap: wrap;">
            <div>
                <h1 style="font-size: 28px; font-weight: 700; margin: 0; color: {{ $textPrimary }};">
                    📊 {{ $group->name }}
                </h1>
                <p style="font-size: 14px; color: {{ $textSecondary }}; margin: 8px 0 0 0;">
                    Resumen de actividad y estadísticas
                </p>
            </div>

            <div style="display: flex; gap: 8px; align-items: center;">
                <!-- Botón Volver -->
                <a href="{{ route('groups.show', $group) }}"
                   style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border: 1px solid {{ $borderColor }}; border-radius: 12px; background: {{ $bgTertiary }}; color: {{ $textPrimary }}; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s ease; cursor: pointer;"
                   onmouseover="this.style.background='{{ $isDark ? '#2a4a47' : '#f0f0f0' }}';"
                   onmouseout="this.style.background='{{ $bgTertiary }}';">
                    <i class="fas fa-arrow-left"></i>
                    <span>Volver</span>
                </a>

                <!-- Botón Editar (solo creador) -->
                @if (auth()->user()->id === $group->created_by)
                <a href="{{ route('groups.edit', $group) }}"
                   style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border: 1px solid {{ $accentColor }}; border-radius: 12px; background: {{ $isDark ? '#1a524e' : '#e5f3f0' }}; color: {{ $accentColor }}; text-decoration: none; font-weight: 600; font-size: 14px; transition: all 0.2s ease; cursor: pointer;"
                   onmouseover="this.style.background='{{ $accentColor }}'; this.style.color='#003b2f';"
                   onmouseout="this.style.background='{{ $isDark ? '#1a524e' : '#e5f3f0' }}'; this.style.color='{{ $accentColor }}';">
                    <i class="fas fa-edit"></i>
                    <span>Editar</span>
                </a>
                @endif
            </div>
        </div>

        <!-- Estadísticas Principales (4 Columnas) -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">

            <!-- Total de Puntos -->
            <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border-left: 4px solid {{ $accentColor }};">
                <div style="font-size: 12px; color: {{ $textSecondary }}; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">
                    💰 Total de Puntos
                </div>
                <div style="font-size: 32px; font-weight: 700; color: {{ $accentColor }};">
                    {{ number_format($stats['total_points'], 0, ',', '.') }}
                </div>
                <div style="font-size: 12px; color: {{ $textSecondary }}; margin-top: 8px;">
                    Actualizado: {{ $group->total_points_updated_at?->diffForHumans() ?? 'Nunca' }}
                </div>
            </div>

            <!-- Integrantes -->
            <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border-left: 4px solid #ff6b6b;">
                <div style="font-size: 12px; color: {{ $textSecondary }}; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">
                    👥 Integrantes
                </div>
                <div style="font-size: 32px; font-weight: 700; color: #ff6b6b;">
                    {{ $stats['member_count'] }}
                </div>
                <div style="font-size: 12px; color: {{ $textSecondary }}; margin-top: 8px;">
                    Promedio: {{ number_format($stats['member_stats']['avg_points'], 0) }} pts
                </div>
            </div>

            <!-- Preguntas -->
            <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border-left: 4px solid #ffd93d;">
                <div style="font-size: 12px; color: {{ $textSecondary }}; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">
                    ❓ Preguntas
                </div>
                <div style="font-size: 32px; font-weight: 700; color: #ffd93d;">
                    {{ $stats['question_count'] }}
                </div>
                <div style="font-size: 12px; color: {{ $textSecondary }}; margin-top: 8px;">
                    Respondidas: {{ $stats['answered_count'] }}
                </div>
            </div>

            <!-- Mensajes Chat -->
            <div style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 12px; border-left: 4px solid #17b796;">
                <div style="font-size: 12px; color: {{ $textSecondary }}; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">
                    💬 Mensajes
                </div>
                <div style="font-size: 32px; font-weight: 700; color: #17b796;">
                    {{ $stats['message_count'] }}
                </div>
                <div style="font-size: 12px; color: {{ $textSecondary }}; margin-top: 8px;">
                    Conversaciones activas
                </div>
            </div>

        </div>

        <!-- Grid de 2 columnas: Top Members + Statistics -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">

            <!-- Top 10 Miembros -->
            <div style="background: {{ $bgTertiary }}; padding: 24px; border-radius: 12px; border: 1px solid {{ $borderColor }};">
                <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px 0; color: {{ $textPrimary }}; display: flex; align-items: center; gap: 8px;">
                    🏆 Top 10 Miembros
                </h2>

                @if($stats['top_members']->count() > 0)
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid {{ $borderColor }};">
                                <th style="text-align: left; padding: 12px 0; font-size: 12px; color: {{ $textSecondary }}; font-weight: 600;">Pos</th>
                                <th style="text-align: left; padding: 12px 8px; font-size: 12px; color: {{ $textSecondary }}; font-weight: 600;">Miembro</th>
                                <th style="text-align: right; padding: 12px 0; font-size: 12px; color: {{ $textSecondary }}; font-weight: 600;">Puntos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($stats['top_members'] as $index => $member)
                            <tr style="border-bottom: 1px solid {{ $isDark ? '#2a4a47' : '#f0f0f0' }};">
                                <td style="padding: 12px 0; text-align: center; font-weight: 700; color: {{ $accentColor }};">
                                    @if($index === 0)
                                        🥇
                                    @elseif($index === 1)
                                        🥈
                                    @elseif($index === 2)
                                        🥉
                                    @else
                                        #{{ $index + 1 }}
                                    @endif
                                </td>
                                <td style="padding: 12px 8px; display: flex; align-items: center; gap: 8px;">
                                    <img src="{{ $member->getAvatarUrl('small') }}" alt="{{ $member->name }}"
                                         style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                    <span style="color: {{ $textPrimary }}; font-weight: 500;">{{ $member->name }}</span>
                                </td>
                                <td style="padding: 12px 0; text-align: right; color: {{ $accentColor }}; font-weight: 700;">
                                    {{ number_format($member->total_points, 0, ',', '.') }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p style="color: {{ $textSecondary }}; text-align: center; padding: 20px;">
                    No hay miembros en este grupo
                </p>
                @endif
            </div>

            <!-- Estadísticas Detalladas -->
            <div style="background: {{ $bgTertiary }}; padding: 24px; border-radius: 12px; border: 1px solid {{ $borderColor }};">
                <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px 0; color: {{ $textPrimary }}; display: flex; align-items: center; gap: 8px;">
                    📈 Estadísticas
                </h2>

                <div style="display: flex; flex-direction: column; gap: 12px;">

                    <!-- Promedio -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px;">
                        <span style="color: {{ $textSecondary }}; font-size: 14px;">Promedio</span>
                        <span style="color: {{ $accentColor }}; font-weight: 700; font-size: 16px;">
                            {{ number_format($stats['member_stats']['avg_points'], 0) }}
                        </span>
                    </div>

                    <!-- Máximo -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px;">
                        <span style="color: {{ $textSecondary }}; font-size: 14px;">⬆️ Máximo</span>
                        <span style="color: #ffd93d; font-weight: 700; font-size: 16px;">
                            {{ number_format($stats['member_stats']['max_points'], 0) }}
                        </span>
                    </div>

                    <!-- Mínimo -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px;">
                        <span style="color: {{ $textSecondary }}; font-size: 14px;">⬇️ Mínimo</span>
                        <span style="color: #ff6b6b; font-weight: 700; font-size: 16px;">
                            {{ number_format($stats['member_stats']['min_points'], 0) }}
                        </span>
                    </div>

                    <!-- Mediana -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px;">
                        <span style="color: {{ $textSecondary }}; font-size: 14px;">📊 Mediana</span>
                        <span style="color: {{ $accentColor }}; font-weight: 700; font-size: 16px;">
                            {{ number_format($stats['member_stats']['median_points'], 0) }}
                        </span>
                    </div>

                    <!-- Desviación Estándar -->
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px;">
                        <span style="color: {{ $textSecondary }}; font-size: 14px;">📉 Desv. Estándar</span>
                        <span style="color: #17b796; font-weight: 700; font-size: 16px;">
                            {{ number_format($stats['member_stats']['std_dev_points'], 0) }}
                        </span>
                    </div>

                </div>

                <!-- Info -->
                <p style="font-size: 12px; color: {{ $textSecondary }}; margin-top: 16px; padding-top: 16px; border-top: 1px solid {{ $borderColor }};">
                    ℹ️ Estas estadísticas se actualizan cada hora automáticamente.
                </p>
            </div>

        </div>

        <!-- Información del Grupo -->
        <div style="background: {{ $bgTertiary }}; padding: 24px; border-radius: 12px; border: 1px solid {{ $borderColor }}; margin-bottom: 24px;">
            <h2 style="font-size: 18px; font-weight: 700; margin: 0 0 16px 0; color: {{ $textPrimary }}; display: flex; align-items: center; gap: 8px;">
                ℹ️ Información del Grupo
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">

                <!-- Código -->
                <div>
                    <label style="font-size: 12px; color: {{ $textSecondary }}; font-weight: 600;">Código del Grupo</label>
                    <p style="margin: 8px 0 0 0; color: {{ $textPrimary }}; font-family: monospace; font-size: 14px; font-weight: 600;">
                        {{ $group->code }}
                    </p>
                </div>

                <!-- Creador -->
                <div>
                    <label style="font-size: 12px; color: {{ $textSecondary }}; font-weight: 600;">Creado por</label>
                    <p style="margin: 8px 0 0 0; color: {{ $textPrimary }}; font-weight: 500;">
                        {{ $group->creator->name ?? 'N/A' }}
                    </p>
                </div>

                <!-- Fecha de Creación -->
                <div>
                    <label style="font-size: 12px; color: {{ $textSecondary }}; font-weight: 600;">Fecha de Creación</label>
                    <p style="margin: 8px 0 0 0; color: {{ $textPrimary }}; font-weight: 500;">
                        {{ $group->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>

                <!-- Última Actualización de Puntos -->
                <div>
                    <label style="font-size: 12px; color: {{ $textSecondary }}; font-weight: 600;">Última Actualización</label>
                    <p style="margin: 8px 0 0 0; color: {{ $textPrimary }}; font-weight: 500;">
                        {{ $group->total_points_updated_at?->format('d/m/Y H:i') ?? 'Pendiente' }}
                    </p>
                </div>

                <!-- Categoría -->
                <div>
                    <label style="font-size: 12px; color: {{ $textSecondary }}; font-weight: 600;">Categoría</label>
                    <p style="margin: 8px 0 0 0; color: {{ $textPrimary }}; font-weight: 500;">
                        @if($group->category === 'quiz')
                            🎮 Quiz
                        @else
                            ⚽ Predictivo
                        @endif
                    </p>
                </div>

                <!-- Competición -->
                <div>
                    <label style="font-size: 12px; color: {{ $textSecondary }}; font-weight: 600;">Competición</label>
                    <p style="margin: 8px 0 0 0; color: {{ $textPrimary }}; font-weight: 500;">
                        {{ $group->competition?->name ?? 'Ninguna' }}
                    </p>
                </div>

            </div>
        </div>

    </div>

    <!-- Menú inferior fijo -->
    <x-layout.bottom-navigation active-item="grupo" />

</x-app-layout>

<style>
    @media (max-width: 768px) {
        div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }

        div[style*="grid-template-columns: repeat(auto-fit"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>
