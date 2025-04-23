<x-app-layout>
    <div class="min-h-screen bg-offside-dark text-white p-4 md:p-6">
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
    </div>
</x-app-layout>
