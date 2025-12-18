<x-app-layout>
    <style>
        /* Reset y estilos base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            background: #f5f5f5 !important;
            color: #333 !important;
            overflow-x: hidden;
        }

        .main-container {
            max-width: 414px;
            margin: 0 auto;
            min-height: 100vh;
            background: #f5f5f5;
            position: relative;
            padding-bottom: 80px;
        }

        /* Header */
        .header {
            background: #fff;
            padding: 20px 16px 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-bottom: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .profile-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: space-around;
            padding: 12px 16px;
            background: #fff;
            margin: 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: #666;
        }

        .stat-item i {
            color: #00deb0;
        }

        .stat-value {
            color: #333;
            font-weight: 600;
        }

        /* Notification Banner */
        .notification-banner {
            background: #fff3cd;
            padding: 12px 16px;
            margin: 16px;
            border-radius: 8px;
            border-left: 4px solid #856404;
            font-size: 13px;
            color: #856404;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Featured Match */
        .featured-match {
            margin: 16px;
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .featured-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 16px;
        }

        .match-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #dee2e6;
        }

        .match-teams {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .team-name {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .team-logo {
            width: 24px;
            height: 24px;
            object-fit: contain;
        }

        .match-time-inline {
            font-size: 14px;
            font-weight: 600;
            color: #003b2f;
            margin: 0 8px;
        }

        .match-league {
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        /* Groups Section */
        .groups-section {
            margin: 16px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 16px;
        }

        /* Group Card */
        .group-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            border: 1px solid #e0e0e0;
            position: relative;
            transition: all 0.2s ease;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .group-card:hover {
            border-color: #00deb0;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .group-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .group-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #00deb0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .group-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }

        .group-info h3 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .group-stats {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 12px;
            color: #003b2f;
        }

        .group-status {
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 16px;
        }

        .group-status .fa-exclamation-triangle {
            color: #ffc107;
        }

        .group-status .fa-check-circle {
            color: #00deb0;
        }

        .ranking-badge {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #17b796;
        }

        /* Bottom Menu */
        .bottom-menu {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 414px;
            background: #fff;
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            border-top: 1px solid #e0e0e0;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
        }

        .menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 12px;
            cursor: pointer;
            transition: color 0.3s ease;
            color: #666;
            text-decoration: none;
        }

        .menu-item:hover {
            color: #00deb0;
        }

        .menu-item.active {
            color: #00deb0;
        }

        .menu-icon {
            font-size: 20px;
        }

        .menu-label {
            font-size: 11px;
            font-weight: 500;
        }
    </style>

    <div class="main-container">
        {{-- Header --}}
        <div class="header">
            <div class="profile-icon">
                <img src="{{ asset('images/logo.png') }}" alt="Offside Club">
            </div>
        </div>

        {{-- Stats Bar --}}
        <div class="stats-bar">
            <div class="stat-item">
                <i class="fas fa-trophy"></i> Racha: <span class="stat-value">{{ $userStreak }} días</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-bullseye"></i> Aciertos: <span class="stat-value">{{ $userAccuracy }}%</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-users"></i> Grupos: <span class="stat-value">{{ $totalGroups }}</span>
            </div>
        </div>

        {{-- Notification Banner --}}
        @if($hasPendingPredictions)
            <div class="notification-banner">
                <i class="fas fa-exclamation-triangle"></i> Tienes predicciones pendientes en algunos grupos
            </div>
        @endif

        {{-- Featured Match --}}
        @if($featuredMatch)
            <div class="featured-match">
                <div class="featured-title">
                    <i class="fas fa-star"></i> Partido Destacado del Día
                </div>
                <div class="match-card">
                    <div class="match-teams">
                        <span class="team-name">{{ $featuredMatch->homeTeam ?? 'Equipo Local' }}</span>
                        @if(isset($featuredMatch->homeTeamLogo))
                            <img src="{{ asset($featuredMatch->homeTeamLogo) }}" class="team-logo" alt="Home">
                        @endif
                        <span class="match-time-inline">{{ $featuredMatch->time ?? '21:00' }}</span>
                        @if(isset($featuredMatch->awayTeamLogo))
                            <img src="{{ asset($featuredMatch->awayTeamLogo) }}" class="team-logo" alt="Away">
                        @endif
                        <span class="team-name">{{ $featuredMatch->awayTeam ?? 'Equipo Visitante' }}</span>
                    </div>
                    <div class="match-league">
                        <i class="fas fa-circle" style="color: white; font-size: 4px; vertical-align: middle;"></i> {{ $featuredMatch->competition ?? 'Liga' }} • {{ $featuredMatch->round ?? 'Jornada' }}
                    </div>
                </div>
            </div>
        @endif

        {{-- Groups Section --}}
        <div class="groups-section">
            <div class="section-title">
                <i class="fas fa-users"></i> Mis Grupos
            </div>

            {{-- Official Groups --}}
            @foreach($officialGroups as $group)
                <div class="group-card" onclick="window.location.href='{{ route('groups.show', $group) }}'">
                    <div class="group-status">
                        @if($group->pending ?? false)
                            <i class="fas fa-exclamation-triangle"></i>
                        @else
                            <i class="fas fa-check-circle"></i>
                        @endif
                    </div>
                    <div class="group-header">
                        <div class="group-avatar">
                            @if($group->competition && $group->competition->crest_url)
                                <img src="{{ asset('images/competitions/' . $group->competition->crest_url) }}" alt="{{ $group->name }}">
                            @else
                                <i class="fas fa-trophy" style="color: #000;"></i>
                            @endif
                        </div>
                        <div class="group-info">
                            <h3>{{ $group->name }}</h3>
                            <div class="group-stats">
                                <span><i class="fas fa-users"></i> {{ $group->users_count ?? $group->users->count() }} miembros</span>
                                @if($group->userRank)
                                    <div class="ranking-badge">
                                        <i class="fas fa-trophy"></i> Ranking: #{{ $group->userRank }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Amateur Groups --}}
            @foreach($amateurGroups as $group)
                <div class="group-card" onclick="window.location.href='{{ route('groups.show', $group) }}'">
                    <div class="group-status">
                        @if($group->pending ?? false)
                            <i class="fas fa-exclamation-triangle"></i>
                        @else
                            <i class="fas fa-check-circle"></i>
                        @endif
                    </div>
                    <div class="group-header">
                        <div class="group-avatar">
                            @if($group->competition && $group->competition->crest_url)
                                <img src="{{ asset('images/competitions/' . $group->competition->crest_url) }}" alt="{{ $group->name }}">
                            @else
                                <i class="fas fa-futbol" style="color: #000;"></i>
                            @endif
                        </div>
                        <div class="group-info">
                            <h3>{{ $group->name }}</h3>
                            <div class="group-stats">
                                <span><i class="fas fa-users"></i> {{ $group->users_count ?? $group->users->count() }} miembros</span>
                                @if($group->userRank)
                                    <div class="ranking-badge">
                                        <i class="fas fa-trophy"></i> Ranking: #{{ $group->userRank }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
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

        {{-- Bottom Menu --}}
        <div class="bottom-menu">
            <a href="{{ route('groups.index') }}" class="menu-item active">
                <div class="menu-icon"><i class="fas fa-users"></i></div>
                <div class="menu-label">Grupo</div>
            </a>
            <div class="menu-item" style="opacity: 0.5; cursor: not-allowed;">
                <div class="menu-icon"><i class="fas fa-globe"></i></div>
                <div class="menu-label">Comunidades</div>
            </div>
            <a href="#" id="openFeedbackModal" class="menu-item">
                <div class="menu-icon"><i class="fas fa-comment"></i></div>
                <div class="menu-label">Tu opinión</div>
            </a>
            <a href="{{ route('profile.edit') }}" class="menu-item">
                <div class="menu-icon"><i class="fas fa-user"></i></div>
                <div class="menu-label">Perfil</div>
            </a>
        </div>
    </div>

    <x-feedback-modal />

    {{-- Scripts mínimos necesarios --}}
    <script>
        // Botón activar notificaciones (si es necesario)
        const activarBtn = document.getElementById('activar-notificaciones');
        if (activarBtn && 'Notification' in window && Notification.permission === 'default') {
            activarBtn.style.display = 'block';
        }
    </script>
</x-app-layout>
