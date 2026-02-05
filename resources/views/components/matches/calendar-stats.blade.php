@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
    
    $bgColor = $isDark ? '#1a524e' : '#f9f9f9';
    $borderColor = $isDark ? '#2d7a77' : '#e0e0e0';
    $textColor = $isDark ? '#f1fff8' : '#333333';
    $secondaryText = $isDark ? '#a0d5d0' : '#999999';
@endphp

<div class="calendar-stats-section" style="margin: 16px; padding: 20px; background: {{ $bgColor }}; border: 1px solid {{ $borderColor }}; border-radius: 12px;">
    <div class="stats-title" style="font-weight: 700; margin-bottom: 16px; color: {{ $textColor }};">
        Estadísticas del Período
    </div>

    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
        
        {{-- Total de Partidos --}}
        <div class="stat-item" style="text-align: center; padding: 12px; background: {{ $isDark ? 'rgba(0, 222, 176, 0.1)' : 'rgba(0, 222, 176, 0.05)' }}; border-radius: 8px;">
            <div style="font-size: 24px; font-weight: 700; color: #00deb0;">{{ $stats['total'] ?? 0 }}</div>
            <div style="font-size: 11px; color: {{ $secondaryText }}; margin-top: 4px; text-transform: uppercase;">Partidos</div>
        </div>

        {{-- Programados --}}
        <div class="stat-item" style="text-align: center; padding: 12px; background: {{ $isDark ? 'rgba(255, 193, 7, 0.1)' : 'rgba(255, 193, 7, 0.05)' }}; border-radius: 8px;">
            <div style="font-size: 24px; font-weight: 700; color: #ffc107;">{{ $stats['scheduled'] ?? 0 }}</div>
            <div style="font-size: 11px; color: {{ $secondaryText }}; margin-top: 4px; text-transform: uppercase;">Próximos</div>
        </div>

        {{-- En Vivo --}}
        <div class="stat-item" style="text-align: center; padding: 12px; background: {{ $isDark ? 'rgba(255, 107, 107, 0.1)' : 'rgba(255, 107, 107, 0.05)' }}; border-radius: 8px;">
            <div style="font-size: 24px; font-weight: 700; color: #ff6b6b;">{{ $stats['live'] ?? 0 }}</div>
            <div style="font-size: 11px; color: {{ $secondaryText }}; margin-top: 4px; text-transform: uppercase;">En Vivo</div>
        </div>

        {{-- Finalizados --}}
        <div class="stat-item" style="text-align: center; padding: 12px; background: {{ $isDark ? 'rgba(100, 200, 200, 0.1)' : 'rgba(100, 200, 200, 0.05)' }}; border-radius: 8px;">
            <div style="font-size: 24px; font-weight: 700; color: #64c8c8;">{{ $stats['finished'] ?? 0 }}</div>
            <div style="font-size: 11px; color: {{ $secondaryText }}; margin-top: 4px; text-transform: uppercase;">Finalizados</div>
        </div>
    </div>
</div>
