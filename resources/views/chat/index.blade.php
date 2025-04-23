<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Chat de {{ $group->name }}
            </h2>
            <a href="{{ route('groups.show', $group) }}" class="text-blue-600 hover:text-blue-800">
                Volver al grupo
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Mensajes del Chat -->
                    <div class="bg-gray-50 p-4 rounded-lg h-[600px] overflow-y-auto mb-4">
                        @foreach($messages as $message)
                            <div class="mb-4">
                                <div class="flex items-start space-x-2">
                                    <div class="flex-1">
                                        <div class="font-medium">{{ $message->user->name }}</div>
                                        <div class="text-gray-600">{{ $message->message }}</div>
                                        @if($message->question)
                                            <div class="text-sm text-blue-600">
                                                Re: {{ $message->question->title }}
                                            </div>
                                        @endif
                                        <div class="text-xs text-gray-500">
                                            {{ $message->created_at->format('d/m/Y H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Formulario para enviar mensajes -->
                    <form action="{{ route('chat.store', $group) }}" method="POST" class="flex space-x-2">
                        @csrf
                        <input type="text" name="message" class="flex-1 rounded-lg border-gray-300" placeholder="Escribe un mensaje..." required>
                        <x-primary-button>
                            Enviar
                        </x-primary-button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
