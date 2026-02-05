@props(['activeItem' => 'grupo'])

<div class="bottom-menu">
    <a href="{{ route('groups.index') }}" class="menu-item {{ $activeItem === 'grupo' ? 'active' : '' }}" data-item="grupo" title="{{ __('views.groups.title') }}">
        <div class="menu-icon"><i class="fas fa-users"></i></div>
        <div class="menu-label">{{ __('views.groups.title') }}</div>
    </a>
    <a href="{{ route('matches.calendar') }}" class="menu-item {{ $activeItem === 'partidos' ? 'active' : '' }}" data-item="partidos" title="Partidos">
        <div class="menu-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="menu-label">Partidos</div>
    </a>
    {{-- <a href="{{ route('competitions.index') }}" class="menu-item {{ $activeItem === 'comunidades' ? 'active' : '' }}" data-item="comunidades" title="Comunidades">
        <div class="menu-icon"><i class="fas fa-globe"></i></div>
        <div class="menu-label">Comunidades</div>
    </a> --}}
    {{-- add a markets link --}}
    <a href="{{ route('market.index') }}" class="menu-item {{ $activeItem === 'mercados' ? 'active' : '' }}" data-item="mercados" title="{{ __('views.market.title') }}">
        <div class="menu-icon"><i class="fas fa-store"></i></div>
        <div class="menu-label">{{ __('views.market.title') }}</div>
    </a>
    <button type="button" onclick="openFeedbackModal(event);" class="menu-item" data-item="feedback" title="{{ __('views.rankings.your_opinion') }}">
        <div class="menu-icon"><i class="fas fa-comment"></i></div>
        <div class="menu-label">{{ __('views.rankings.your_opinion') }}</div>
    </button>
    <a href="{{ route('profile.edit') }}" class="menu-item {{ $activeItem === 'perfil' ? 'active' : '' }}" data-item="perfil" title="{{ __('messages.profile') }}">
        <div class="menu-icon"><i class="fas fa-user-circle"></i></div>
        <div class="menu-label">{{ __('messages.profile') }}</div>
    </a>

</div>

