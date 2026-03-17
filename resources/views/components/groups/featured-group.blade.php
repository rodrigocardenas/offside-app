@props([
    'group' => null,
    'quizGroup' => null,
    'title' => __('views.groups.featured_public_group')
])

@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDark = $themeMode === 'dark';
    $bgPrimary = $isDark ? '#0a2e2c' : '#f5f5f5';
    $bgColor = $isDark ? '#0d3d3a' : '#f0fffe';
    $borderColor = $isDark ? '#1d5956' : '#d0f0ee';
    $textColor = $isDark ? '#ffffff' : '#333333';
    $secondaryText = $isDark ? '#b0e0dd' : '#666666';
    $badgeBg = '#00deb0';
    $accentColor = '#17b796';
    $accentDark = '#003b2f';
    $componentsBg = $isDark ? '#1a524e' : '#ffffff';
    $shadowDark = '0 8px 24px rgba(0, 222, 176, 0.15)';
    $shadowLight = '0 4px 12px rgba(0, 222, 176, 0.08)';
@endphp

@if($group || $quizGroup)
<div class="mt-4">
    {{-- Título del carrusel --}}
    {{-- <div class="flex items-center gap-2 mb-6 px-4">
        <i class="fas fa-rocket" style="color: {{ $badgeBg }};"></i>
        <h2 class="text-base font-semibold" style="color: {{ $textColor }};">Grupos Destacados</h2>
    </div> --}}

    {{-- Carrusel --}}
    <div class="relative flex items-center mt-2">
        <!-- Contenedor de scroll con snap-scroll personalizado -->
        <div class="featured-carousel-wrapper" id="featuredGroupsCarousel">
            {{-- Card de Grupo Público --}}
            @if($group)
                <div class="featured-carousel-card" style="background: {{ $componentsBg }}; border-color: {{ $borderColor }}; min-width: 310px; cursor: pointer; transition: all 0.3s ease;" onclick="window.location.href='{{ route('groups.show', $group->id) }}'" onmouseover="this.style.boxShadow='{{ $shadowDark }}'; this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='{{ $shadowLight }}'; this.style.transform='translateY(0)'">

                    {{-- Badge de categoría --}}
                    <div style="text-align: center; margin-bottom: 16px;">
                        <div style="display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 11px; font-weight: 700; background: {{ $badgeBg }}; color: #000; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-globe"></i> {{ __('views.groups.public') }}
                        </div>
                    </div>

                    {{-- Nombre del grupo --}}
                    <h3 style="font-size: 16px; font-weight: 700; text-align: center; margin: 0 0 16px 0; color: {{ $textColor }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $group->name }}
                    </h3>

                    {{-- Información --}}
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px;">
                            <i class="fas fa-users" style="color: {{ $accentColor }};"></i>
                            <span style="color: {{ $secondaryText }};">
                                <strong style="color: {{ $textColor }};">{{ $group->users()->count() }}</strong> {{ __('views.groups.members') }}
                            </span>
                        </div>

                        @if($group->expires_at)
                            <div style="display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; color: {{ $group->isExpired() ? '#ff5252' : '#ff9800' }};">
                                <i class="fas fa-clock"></i>
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
                    <button onclick="event.stopPropagation(); joinPublicGroup({{ $group->id }}, '{{ $group->code }}')"
                            style="width: 100%; padding: 12px; background: linear-gradient(135deg, {{ $accentColor }}, {{ $badgeBg }}); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: opacity 0.3s ease;"
                            onmouseover="this.style.opacity='0.9'"
                            onmouseout="this.style.opacity='1'">
                        @if(auth()->user()->groups->contains($group->id))
                            <i class="fas fa-check"></i> {{ __('views.groups.already_member') }}
                        @else
                            <i class="fas fa-sign-in-alt"></i> {{ __('views.groups.join_now') }}
                        @endif
                    </button>
                </div>
            @endif

            {{-- Card de Grupo Quiz --}}
            @if($quizGroup)
                <div class="featured-carousel-card" style="background: {{ $componentsBg }}; border-color: {{ $borderColor }}; min-width: 310px; cursor: pointer; transition: all 0.3s ease;" onclick="window.location.href='{{ route('groups.show', $quizGroup->id) }}'" onmouseover="this.style.boxShadow='{{ $shadowDark }}'; this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='{{ $shadowLight }}'; this.style.transform='translateY(0)'">

                    {{-- Badge de categoría --}}
                    <div style="text-align: center; margin-bottom: 16px;">
                        <div style="display: inline-block; padding: 6px 16px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #8b5cf6; color: #fff; text-transform: uppercase; letter-spacing: 0.5px;">
                            <i class="fas fa-question-circle"></i> Quiz
                        </div>
                    </div>

                    {{-- Nombre del grupo --}}
                    <h3 style="font-size: 16px; font-weight: 700; text-align: center; margin: 0 0 16px 0; color: {{ $textColor }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $quizGroup->name }}
                    </h3>

                    {{-- Información --}}
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px;">
                            <i class="fas fa-users" style="color: #8b5cf6;"></i>
                            <span style="color: {{ $secondaryText }};">
                                <strong style="color: {{ $textColor }};">{{ $quizGroup->users()->count() }}</strong> {{ __('views.groups.members') }}
                            </span>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px;">
                            {{-- <i class="fas fa-info" style="color: #8b5cf6;"></i> --}}
                            <p style="color: {{ $secondaryText }}; max-width: 240px; text-align: center; font-style: italic;">
                                <small>{{ __('views.groups.quiz_group_description') }}</small>
                            </p>
                        </div>

                </div>
            @endif
        </div>

        {{-- Botón Flecha Izquierda --}}
        {{-- <button class="featured-carousel-btn featured-carousel-btn-left" style="color: {{ $accentColor }}; background: {{ $componentsBg }};" onclick="document.getElementById('featuredGroupsCarousel').scrollBy({left: -350, behavior: 'smooth'})">
            <i class="fas fa-chevron-left"></i>
        </button> --}}

        {{-- Botón Flecha Derecha --}}
        {{-- <button class="featured-carousel-btn featured-carousel-btn-right" style="color: {{ $accentColor }}; background: {{ $componentsBg }};" onclick="document.getElementById('featuredGroupsCarousel').scrollBy({left: 350, behavior: 'smooth'})">
            <i class="fas fa-chevron-right"></i>
        </button> --}}
    </div>

    {{-- Indicadores de navegación --}}
    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 16px;">
        @php $cardCount = ($group ? 1 : 0) + ($quizGroup ? 1 : 0); @endphp
        @for($i = 0; $i < $cardCount; $i++)
            <button class="featured-group-indicator" style="width: 8px; height: 8px; border-radius: 50%; background: {{ $borderColor }}; border: none; cursor: pointer; transition: all 0.3s ease;" data-index="{{ $i }}"></button>
        @endfor
    </div>
