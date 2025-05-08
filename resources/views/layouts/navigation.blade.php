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

            <!-- Settings Button -->
            <!-- <div class="flex items-center">
                <button class="p-2 rounded-full bg-offside-dark hover:bg-offside-primary transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>
            </div> -->
            <!-- User Dropdown -->
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
        </div>
    </div>
</nav>
