<x-app-layout>
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6">
        <div class="max-w-4xl mx-auto">
            <!-- Encabezado con imagen del grupo -->
            <div class="mb-8 text-center">
                <div class="flex items-center justify-center mb-4">
                    @if($group->logo)
                        <img src="{{ asset('storage/' . $group->logo) }}" alt="{{ $group->name }}" class="h-16 w-16 rounded-lg mr-4">
                    @endif
                    <h1 class="text-3xl font-bold">Ranking de {{ $group->name }}</h1>
                </div>
                <p class="text-offside-light">Clasificación actualizada</p>
            </div>

            <!-- Lista de clasificación -->
            <div class="bg-offside-primary bg-opacity-20 rounded-lg p-6">
                @if($rankings->isEmpty())
                    <div class="text-center py-8">
                        <p class="text-offside-light">Aún no hay puntuaciones para mostrar en este grupo.</p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach($rankings as $index => $user)
                            <div class="flex items-center bg-offside-dark bg-opacity-50 rounded-lg p-4 hover:bg-offside-primary hover:bg-opacity-30 transition-colors">
                                <!-- Posición -->
                                <div class="w-10 h-10 flex items-center justify-center rounded-full 
                                    @if($index === 0) bg-yellow-400 text-offside-dark
                                    @elseif($index === 1) bg-gray-300 text-offside-dark
                                    @elseif($index === 2) bg-amber-600
                                    @else bg-offside-primary
                                    @endif
                                    text-white font-bold mr-4">
                                    {{ $index + 1 }}
                                </div>
                                
                                <!-- Avatar del usuario -->
                                <div class="flex-shrink-0 mr-4">
                                    @if($user->avatar)
                                        <img src="{{ asset('storage/avatars/' . $user->avatar) }}" 
                                             alt="{{ $user->name }}" 
                                             class="w-12 h-12 rounded-full border-2 border-offside-primary">
                                    @else
                                        <div class="w-12 h-12 rounded-full bg-offside-primary flex items-center justify-center text-white font-bold">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Nombre y puntuación -->
                                <div class="flex-1">
                                    <h3 class="font-semibold">{{ $user->name }}</h3>
                                    <p class="text-sm text-offside-light">Miembro desde {{ $user->created_at->format('d/m/Y') }}</p>
                                </div>
                                
                                <!-- Puntuación -->
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-offside-primary">{{ $user->total_points ?? 0 }}</span>
                                    <p class="text-xs text-offside-light">puntos</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            
            <!-- Botón de volver al grupo -->
            <div class="mt-8 text-center">
                <a href="{{ route('groups.show', $group) }}" class="inline-flex items-center px-6 py-2 bg-offside-primary text-white rounded-md hover:bg-opacity-90 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver al grupo
                </a>
            </div>
        </div>
    </div>

    <!-- Barra de navegación inferior -->
    <div class="fixed bottom-0 left-0 right-0 bg-offside-dark border-t border-offside-primary">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-around items-center py-3">
                <a href="{{ route('groups.index') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="text-xs mt-1">Grupos</span>
                </a>
                <a href="{{ route('rankings.group', $group) }}" class="flex flex-col items-center text-white transition-colors">
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
    </div>
</x-app-layout>
