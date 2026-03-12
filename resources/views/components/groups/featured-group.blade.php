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
    <div class="flex items-center gap-2 mb-6 px-4">
        <i class="fas fa-rocket" style="color: {{ $badgeBg }};"></i>
        <h2 class="text-base font-semibold" style="color: {{ $textColor }};">Grupos Destacados</h2>
    </div>

    {{-- Carrusel --}}
    <div class="relative flex items-center">
        <!-- Contenedor de scroll -->
        <div class="overflow-x-auto hide-scrollbar snap-x snap-mandatory flex space-x-4 flex-1 px-1 pb-4" id="featuredGroupsCarousel">
            {{-- Card de Grupo Público --}}
            @if($group)
                <div class="snap-center flex-none w-full rounded-2xl p-5 border shadow-sm" style="background: {{ $componentsBg }}; border-color: {{ $borderColor }}; min-width: 300px; cursor: pointer; transition: all 0.3s ease;" onclick="window.location.href='{{ route('groups.show', $group->id) }}'" onmouseover="this.style.boxShadow='{{ $shadowDark }}'; this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='{{ $shadowLight }}'; this.style.transform='translateY(0)'">
                    
                    {{-- Badge de categoría --}}
                    <div class="text-center mb-5">
                        <div class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider" style="background: {{ $badgeBg }}; color: #000;">
                            <i class="fas fa-globe"></i> {{ __('views.groups.public') }}
                        </div>
                    </div>

                    {{-- Nombre del grupo --}}
                    <h3 class="text-base font-bold text-center mb-4" style="color: {{ $textColor }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $group->name }}
                    </h3>

                    {{-- Información --}}
                    <div class="space-y-3 mb-5">
                        <div class="flex items-center justify-center gap-2 text-xs" style="color: {{ $secondaryText }};">
                            <i class="fas fa-users" style="color: {{ $accentColor }};"></i>
                            <span>
                                <strong style="color: {{ $textColor }};">{{ $group->users()->count() }}</strong> {{ __('views.groups.members') }}
                            </span>
                        </div>

                        @if($group->expires_at)
                            <div class="flex items-center justify-center gap-2 text-xs" style="color: {{ $group->isExpired() ? '#ff5252' : '#ff9800' }};">
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
                    <button onclick="event.stopPropagation(); joinPublicGroup({{ $group->id }})"
                            class="w-full py-3 rounded-lg font-semibold text-sm transition-all"
                            style="background: linear-gradient(135deg, {{ $accentColor }}, {{ $badgeBg }}); color: white; border: none; cursor: pointer;"
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
                <div class="snap-center flex-none w-full rounded-2xl p-5 border shadow-sm" style="background: {{ $componentsBg }}; border-color: {{ $borderColor }}; min-width: 300px; cursor: pointer; transition: all 0.3s ease;" onclick="window.location.href='{{ route('groups.show', $quizGroup->id) }}'" onmouseover="this.style.boxShadow='{{ $shadowDark }}'; this.style.transform='translateY(-4px)'" onmouseout="this.style.boxShadow='{{ $shadowLight }}'; this.style.transform='translateY(0)'">
                    
                    {{-- Badge de categoría --}}
                    <div class="text-center mb-5">
                        <div class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wider" style="background: #8b5cf6; color: #fff;">
                            <i class="fas fa-question-circle"></i> Quiz
                        </div>
                    </div>

                    {{-- Nombre del grupo --}}
                    <h3 class="text-base font-bold text-center mb-4" style="color: {{ $textColor }}; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $quizGroup->name }}
                    </h3>

                    {{-- Información --}}
                    <div class="space-y-3 mb-5">
                        <div class="flex items-center justify-center gap-2 text-xs" style="color: {{ $secondaryText }};">
                            <i class="fas fa-users" style="color: #8b5cf6;"></i>
                            <span>
                                <strong style="color: {{ $textColor }};">{{ $quizGroup->users()->count() }}</strong> {{ __('views.groups.members') }}
                            </span>
                        </div>

                        @if($quizGroup->expires_at)
                            <div class="flex items-center justify-center gap-2 text-xs" style="color: {{ $quizGroup->isExpired() ? '#ff5252' : '#ff9800' }};">
                                <i class="fas fa-clock"></i>
                                <span>
                                    @if($quizGroup->isExpired())
                                        {{ __('views.groups.expired') }}
                                    @else
                                        {{ __('views.groups.expires_in') }}: <strong>{{ $quizGroup->expires_at->diffForHumans() }}</strong>
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>

                    {{-- Botón de acción --}}
                    <button onclick="event.stopPropagation(); joinPublicGroup({{ $quizGroup->id }})"
                            class="w-full py-3 rounded-lg font-semibold text-sm transition-all"
                            style="background: linear-gradient(135deg, #7c3aed, #8b5cf6); color: white; border: none; cursor: pointer;"
                            onmouseover="this.style.opacity='0.9'"
                            onmouseout="this.style.opacity='1'">
                        @if(auth()->user()->groups->contains($quizGroup->id))
                            <i class="fas fa-check"></i> {{ __('views.groups.already_member') }}
                        @else
                            <i class="fas fa-sign-in-alt"></i> {{ __('views.groups.join_now') }}
                        @endif
                    </button>
                </div>
            @endif
        </div>

        {{-- Botón Flecha Izquierda --}}
        <button class="absolute left-0 z-10 rounded-full p-2 shadow-md hover:shadow-lg transition-all" style="background: {{ $componentsBg }}; color: {{ $accentColor }}; top: 50%; transform: translateY(-50%);" onclick="document.getElementById('featuredGroupsCarousel').scrollBy({left: -300, behavior: 'smooth'})">
            <i class="fas fa-chevron-left text-lg"></i>
        </button>

        {{-- Botón Flecha Derecha --}}
        <button class="absolute right-0 z-10 rounded-full p-2 shadow-md hover:shadow-lg transition-all" style="background: {{ $componentsBg }}; color: {{ $accentColor }}; top: 50%; transform: translateY(-50%);" onclick="document.getElementById('featuredGroupsCarousel').scrollBy({left: 300, behavior: 'smooth'})">
            <i class="fas fa-chevron-right text-lg"></i>
        </button>
    </div>

    {{-- Indicadores de navegación --}}
    <div class="flex justify-center gap-1 mt-4">
        @php $cardCount = ($group ? 1 : 0) + ($quizGroup ? 1 : 0); @endphp
        @for($i = 0; $i < $cardCount; $i++)
            <button class="w-2 h-2 rounded-full featured-group-indicator transition-all" style="background: {{ $borderColor }};" data-index="{{ $i }}"></button>
        @endfor
    </div>
</div>

<script>
    window.joinPublicGroup = function(groupId) {
        const isAlreadyMember = document.querySelector(`button[onclick*="joinPublicGroup(${groupId})"]`)?.textContent.includes('{{ __('views.groups.already_member') }}');

        if (isAlreadyMember) {
            window.location.href = '/groups/' + groupId;
            return;
        }

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

    // Actualizar indicadores al hacer scroll
    document.addEventListener('DOMContentLoaded', function() {
        const carousel = document.getElementById('featuredGroupsCarousel');
        if (!carousel) return;

        const updateIndicators = () => {
            const scrollPosition = carousel.scrollLeft;
            const cardWidth = carousel.querySelector('.snap-center')?.offsetWidth || 300;
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
                const cardWidth = carousel.querySelector('.snap-center')?.offsetWidth || 300;
                carousel.scrollTo({
                    left: index * (cardWidth + 16),
                    behavior: 'smooth'
                });
            });
        });
    });
</script>
@endif
