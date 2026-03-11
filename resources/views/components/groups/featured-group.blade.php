@props([
    'group' => null,
    'title' => __('views.groups.featured_public_group')
])

@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
    $bgColor = $isDark ? '#0d3d3a' : '#f0fffe';
    $borderColor = $isDark ? '#1d5956' : '#d0f0ee';
    $textColor = $isDark ? '#ffffff' : '#333333';
    $secondaryText = $isDark ? '#b0e0dd' : '#666666';
    $badgeBg = '#00deb0';
    $accentColor = '#17b796';
    $shadowDark = '0 8px 24px rgba(0, 222, 176, 0.15)';
    $shadowLight = '0 4px 12px rgba(0, 222, 176, 0.08)';
@endphp

@if($group)
<div class="featured-group">
    <div class="featured-title" style="display: flex; align-items: center; gap: 8px; font-size: 16px; font-weight: 700; margin-bottom: 12px; color: {{ $badgeBg }}; margin-left: 15px; margin-right: 15px;">
        <i class="fas fa-globe"></i> {{ $title }}
    </div>

    <div class="group-card" style="margin: 0 15px 20px 15px; background: {{ $bgColor }}; border: 2px solid {{ $borderColor }}; border-radius: 12px; padding: 20px; color: {{ $textColor }}; box-shadow: {{ $shadowLight }}; cursor: pointer; transition: all 0.3s ease;" onclick="window.location.href='{{ route('groups.show', $group->id) }}'" onmouseover="this.style.boxShadow='{{ $shadowDark }}'; this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='{{ $shadowLight }}'; this.style.transform='translateY(0)'">

        {{-- Header con nombre y badge --}}
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; gap: 12px;">
            <h3 style="font-size: 18px; font-weight: 700; margin: 0; flex: 1; color: {{ $textColor }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ $group->name }}
            </h3>
            <span style="background: {{ $badgeBg }}; color: #000; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; flex-shrink: 0;">
                {{ __('views.groups.public') }}
            </span>
        </div>

        {{-- Información del grupo --}}
        <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
            {{-- Creador --}}
            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                <i class="fas fa-user-circle" style="color: {{ $accentColor }}; width: 20px; text-align: center;"></i>
                <span style="color: {{ $secondaryText }};">
                    {{ __('views.groups.created_by') }}: 
                    <strong style="color: {{ $textColor }};">{{ $group->creator->name }}</strong>
                </span>
            </div>

            {{-- Miembros --}}
            <div style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                <i class="fas fa-users" style="color: {{ $accentColor }}; width: 20px; text-align: center;"></i>
                <span style="color: {{ $secondaryText }};">
                    <strong style="color: {{ $textColor }};">{{ $group->users()->count() }}</strong> {{ __('views.groups.members') }}
                </span>
            </div>

            {{-- Fecha de expiración (si existe) --}}
            @if($group->expires_at)
                <div style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: {{ $group->isExpired() ? '#ff5252' : '#ff9800' }};">
                    <i class="fas fa-clock" style="width: 20px; text-align: center;"></i>
                    <span>
                        @if($group->isExpired())
                            {{ __('views.groups.expired') }}
                        @else
                            {{ __('views.groups.expires_in') }}: <strong>{{ $group->expires_at->diffForHumans() }}</strong>
                        @endif
                    </span>
                </div>
            @endif
        </div>

        {{-- Botón de acción --}}
        <button onclick="event.stopPropagation(); joinPublicGroup({{ $group->id }})"
                style="width: 100%; padding: 14px; background: linear-gradient(135deg, {{ $accentColor }}, {{ $badgeBg }}); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 15px; cursor: pointer; transition: all 0.3s ease;">
            @if(auth()->user()->groups->contains($group->id))
                <i class="fas fa-check"></i> {{ __('views.groups.already_member') }}
            @else
                <i class="fas fa-sign-in-alt"></i> {{ __('views.groups.join_now') }}
            @endif
        </button>

        {{-- Hint text --}}
        <p style="font-size: 12px; color: {{ $secondaryText }}; margin-top: 12px; margin-bottom: 0; text-align: center;">
            <i class="fas fa-mouse"></i> {{ __('views.groups.click_to_see_group') }}
        </p>
    </div>
</div>

<script>
    window.joinPublicGroup = function(groupId) {
        // Verificar si ya es miembro
        const isAlreadyMember = document.querySelector(`button[onclick*="joinPublicGroup(${groupId})"]`)?.textContent.includes('{{ __('views.groups.already_member') }}');
        
        if (isAlreadyMember) {
            window.location.href = '/groups/' + groupId;
            return;
        }
        
        // Abrir modal de unirse al grupo
        const code = prompt('{{ __('views.settings.group_code_placeholder') }}');
        if (code) {
            fetch('{{ route('groups.join') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({ code: code })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/groups/' + groupId;
                } else {
                    alert(data.message || 'Error al unirse al grupo');
                }
            })
            .catch(error => console.error('Error:', error));
        }
    };
</script>
