@props(['logoUrl' => null, 'altText' => 'Offside Club'])

@php
    $themeMode = auth()->user()->theme_mode ?? 'light';
    $isDarkHeader = $themeMode === 'dark';
    $headerTitleColor = $isDarkHeader ? '#f1fff8' : '#333333';
@endphp

<div class="header">
    <!-- Logo Left -->
    <div class="logo-container">
        @if($logoUrl)
            <a href="{{ route('groups.index') }}">
                <img src="{{ $logoUrl }}" alt="{{ $altText }}" class="header-logo">
            </a>
        @else
            <a href="{{ route('groups.index') }}" style="color: #00deb0; font-size: 20px; text-decoration: none;">
                <i class="fas fa-arrow-left"></i>
            </a>
        @endif
    </div>

    <!-- Center Title - Show if we're in a group page -->
    @if(View::hasSection('navigation-logo'))
        <div style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);">
            <img src="@yield('navigation-logo')" style="width: 32px; height: 32px; border-radius: 50%; background-color: white; object-fit: cover;"/>
        </div>
    @elseif(View::hasSection('navigation-title'))
        <div style="position: sticky; left: 50%; top: 50%; transform: translate(-50%, -50%); font-size: 1rem; font-weight: 600; color: {{ $headerTitleColor }};">
            @yield('navigation-title', 'Offside Club')
        </div>
    @endif

    <!-- Profile Button Right -->
    <div class="header-profile-btn">
        <button
            class="profile-btn"
            title="{{ __('views.profile.title') }}"
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
                <span>{{ __('views.profile.view_profile') }}</span>
            </a>
            <a href="{{ route('settings.index') }}" class="dropdown-item">
                <i class="fas fa-cog"></i>
                <span>{{ __('messages.settings') }}</span>
            </a>
            {{-- <form method="POST" action="{{ route('logout') }}" class="block w-full">
                @csrf
                <button type="submit" class="dropdown-item w-full text-left" style="border: none;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar sesión</span>
                </button>
            </form> --}}
        </div>
    </div>

    <script>
        // Cerrar dropdown al hacer click fuera / Close dropdown when clicking outside
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
