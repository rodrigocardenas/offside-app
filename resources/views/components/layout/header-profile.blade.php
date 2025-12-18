@props(['logoUrl' => null, 'altText' => 'Offside Club'])

<div class="header">
    <!-- Logo Left -->
    <div class="logo-container">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $altText }}" class="header-logo">
        @else
            <i class="fas fa-users"></i>
        @endif
    </div>

    <!-- Profile Button Right -->
    <div class="header-profile-btn">
        <button
            class="profile-btn"
            title="Perfil de usuario"
        >
            @auth
                @if(Auth::user()->avatar)
                    <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="profile-avatar">
                @else
                    <div class="profile-avatar-placeholder">{{ substr(Auth::user()->name, 0, 1) }}</div>
                @endif
            @else
                <i class="fas fa-user-circle"></i>
            @endauth
        </button>

        <!-- Dropdown Menu -->
        <div class="profile-dropdown">
            <a href="{{ route('profile.edit') }}" class="dropdown-item">
                <i class="fas fa-user"></i>
                <span>Ver Perfil</span>
            </a>
            <a href="{{ route('settings.index') }}" class="dropdown-item">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
        </div>
    </div>


</div>
