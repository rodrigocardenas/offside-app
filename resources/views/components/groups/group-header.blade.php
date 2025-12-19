@php
    $themeColors = $themeColors ?? [];
    $bgPrimary = $themeColors['bgPrimary'] ?? '#0a2e2c';
    $bgSecondary = $themeColors['bgSecondary'] ?? '#0f3d3a';
    $bgTertiary = $themeColors['bgTertiary'] ?? '#1a524e';
    $textPrimary = $themeColors['textPrimary'] ?? '#ffffff';
    $textSecondary = $themeColors['textSecondary'] ?? '#b0b0b0';
    $borderColor = $themeColors['borderColor'] ?? '#2a4a47';
    $accentColor = $themeColors['accentColor'] ?? '#00deb0';
    $accentDark = $themeColors['accentDark'] ?? '#17b796';
@endphp

<!-- Header del Grupo -->
<div style="background: {{ $bgSecondary }}; padding: 1.25rem 1rem 1rem; position: relative; border-bottom: 1px solid {{ $borderColor }}; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 1rem;">
    <!-- Botón de retroceso -->
    <a href="{{ route('groups.index') }}" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: {{ $accentColor }}; font-size: 1.25rem; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; text-decoration: none;">
        <i class="fas fa-arrow-left"></i>
    </a>

    <!-- Grupo Info (centrado) -->
    <div style="display: flex; align-items: center; justify-content: center; gap: 0.75rem; margin-bottom: 1rem;">
        <div style="width: 2.5rem; height: 2.5rem; background: #ff6b6b; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.125rem; font-weight: bold; color: #000;">
            <i class="fas fa-trophy"></i>
        </div>
        <div style="font-size: 1.25rem; font-weight: 600; color: {{ $textPrimary }}; text-align: center;">{{ $group->name }}</div>
    </div>
</div>
