<x-app-layout
    :logo-url="asset('images/logo_alone.png')"
    alt-text="Offside Club"
>
    @section('navigation-title', $group->name . ' - Pre Matches')

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
        $accentLight = 'rgba(0, 222, 176, 0.1)';
        $buttonBgHover = $isDark ? 'rgba(0, 222, 176, 0.12)' : 'rgba(0, 222, 176, 0.08)';
        $redAccent = '#ff6b6b';
        $redLight = 'rgba(255, 107, 107, 0.1)';
        $orangeAccent = '#ff9500';
    @endphp

    <div class="min-h-screen p-1 md:p-6 pb-24" style="background: {{ $bgPrimary }}; color: {{ $textPrimary }}; margin-top: 3.75rem;">

        <!-- Page Header -->
        <div class="ml-1 mr-1" style="background: {{ $bgTertiary }}; padding: 20px; border-radius: 16px; border: 1px solid {{ $borderColor }}; margin-bottom: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                <div>
                    <h1 style="font-size: 28px; font-weight: 700; color: {{ $textPrimary }}; margin: 0; display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 36px;">🔥</span>
                        Pre Match Challenges
                    </h1>
                    <p style="color: {{ $textSecondary }}; margin: 8px 0 0 0; font-size: 14px;">
                        Crea desafíos y compite con tu grupo
                    </p>
                </div>
                <a href="{{ route('groups.show', $group) }}"
                   style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border: none; border-radius: 8px; background: {{ $isDark ? '#2a4a47' : '#e5f3f0' }}; color: {{ $accentColor }}; font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid {{ $borderColor }}; transition: all 0.2s ease; text-decoration: none;"
                   onmouseover="this.style.background='{{ $accentLight }}'"
                   onmouseout="this.style.background='{{ $isDark ? '#2a4a47' : '#e5f3f0' }}';">
                    ← Volver
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 24px;">
            <!-- Total Desafíos -->
            <div style="background: {{ $bgTertiary }}; padding: 16px; border-radius: 12px; border: 1px solid {{ $borderColor }};">
                <p style="font-size: 12px; font-weight: 700; color: {{ $textSecondary }}; margin: 0; text-transform: uppercase;">
                    📊 Total
                </p>
                <p style="font-size: 28px; font-weight: 700; color: {{ $accentColor }}; margin: 12px 0 0 0;">
                    {{ $preMatches->count() }}
                </p>
            </div>

            <!-- Pendientes -->
            <div style="background: {{ $bgTertiary }}; padding: 16px; border-radius: 12px; border: 1px solid {{ $borderColor }};">
                <p style="font-size: 12px; font-weight: 700; color: {{ $textSecondary }}; margin: 0; text-transform: uppercase;">
                    ⏳ Pendientes
                </p>
                <p style="font-size: 28px; font-weight: 700; color: #ff6b6b; margin: 12px 0 0 0;">
                    {{ $preMatches->where('status', 'pending')->count() }}
                </p>
            </div>

            <!-- Activos -->
            <div style="background: {{ $bgTertiary }}; padding: 16px; border-radius: 12px; border: 1px solid {{ $borderColor }};">
                <p style="font-size: 12px; font-weight: 700; color: {{ $textSecondary }}; margin: 0; text-transform: uppercase;">
                    🔴 Activos
                </p>
                <p style="font-size: 28px; font-weight: 700; color: #ffa726; margin: 12px 0 0 0;">
                    {{ $preMatches->where('status', 'active')->count() }}
                </p>
            </div>

            <!-- Completados -->
            <div style="background: {{ $bgTertiary }}; padding: 16px; border-radius: 12px; border: 1px solid {{ $borderColor }};">
                <p style="font-size: 12px; font-weight: 700; color: {{ $textSecondary }}; margin: 0; text-transform: uppercase;">
                    ✅ Completados
                </p>
                <p style="font-size: 28px; font-weight: 700; color: #66bb6a; margin: 12px 0 0 0;">
                    {{ $preMatches->where('status', 'completed')->count() }}
                </p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div style="display: flex; gap: 8px; overflow-x: auto; margin-bottom: 24px; border-bottom: 2px solid {{ $borderColor }}; padding-bottom: 0;">
            <button onclick="filterMatches('ALL')"
                    id="filterAll"
                    style="padding: 12px 16px; border: none; background: transparent; color: {{ $accentColor }}; font-weight: 700; font-size: 13px; cursor: pointer; border-bottom: 3px solid {{ $accentColor }}; transition: all 0.2s ease; white-space: nowrap;">
                📊 Todos
            </button>
            <button onclick="filterMatches('pending')"
                    id="filterPending"
                    style="padding: 12px 16px; border: none; background: transparent; color: {{ $textSecondary }}; font-weight: 700; font-size: 13px; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s ease; white-space: nowrap;">
                ⏳ Pendientes
            </button>
            <button onclick="filterMatches('active')"
                    id="filterActive"
                    style="padding: 12px 16px; border: none; background: transparent; color: {{ $textSecondary }}; font-weight: 700; font-size: 13px; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s ease; white-space: nowrap;">
                🔴 Activos
            </button>
            <button onclick="filterMatches('completed')"
                    id="filterCompleted"
                    style="padding: 12px 16px; border: none; background: transparent; color: {{ $textSecondary }}; font-weight: 700; font-size: 13px; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s ease; white-space: nowrap;">
                ✅ Completados
            </button>
        </div>

        <!-- Pre Matches List -->
        <div id="preMatchesContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 32px;">
            @forelse($preMatches as $preMatch)
                <x-pre-match.card
                    :preMatch="$preMatch"
                    :match="$preMatch->match"
                    :isDark="$isDark"
                    :bgTertiary="$bgTertiary"
                    :textPrimary="$textPrimary"
                    :textSecondary="$textSecondary"
                    :borderColor="$borderColor"
                    :accentColor="$accentColor"
                    :redAccent="$redAccent"
                    :redLight="$redLight"
                    :orangeAccent="$orangeAccent"
                />
            @empty
                <div style="grid-column: 1 / -1; text-align: center; padding: 48px 20px; color: {{ $textSecondary }};">
                    <p style="font-size: 18px; margin: 0 0 16px 0;">📭 No hay desafíos creados aún</p>
                    <button onclick="openCreatePreMatchModal({{ $group->id }})"
                            style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 8px; background: linear-gradient(135deg, {{ $redAccent }}, #ff8787); color: #fff; font-weight: 700; cursor: pointer; transition: transform 0.2s ease; text-decoration: none;"
                            onmouseover="this.style.transform='translateY(-2px)'"
                            onmouseout="this.style.transform='translateY(0)';">
                        <i class="fas fa-fire"></i>
                        ➕ Crear Primer Desafío
                    </button>
                </div>
            @endforelse
        </div>

        <!-- Penalty History Section -->
        @if($groupPenalties->count() > 0)
        <div style="background: {{ $bgTertiary }}; padding: 24px; border-radius: 16px; border: 1px solid {{ $borderColor }}; margin-top: 32px;">
            <h2 style="font-size: 18px; font-weight: 700; color: {{ $textPrimary }}; margin: 0 0 20px 0;">
                ⚖️ Castigos Recientes
            </h2>

            <div style="display: grid; gap: 12px;">
                @foreach($groupPenalties->take(10) as $penalty)
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: {{ $bgSecondary }}; border-radius: 8px; border: 1px solid {{ $borderColor }};">
                    <div>
                        <p style="font-weight: 600; color: {{ $textPrimary }}; margin: 0 0 4px 0;">
                            {{ $penalty->user->name }}
                        </p>
                        <p style="font-size: 12px; color: {{ $textSecondary }}; margin: 0;">
                            @if($penalty->penalty_type === 'POINTS')
                                💔 -{{ $penalty->penalty_points }} puntos
                            @else
                                📝 {{ $penalty->penalty_description }}
                            @endif
                        </p>
                    </div>
                    <div style="text-align: right;">
                        @if($penalty->fulfilled_at)
                            <span style="display: inline-block; padding: 4px 12px; background: #4CAF50; color: #fff; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                ✓ Cumplido
                            </span>
                        @else
                            <span style="display: inline-block; padding: 4px 12px; background: {{ $redLight }}; color: {{ $redAccent }}; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                ⏳ Pendiente
                            </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    <!-- Create Pre Match Modal -->
    <x-modals.create-pre-match-modal :isDark="$isDark" />

    <script>
        let allPreMatches = @json($preMatches);
        let currentFilter = 'ALL';

        function filterMatches(status) {
            currentFilter = status;

            // Update button styles
            const buttons = {
                'ALL': document.getElementById('filterAll'),
                'pending': document.getElementById('filterPending'),
                'active': document.getElementById('filterActive'),
                'completed': document.getElementById('filterCompleted')
            };

            Object.values(buttons).forEach(btn => {
                if (btn) {
                    btn.style.borderBottomColor = 'transparent';
                    btn.style.color = '{{ $textSecondary }}';
                }
            });

            if (buttons[status]) {
                buttons[status].style.borderBottomColor = '{{ $accentColor }}';
                buttons[status].style.color = '{{ $accentColor }}';
            }

            // Filter cards (placeholder - actual filtering can be done with JS or server-side)
        }
    </script>

    <x-layout.bottom-navigation active-item="pre-matches" />

</x-app-layout>

