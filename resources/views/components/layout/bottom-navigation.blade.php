@props(['activeItem' => 'grupo'])

<div class="bottom-menu">
    <a href="{{ route('groups.index') }}" class="menu-item {{ $activeItem === 'grupo' ? 'active' : '' }}">
        <div class="menu-icon"><i class="fas fa-users"></i></div>
        <div class="menu-label">Grupo</div>
    </a>
    <a href="{{ route('competitions.index') }}" class="menu-item {{ $activeItem === 'comunidades' ? 'active' : '' }}">
        <div class="menu-icon"><i class="fas fa-globe"></i></div>
        <div class="menu-label">Comunidades</div>
    </a>
    <button type="button" onclick="openFeedbackModal(event)" class="menu-item">
        <div class="menu-icon"><i class="fas fa-comment"></i></div>
        <div class="menu-label">Tu opinión</div>
    </button>
    <a href="{{ route('profile.edit') }}" class="menu-item {{ $activeItem === 'perfil' ? 'active' : '' }}">
        <div class="menu-icon"><i class="fas fa-user"></i></div>
        <div class="menu-label">Perfil</div>
    </a>
</div>
