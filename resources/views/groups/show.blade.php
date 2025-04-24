<x-app-layout>
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6 pb-24">
        <!-- Botón para volver -->
        <div class="max-w-4xl mx-auto mb-4">
            <a href="{{ route('groups.index') }}" class="inline-flex items-center text-offside-light hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver a grupos
            </a>
        </div>

        <!-- Encabezado del grupo -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="bg-offside-primary rounded-lg p-4 mb-8">
                <h1 class="text-2xl font-bold text-center">Grupo "{{ $group->name }}"</h1>
                @if($group->competition)
                    <div class="text-center mt-2 text-offside-light">
                        <span class="inline-flex items-center">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            {{ $group->competition->name }} - {{ ucfirst($group->competition->type) }}
                            @if($group->competition->country)
                                ({{ $group->competition->country }})
                            @endif
                        </span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <!-- Ranking Histórico -->
                    <div class="bg-offside-dark rounded-lg p-6 mb-8">
                        <h2 class="text-xl font-bold mb-4">Ranking Histórico del grupo</h2>
                        <div class="space-y-3">
                            @foreach($rankings as $index => $ranking)
                                <div class="flex justify-between items-center bg-offside-primary bg-opacity-20 p-3 rounded-lg">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-yellow-400">🏆</span>
                                        <span>{{ $ranking->name }}</span>
                                    </div>
                                    <span class="font-semibold">{{ $ranking->total_points }} ponts.</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Pregunta del día -->
                    <div class="bg-offside-dark rounded-lg p-6">
                        <h2 class="text-xl font-bold mb-2">JORNADA {{ now()->format('d') }}</h2>
                        <h3 class="text-lg mb-6">Responde las preguntas de hoy:</h3>

                        @if($dailyQuestion && !$userAnswer)
                            <div class="space-y-6">
                                <h4 class="text-xl">{{ $dailyQuestion->title }}</h4>
                                <form action="{{ route('questions.answer', $dailyQuestion) }}" method="POST" class="space-y-4">
                                    @csrf
                                    @foreach($dailyQuestion->options as $option)
                                        <button type="submit"
                                                name="option_id"
                                                value="{{ $option->id }}"
                                                class="w-full text-center bg-offside-secondary hover:bg-offside-primary transition-colors p-4 rounded-lg">
                                            {{ $option->text }}
                                        </button>
                                    @endforeach
                                </form>
                            </div>
                        @elseif($dailyQuestion && $userAnswer)
                            <div class="space-y-6">
                                <h4 class="text-xl">{{ $dailyQuestion->title }}</h4>
                                <div class="space-y-4">
                                    @foreach($dailyQuestion->options as $option)
                                        <div class="p-4 rounded-lg {{
                                            $dailyQuestion->type === 'predictive' && $dailyQuestion->available_until > now()
                                                ? ($userAnswer->option_id == $option->id ? 'bg-blue-600' : 'bg-offside-primary bg-opacity-20')
                                                : ($option->is_correct ? 'bg-green-600' : ($userAnswer->option_id == $option->id ? 'bg-red-600' : 'bg-offside-primary bg-opacity-20'))
                                        }}">
                                            <div class="flex justify-between items-center">
                                                <div>
                                                    <span>{{ $option->text }}</span>
                                                    @if($dailyQuestion->type === 'predictive' && $dailyQuestion->available_until > now() && $userAnswer->option_id == $option->id)
                                                        <span class="text-xs ml-2 text-white">(Tu predicción)</span>
                                                    @endif
                                                </div>
                                                <div class="space-x-2">
                                                    @foreach($dailyQuestion->answers as $answer)
                                                        @if($answer->option_id == $option->id)
                                                            <span class="inline-block">{{ $answer->user->name }}</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="text-center text-gray-400">No hay preguntas disponibles para hoy.</p>
                        @endif
                    </div>
                </div>

                <!-- Chat del Grupo -->
                <div class="bg-offside-dark rounded-lg p-6">
                    <h2 class="text-xl font-bold mb-4">Chat del Grupo</h2>
                    <div class="bg-offside-primary bg-opacity-20 rounded-lg h-[600px] flex flex-col">
                        <div class="flex-1 p-4 overflow-y-auto space-y-4">
                            @foreach($group->chatMessages()->with('user')->latest()->get() as $message)
                                <div class="flex items-start space-x-3">
                                    <div class="flex-1">
                                        <div class="bg-offside-primary bg-opacity-40 rounded-lg p-3">
                                            <div class="font-medium text-sm">{{ $message->user->name }}</div>
                                            <div class="text-white">{{ $message->message }}</div>
                                            <div class="text-xs text-gray-400 mt-1">
                                                {{ $message->created_at->format('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="p-4 border-t border-offside-primary">
                            <form action="{{ route('chat.store', $group) }}" method="POST" class="flex space-x-2">
                                @csrf
                                <input type="text"
                                    name="message"
                                    class="flex-1 bg-offside-primary bg-opacity-40 border-0 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-offside-secondary"
                                    placeholder="Escribe un mensaje..."
                                    required>
                                <button type="submit" class="bg-offside-secondary text-white px-4 py-2 rounded-lg hover:bg-opacity-90 transition-colors">
                                    Enviar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menú inferior fijo -->
        <div class="fixed bottom-0 left-0 right-0 bg-offside-dark border-t border-offside-primary">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-around items-center py-3">
                    <a href="{{ route('dashboard') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span class="text-xs mt-1">Inicio</span>
                    </a>
                    <a href="{{ route('groups.index') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="text-xs mt-1">Grupos</span>
                    </a>
                    <a href="{{ route('questions.index') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-xs mt-1">Preguntas</span>
                    </a>
                    <a href="{{ route('rankings.daily') }}" class="flex flex-col items-center text-offside-light hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <span class="text-xs mt-1">Ranking</span>
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
    </div>
</x-app-layout>
