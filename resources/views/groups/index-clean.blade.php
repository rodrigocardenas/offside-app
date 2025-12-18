<x-app-layout>
    <div class="main-container">
        {{-- 1. HEADER CON LOGO --}}
        <x-layout.header-profile
            :logo-url="asset('images/logo.png')"
            alt-text="Offside Club"
        />

        {{-- 2. BARRA DE ESTADÍSTICAS --}}
        <x-groups.stats-bar
            :streak="$userStreak"
            :accuracy="$userAccuracy"
            :groups-count="$totalGroups"
        />

        {{-- 3. BANNER DE NOTIFICACIONES --}}
        <x-common.notification-banner
            :show="$hasPendingPredictions"
            message="Tienes predicciones pendientes en algunos grupos"
            type="warning"
        />

        {{-- 4. PARTIDO DESTACADO --}}
        @if($featuredMatch)
            <x-matches.featured-match
                :match="$featuredMatch"
                title="Partido Destacado del Día"
            />
        @endif

        {{-- 5. SECCIÓN DE GRUPOS --}}
        <div class="groups-section">
            <div class="section-title">
                <i class="fas fa-users"></i> Mis Grupos
            </div>

            {{-- Official Groups --}}
            @foreach($officialGroups as $group)
                <x-groups.group-card
                    :group="$group"
                    :user-rank="$group->userRank"
                    :has-pending="$group->pending"
                    :show-members="true"
                />
            @endforeach

            {{-- Amateur Groups --}}
            @foreach($amateurGroups as $group)
                <x-groups.group-card
                    :group="$group"
                    :user-rank="$group->userRank"
                    :has-pending="$group->pending"
                    :show-members="true"
                />
            @endforeach

            {{-- Empty State --}}
            @if($officialGroups->isEmpty() && $amateurGroups->isEmpty())
                <div style="text-align: center; padding: 40px 20px; color: #999;">
                    <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p style="font-size: 16px; margin-bottom: 8px;">No tienes grupos aún</p>
                    <p style="font-size: 14px;">Únete a un grupo o crea uno nuevo</p>
                </div>
            @endif
        </div>

        {{-- 6. NAVEGACIÓN INFERIOR --}}
        <x-layout.bottom-navigation active-item="grupo" />
    </div>

    {{-- MODALES (si existen) --}}
    @if(View::exists('components.feedback-modal'))
        <x-feedback-modal />
    @endif

    {{-- SCRIPTS MÍNIMOS --}}
    <script>
        // Feedback modal handler
        document.addEventListener('DOMContentLoaded', function() {
            const feedbackBtns = document.querySelectorAll('[onclick*="feedbackModal"]');
            const feedbackModal = document.getElementById('feedbackModal');

            feedbackBtns.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (feedbackModal) {
                        feedbackModal.classList.remove('hidden');
                    }
                });
            });
        });
    </script>
</x-app-layout>
