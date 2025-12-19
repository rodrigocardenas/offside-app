@props(['logoUrl' => null, 'altText' => 'Offside Club'])

<div class="header">
    <!-- Logo Left -->
    <div class="logo-container">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $altText }}" class="header-logo">
        @else
            <a href="{{ route('groups.index') }}" class="font-bold text-lg flex items-center" style="color: #333;">
                <i class="fas fa-arrow-left" style="color: #00deb0; font-size: 20px; cursor: pointer;"></i>
            </a>
        @endif
    </div>

    <!-- Center Title - Show if we're in a group page -->
    @if(View::hasSection('navigation-logo'))
        <div class="flex-1 flex justify-center items-center" style="position: absolute; left: 50%; transform: translateX(-50%);">
            <img src="@yield('navigation-logo')" class="h-8 w-8 rounded-full" style="background-color: white"/>
        </div>
    @elseif(View::hasSection('navigation-title'))
        <div class="flex-1 flex justify-center items-center" style="position: absolute; left: 50%; transform: translateX(-50%);">
            <span style="font-size: 1rem; font-weight: 600; color: #333;">@yield('navigation-title', '')</span>
        </div>
    @endif

    <!-- Profile Button Right -->
    <div class="header-profile-btn">
        <button
            class="profile-btn"
            title="Perfil de usuario"
            id="user-menu"
            onclick="document.querySelector('.profile-dropdown').classList.toggle('hidden')"
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
        <div class="profile-dropdown hidden">
            <a href="{{ route('profile.edit') }}" class="dropdown-item">
                <i class="fas fa-user"></i>
                <span>Ver Perfil</span>
            </a>
            <a href="{{ route('settings.index') }}" class="dropdown-item">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="block w-full">
                @csrf
                <button type="submit" class="dropdown-item w-full text-left" style="border: none;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar sesión</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', function(e) {
            const header = document.querySelector('.header');
            const dropdown = document.querySelector('.profile-dropdown');
            const btn = document.getElementById('user-menu');

            if (header && dropdown && !header.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
</div>
