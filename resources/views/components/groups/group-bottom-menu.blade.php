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

<div style="position: fixed; bottom: 0; left: 0; right: 0; background: {{ $bgTertiary }}; border-top: 1px solid {{ $borderColor }};">
    <div style="max-width: 56rem; margin: 0 auto;">
        <div style="display: flex; justify-content: space-around; align-items: center; padding: 0.75rem;">
            <a href="{{ route('groups.index') }}" style="display: flex; flex-direction: column; align-items: center; color: {{ $textSecondary }}; transition: color 0.2s;" onmouseover="this.style.color='{{ $textPrimary }}'" onmouseout="this.style.color='{{ $textSecondary }}'">
                <svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span style="font-size: 0.75rem; margin-top: 0.25rem;">Grupos</span>
            </a>
            <a href="{{ route('rankings.group', $group) }}" style="display: flex; flex-direction: column; align-items: center; color: {{ $textSecondary }}; transition: color 0.2s;" onmouseover="this.style.color='{{ $textPrimary }}'" onmouseout="this.style.color='{{ $textSecondary }}'">
                <svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span style="font-size: 0.75rem; margin-top: 0.25rem;">Ranking</span>
            </a>
            <a href="{{ route('market.index') }}" style="display: flex; flex-direction: column; align-items: center; color: {{ $textSecondary }}; transition: color 0.2s;" onmouseover="this.style.color='{{ $textPrimary }}'" onmouseout="this.style.color='{{ $textSecondary }}'">
                <svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span style="font-size: 0.75rem; margin-top: 0.25rem;">Market</span>
            </a>
            <a href="{{ route('groups.predictive-results', $group) }}" style="display: flex; flex-direction: column; align-items: center; color: {{ $textSecondary }}; transition: color 0.2s;" onmouseover="this.style.color='{{ $textPrimary }}'" onmouseout="this.style.color='{{ $textSecondary }}'">
                <svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span style="font-size: 0.75rem; margin-top: 0.25rem;">Resultados</span>
            </a>
            {{-- <a href="{{ route('profile.edit') }}" style="display: flex; flex-direction: column; align-items: center; color: {{ $textSecondary }}; transition: color 0.2s;" onmouseover="this.style.color='{{ $textPrimary }}'" onmouseout="this.style.color='{{ $textSecondary }}'">
                <svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span style="font-size: 0.75rem; margin-top: 0.25rem;">Perfil</span>
            </a> --}}
        </div>
    </div>
    <!-- Botón flotante del chat -->
    @if (request()->route()->getName() !== 'groups.predictive-results')
        <button id="chatToggle" style="position: fixed; bottom: 6rem; right: 2rem; background: {{ $accentColor }}; color: #000; border-radius: 50%; padding: 1rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3); transition: all 0.3s; display: flex; align-items: center; justify-content: center; z-index: 50; border: none; cursor: pointer;" class="hover:opacity-90">
            <svg xmlns="http://www.w3.org/2000/svg" style="height: 24px; width: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span id="unreadCount" style="position: absolute; top: -4px; right: -4px; background: #ef4444; color: white; font-size: 0.75rem; border-radius: 50%; height: 20px; width: 20px; display: flex; align-items: center; justify-content: center;">
                {{ $group->chatMessages()->count() }}
            </span>
        </button>
    @endif
</div>
