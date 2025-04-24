<x-app-layout>
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6">
        <div class="max-w-md mx-auto">
            <div class="bg-white bg-opacity-10 rounded-xl p-6">
                <div class="flex items-center justify-center mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-offside-light" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-center text-offside-light mb-6">Crear cuenta</h2>

                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-400 mb-2">Nombre</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-offside-primary @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-400 mb-2">Correo electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-offside-primary @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-400 mb-2">Contraseña</label>
                        <input id="password" type="password" name="password" required
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-offside-primary @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-400 mb-2">Confirmar contraseña</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                            class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-offside-primary">
                    </div>

                    <div class="flex items-center justify-between mt-6">
                        <a href="{{ route('login') }}" class="text-sm text-offside-light hover:text-white transition-colors">
                            ¿Ya tienes cuenta?
                        </a>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-orange-500 to-orange-400 text-white py-2 rounded-lg font-semibold hover:from-orange-600 hover:to-orange-500 transition-all">
                        Registrarse
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
