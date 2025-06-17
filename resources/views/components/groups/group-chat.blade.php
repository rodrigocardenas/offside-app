<div id="chatSection" class="bg-offside-dark rounded-lg p-6">
    <div class="bg-offside-primary bg-opacity-20 rounded-lg h-[300px] flex flex-col">
        <div class="flex-1 p-4 overflow-y-auto space-y-4">
            @foreach($group->chatMessages as $message)
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
            <form action="{{ route('chat.store', $group) }}" method="POST" class="flex items-center w-full space-x-2" id="chatForm" onsubmit="return false;">
                @csrf
                <div class="flex-1">
                    <input type="text"
                           name="message"
                           id="chatMessage"
                           class="w-full bg-offside-primary bg-opacity-40 border-0 rounded-lg text-white placeholder-gray-400 focus:ring-2 focus:ring-offside-secondary px-4 py-2"
                           placeholder="Escribe un mensaje..."
                           required>
                </div>
                <button type="submit"
                        id="sendMessageBtn"
                        title="Enviar mensaje"
                        class="bg-offside-primary text-white px-4 py-2 rounded-lg hover:bg-opacity-90 transition-colors flex items-center justify-center">
                    <span class="hidden sm:block">Enviar</span>
                    <i class="fas fa-paper-plane sm:hidden"></i>
                </button>
            </form>
        </div>
        @if($group->created_by === auth()->id())
            <div class="flex justify-center mt-2 mb-2">
                <button id="openRewardPenaltyModal" class="flex items-center px-4 py-2 bg-offside-primary text-white rounded-lg hover:bg-offside-secondary transition-colors focus:outline-none">
                    @if($group->reward_or_penalty)
                        <span class="truncate max-w-xs">{{ $group->reward_or_penalty }}</span>
                        <i class="fa-solid fa-edit ml-2"></i>
                    @else
                        <i class="fa-solid fa-plus"></i>
                        Agregar recompensa/penitencia
                    @endif
                </button>
            </div>
        @elseif($group->reward_or_penalty)
            <div class="flex justify-center mt-2 mb-2">
                <div class="px-4 py-2 bg-offside-primary text-white rounded-lg text-center">
                    <span class="font-bold text-offside-secondary">Recompensa/Penitencia:</span><br>
                    <span>{{ $group->reward_or_penalty }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
