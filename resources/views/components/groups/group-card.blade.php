@php
    $themeMode = auth()->user()->theme_mode ?? 'auto';
    $isDarkMode = $themeMode === 'dark';
    $bgColor = $isDarkMode ? '#1a524e' : '#ffffff';
    $textColor = $isDarkMode ? '#ffffff' : '#333333';
    $secondaryTextColor = $isDarkMode ? '#b0b0b0' : '#999999';
    $hoverBg = $isDarkMode ? 'rgba(0, 222, 176, 0.1)' : 'rgba(0, 222, 176, 0.05)';
    $borderColor = $isDarkMode ? '#2a4a47' : '#e0e0e0';
@endphp

@props([
    'group',
    'userRank' => null,
    'hasPending' => false,
    'showMembers' => true
])

<div class="group-card" style="position: relative;">
    <div style="cursor: pointer; onclick-disabled=true;" onclick="window.location.href='{{ route('groups.show', $group) }}'">
        {{-- <div class="group-status">
            @if($hasPending)
                <i class="fas fa-exclamation-triangle"></i>
            @else
                <i class="fas fa-check-circle"></i>
            @endif
        </div> --}}

        <div class="group-header">
            <div class="group-avatar">
                @if($group->competition && $group->competition->crest_url)
                    {{-- <img src="{{ asset('images/competitions/' . $group->competition->crest_url) }}" alt="{{ $group->name }}"> --}}
                    <i class="fas fa-trophy" style="color: #000;"></i>
                @else
                    <i class="fas fa-trophy" style="color: #000;"></i>
                @endif
            </div>
            <div class="group-info">
                <h3>
                    {{ $group->name }}
                    @if($hasPending)
                        <span title="Tienes predicciones pendientes" style="color: red; margin-left: 8px;">
                            <small><i class="fas fa-circle"></i></small>
                        </span>
                    @endif
                </h3>

                <div class="group-stats">
                    @if($showMembers)
                        <span><i class="fas fa-users"></i> {{ $group->users_count ?? $group->users->count() }} miembros</span>
                    @endif
                    @if($userRank)
                        <div class="ranking-badge">
                            <i class="fas fa-trophy"></i> Ranking: #{{ $userRank }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Menú de 3 puntos -->
    <div style="position: absolute; top: 12px; right: 12px; z-index: 10;">
        <button class="group-menu-btn" onclick="event.stopPropagation(); toggleGroupMenu(event, 'group-{{ $group->id }}')"
                style="background: none; border: none; color: {{ $textColor }}; cursor: pointer; font-size: 18px; padding: 8px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s ease;"
                onmouseover="this.style.background='{{ $hoverBg }}'"
                onmouseout="this.style.background='none'">
            <i class="fas fa-ellipsis-v"></i>
        </button>

        <!-- Dropdown -->
        <div class="group-menu-dropdown" id="group-{{ $group->id }}"
             style="display: none; position: absolute; top: 100%; right: 0; background: {{ $bgColor }}; border: 1px solid {{ $borderColor }}; border-radius: 8px; min-width: 180px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 20; margin-top: 4px;">

            <!-- Opción Compartir -->
            <button onclick="event.stopPropagation(); showInviteModal('{{ $group->name }}', '{{ route('groups.invite', $group->code) }}'); closeGroupMenu('group-{{ $group->id }}')"
                    style="width: 100%; text-align: left; padding: 12px 16px; background: none; border: none; color: {{ $textColor }}; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid {{ $borderColor }}; transition: background 0.2s ease;"
                    onmouseover="this.style.background='{{ $hoverBg }}'"
                    onmouseout="this.style.background='none'">
                <i class="fas fa-share-alt" style="width: 16px; color: #00deb0;"></i>
                <span>Compartir grupo</span>
            </button>

            <!-- Opción Salir (si es miembro) -->
            @if($group->users()->where('user_id', auth()->id())->exists())
                <form action="{{ route('groups.leave', $group) }}" method="POST" style="width: 100%;">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            style="width: 100%; text-align: left; padding: 12px 16px; background: none; border: none; color: {{ $textColor }}; cursor: pointer; font-size: 14px; display: flex; align-items: center; gap: 12px; transition: background 0.2s ease;"
                            onmouseover="this.style.background='{{ $hoverBg }}'"
                            onmouseout="this.style.background='none'"
                            onclick="event.stopPropagation(); return confirm('¿Estás seguro de que quieres salir de este grupo?');">
                        <i class="fas fa-sign-out-alt" style="width: 16px; color: #ffa500;"></i>
                        <span>Salir del grupo</span>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    window.toggleGroupMenu = function(event, menuId) {
        event.stopPropagation();
        const menu = document.getElementById(menuId);
        const isOpen = menu.style.display === 'flex';

        // Cerrar todos los menús abiertos
        document.querySelectorAll('.group-menu-dropdown').forEach(m => m.style.display = 'none');

        // Abrir/cerrar el menú actual
        if (!isOpen) {
            menu.style.display = 'flex';
            menu.style.flexDirection = 'column';
        }
    };

    window.closeGroupMenu = function(menuId) {
        const menu = document.getElementById(menuId);
        if (menu) {
            menu.style.display = 'none';
        }
    };

    // Cerrar menús al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.group-menu-btn') && !e.target.closest('.group-menu-dropdown')) {
            document.querySelectorAll('.group-menu-dropdown').forEach(m => m.style.display = 'none');
        }
    });
})();
</script>
