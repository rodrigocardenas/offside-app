<nav class="bg-transparent">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="{{ route('groups.index') }}" class="text-white font-bold text-2xl flex items-center">
                        <img src="/images/logo-offside.png" alt="Offside Club" class="h-10 w-10 mr-2">
                        <span class="text-offside-light">Offside Club</span>
                    </a>
                </div>
            </div>

            <!-- Botón de Instalación -->
            <div class="flex items-center mr-2" id="installButtonContainer" style="display: none;">
                <button id="installButtonNav" class="p-2 rounded-full bg-offside-dark hover:bg-offside-primary transition-colors" title="Instalar aplicación">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </button>
            </div>
            <!-- User Dropdown -->
            @if(Auth::check())
            <div class="relative ml-3">
                <div>
                    <button type="button" class="flex items-center max-w-xs rounded-full bg-offside-primary text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offside-primary" id="user-menu" aria-expanded="false" aria-haspopup="true">
                        <span class="sr-only">Abrir menú de usuario</span>
                        @if(Auth::user()->avatar)
                            <img class="h-8 w-8 rounded-full" src="{{ asset('storage/avatars/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}">
                        @else
                            <div class="h-8 w-8 rounded-full bg-offside-primary flex items-center justify-center text-white">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                        @endif
                    </button>
                </div>
                <!--
                  Dropdown menu, show/hide based on menu state.
                -->
                <div class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-offside-dark ring-1 ring-black ring-opacity-5 focus:outline-none hidden" role="menu" aria-orientation="vertical" aria-labelledby="user-menu" tabindex="-1">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-offside-light hover:bg-offside-primary hover:text-white" role="menuitem" tabindex="-1" id="user-menu-item-0">
                        Tu perfil
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="block w-full text-left">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-sm text-offside-light hover:bg-offside-primary hover:text-white" role="menuitem" tabindex="-1" id="user-menu-item-2">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</nav>
