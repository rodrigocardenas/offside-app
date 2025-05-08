<x-app-layout>
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6">
        <div class="max-w-4xl mx-auto">
            <!-- Encabezado -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold mb-2">Editar Perfil</h1>
                <p class="text-offside-light">Actualiza tu información personal y preferencias.</p>
            </div>

            <!-- Formulario -->
            <div class="bg-offside-primary bg-opacity-20 rounded-lg p-6">
                @if(session('success'))
                    <div class="mb-4 p-4 bg-green-600 rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Avatar -->
                    <div class="mb-6 flex flex-col items-center">
                        <div class="relative mb-4">
                            @if($user->avatar)
                                <img src="{{ asset('storage/avatars/' . $user->avatar) }}" 
                                     alt="{{ $user->name }}" 
                                     class="w-32 h-32 rounded-full object-cover border-2 border-offside-primary">
                            @else
                                <div class="w-32 h-32 rounded-full bg-offside-primary flex items-center justify-center">
                                    <span class="text-4xl">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                            @endif
                            <label for="avatar" class="absolute bottom-0 right-0 bg-offside-primary p-2 rounded-full cursor-pointer hover:bg-offside-primary/90">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <input type="file" id="avatar" name="avatar" class="hidden" accept="image/*">
                            </label>
                        </div>
                        <p class="text-sm text-offside-light">Haz clic en el ícono para cambiar tu foto de perfil</p>
                    </div>

                    <!-- Nombre -->
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium mb-2">Nombre</label>
                        <input type="text" id="name" name="name" 
                               value="{{ old('name', $user->name) }}" 
                               class="w-full bg-offside-primary bg-opacity-20 border border-offside-primary rounded-md p-2 text-white focus:ring-2 focus:ring-offset-2 focus:ring-offside-primary focus:outline-none">
                        @error('name')
                            <p class="mt-1 text-red-400 text-sm">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium mb-2">Correo electrónico</label>
                        <input type="email" id="email" name="email" 
                               value="{{ old('email', $user->email) }}" 
                               class="w-full bg-offside-primary bg-opacity-20 border border-offside-primary rounded-md p-2 text-white focus:ring-2 focus:ring-offset-2 focus:ring-offside-primary focus:outline-none">
                        @error('email')
                            <p class="mt-1 text-red-400 text-sm">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end space-x-4">
                        <a href="{{ url()->previous() }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                            Cancelar
                        </a>
                        <button type="submit" class="px-4 py-2 bg-offside-primary text-white rounded-md hover:bg-offside-primary/90 transition-colors">
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="fixed bottom-0 left-0 right-0 bg-offside-dark border-t border-offside-primary">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-around items-center py-3">
                    <!-- <a href="{{ route('dashboard') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="text-xs mt-1">Inicio</span>
                    </a> -->
                    <a href="{{ route('groups.index') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="text-xs mt-1">Grupos</span>
                    </a>
                    <a href="{{ route('rankings.daily') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span class="text-xs mt-1">Ranking</span>
                    </a>
                    <a href="#" id="openFeedbackModal" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span class="text-xs mt-1">Tu opinión</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-xs mt-1">Perfil</span>
                    </a>
                </div>
            </div>
            <!-- Botón flotante del chat -->
        
    </div>


    @push('scripts')
    <script>
        // Mostrar vista previa de la imagen seleccionada
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'w-32 h-32 rounded-full object-cover border-2 border-offside-primary';
                    
                    const avatarContainer = document.querySelector('.relative.mb-4');
                    const existingImg = avatarContainer.querySelector('img, div');
                    if (existingImg) {
                        avatarContainer.replaceChild(img, existingImg);
                    } else {
                        avatarContainer.appendChild(img);
                    }
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
    @endpush
</x-app-layout>
