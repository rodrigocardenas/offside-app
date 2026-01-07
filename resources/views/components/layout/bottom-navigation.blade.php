@props(['activeItem' => 'grupo'])

<div class="bottom-menu">
    <a href="{{ route('groups.index') }}" class="menu-item {{ $activeItem === 'grupo' ? 'active' : '' }}" title="Mis Grupos">
        <div class="menu-icon"><i class="fas fa-users"></i></div>
        <div class="menu-label">Grupo</div>
    </a>
    {{-- <a href="{{ route('competitions.index') }}" class="menu-item {{ $activeItem === 'comunidades' ? 'active' : '' }}" title="Comunidades">
        <div class="menu-icon"><i class="fas fa-globe"></i></div>
        <div class="menu-label">Comunidades</div>
    </a> --}}
    {{-- add a markets link --}}
    <a href="{{ route('market.index') }}" class="menu-item {{ $activeItem === 'mercados' ? 'active' : '' }}" title="Tienda">
        <div class="menu-icon"><i class="fas fa-store"></i></div>
        <div class="menu-label">Tienda</div>
    </a>
    <button type="button" onclick="openFeedbackModal(event);" class="menu-item" title="Enviar Opinión">
        <div class="menu-icon"><i class="fas fa-comment"></i></div>
        <div class="menu-label">Tu opinión</div>
    </button>
    <a href="{{ route('profile.edit') }}" class="menu-item {{ $activeItem === 'perfil' ? 'active' : '' }}" title="Mi Perfil">
        <div class="menu-icon"><i class="fas fa-user-circle"></i></div>
        <div class="menu-label">Perfil</div>
    </a>

</div>