</div>

<style>
    .featured-carousel-wrapper {
        overflow-x: auto;
        overflow-y: hidden;
        display: flex;
        gap: 16px;
        padding: 0 4px 16px 4px;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .featured-carousel-wrapper::-webkit-scrollbar {
        display: none;
    }

    .featured-carousel-card {
        flex: 0 0 auto;
        padding: 20px;
        border: 1px solid;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .featured-carousel-btn {
        position: absolute;
        top: 30%;
        transform: translateY(-50%);
        z-index: 10;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }

    .featured-carousel-btn:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .featured-carousel-btn-left {
        left: 0;
    }

    .featured-carousel-btn-right {
        right: 0;
    }
</style>

<script>
    window.joinPublicGroup = function(groupId, groupCode = null) {
        const isAlreadyMember = document.querySelector(`button[onclick*="joinPublicGroup(${groupId})"]`)?.textContent.includes('{{ __('views.groups.already_member') }}');

        if (isAlreadyMember) {
            window.location.href = '/groups/' + groupId;
            return;
        }


        window.location.href = '/groups/invite/' + groupCode;

    };

    // Actualizar indicadores al hacer scroll
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.getElementById('featuredGroupsCarousel');
        if (!carousel) return;

        const updateIndicators = () => {
            const scrollPosition = carousel.scrollLeft;
            const cardWidth = 310; // min-width de las tarjetas
            const activeIndex = Math.round(scrollPosition / (cardWidth + 16)); // 16px es el espacio entre cards

            document.querySelectorAll('.featured-group-indicator').forEach((indicator, index) => {
                if (index === activeIndex) {
                    indicator.style.width = '8px';
                    indicator.style.background = '{{ $badgeBg }}';
                } else {
                    indicator.style.width = '8px';
                    indicator.style.background = 'rgba(0, 222, 176, 0.3)';
                }
            });
        };

        carousel.addEventListener('scroll', updateIndicators);
        updateIndicators();

        // Click en indicadores
        document.querySelectorAll('.featured-group-indicator').forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                const cardWidth = 310;
                carousel.scrollTo({
                    left: index * (cardWidth + 16),
                    behavior: 'smooth'
                });
            });
        });
    });
</script>
@endif
