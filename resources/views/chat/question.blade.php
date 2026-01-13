<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Chat de la Pregunta: {{ $question->title }}
            </h2>
            <a href="{{ route('questions.show', $question) }}" class="text-blue-600 hover:text-blue-800">
                Volver a la pregunta
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Detalles de la Pregunta -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Detalles de la Pregunta</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-600">{{ $question->description }}</p>
                            <p class="text-sm text-gray-500 mt-2">
                                Disponible hasta: {{ @userTime($question->available_until) }}
                            </p>
                        </div>
                    </div>

                    <!-- Mensajes del Chat -->
                    <div class="bg-gray-50 p-4 rounded-lg h-[400px] overflow-y-auto mb-4">
                        @foreach($messages as $message)
                            <div class="mb-4">
                                <div class="flex items-start space-x-2">
                                    <div class="flex-1">
                                        <div class="font-medium">{{ $message->user->name }}</div>
                                        <div class="text-gray-600">{{ $message->message }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ @userTime($message->created_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Formulario para enviar mensajes -->
                    @if(!$question->answers()->where('user_id', auth()->id())->exists())
                        <form action="{{ route('chat.store', ['group' => $group, 'question' => $question]) }}" method="POST" class="flex space-x-2">
                            @csrf
                            <input type="text" name="message" class="flex-1 rounded-lg border-gray-300" placeholder="Escribe un mensaje..." required>
                            <x-primary-button>
                                Enviar
                            </x-primary-button>
                        </form>
                    @else
                        <p class="text-gray-500 text-center">Ya has respondido a esta pregunta.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
