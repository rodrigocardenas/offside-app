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
            <form action="{{ route('chat.store', $group) }}" method="POST" class="flex items-center w-full space-x-2" id="chatForm">
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

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const chatForm = document.getElementById('chatForm');
                    const sendButton = document.getElementById('sendMessageBtn');
                    const messageInput = document.getElementById('chatMessage');
                    const chatContainer = document.querySelector('.overflow-y-auto');
                    let lastMessageTime = 0;

                    chatForm.addEventListener('submit', async function(e) {
                        e.preventDefault();

                        const now = Date.now();
                        if (formSubmissionTracker.isSubmitting('chatForm') || (now - lastMessageTime < 2000)) {
                            return;
                        }

                        formSubmissionTracker.startSubmission('chatForm');
                        lastMessageTime = now;
                        sendButton.disabled = true;
                        sendButton.classList.add('opacity-50', 'cursor-not-allowed');

                        try {
                            const formData = new FormData(chatForm);
                            const response = await fetch(chatForm.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            });

                            if (response.status === 429) {
                                console.log('Mensaje duplicado detectado');
                                return;
                            }

                            if (response.ok) {
                                const data = await response.json();
                                messageInput.value = '';
                                const messageHtml = `
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-1">
                                            <div class="bg-offside-primary bg-opacity-40 rounded-lg p-3">
                                                <div class="font-medium text-sm">${data.message.user.name}</div>
                                                <div class="text-white">${data.message.message}</div>
                                                <div class="text-xs text-gray-400 mt-1">
                                                    ${new Date(data.message.created_at).toLocaleString()}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                chatContainer.insertAdjacentHTML('beforeend', messageHtml);
                                chatContainer.scrollTop = chatContainer.scrollHeight;
                            } else {
                                throw new Error('Error al enviar el mensaje');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                        } finally {
                            setTimeout(() => {
                                formSubmissionTracker.endSubmission('chatForm');
                                sendButton.disabled = false;
                                sendButton.classList.remove('opacity-50', 'cursor-not-allowed');
                            }, 1000);
                        }
                    });

                    // Prevenir envío con Enter múltiple
                    messageInput.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' && formSubmissionTracker.isSubmitting('chatForm')) {
                            e.preventDefault();
                        }
                    });
                });
            </script>
        </div>
    </div>
</div>
@if($group->created_by === auth()->id())
    <div class="flex justify-center mb-2">
        @if($group->reward_or_penalty)
        <div class="flex justify-center mt-2 mb-2">
            <div class="px-4 py-1 bg-offside-primary bg-opacity-40 text-white rounded-lg text-center">
                <span class="font-bold text-offside-secondary">Recompensa/Penitencia:</span><br>
                <span>{{ $group->reward_or_penalty }} <button id="openRewardPenaltyModal" class=" text-white rounded-lg hover:bg-offside-secondary transition-colors focus:outline-none">
                        <i class="fa-solid fa-edit ml-2"></i>
                    </button>
                </span>
            </div>
        </div>
        @else
            <button id="openRewardPenaltyModal" class="flex items-center px-4 py-2 bg-offside-primary text-white rounded-lg hover:bg-offside-secondary transition-colors focus:outline-none">
                <i class="fa-solid fa-plus"></i>
                Agregar recompensa/penitencia
            </button>
        @endif
    </div>
@elseif($group->reward_or_penalty)
    <div class="flex justify-center mt-2 mb-2">
        <div class="px-4 py-2 bg-offside-primary text-white rounded-lg text-center">
            <span class="font-bold text-offside-secondary">Recompensa/Penitencia:</span><br>
            <span>{{ $group->reward_or_penalty }}</span>
        </div>
    </div>
@endif

<!-- Modal Premio/Penitencia -->
@if($group->created_by === auth()->id())
<div id="rewardPenaltyModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-offside-dark rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Premio o Penitencia</h3>
            <button id="closeRewardPenaltyModal" class="text-offside-light hover:text-white">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="rewardPenaltyForm">
            @csrf
            <div class="mb-4">
                <label for="reward_or_penalty" class="block text-sm font-medium mb-2">Escribe el premio para el ganador o la penitencia para el perdedor:</label>
                <textarea id="reward_or_penalty" name="reward_or_penalty" rows="4" class="w-full bg-offside-primary bg-opacity-20 border border-offside-primary rounded-md p-2 text-white" required>{{ $group->reward_or_penalty }}</textarea>
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" id="cancelRewardPenalty" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-offside-primary text-white rounded-md hover:bg-offside-primary/90">Guardar</button>
            </div>
        </form>
        <div id="rewardPenaltySuccess" class="hidden mt-4 text-green-500 font-bold">¡Guardado correctamente!</div>
    </div>
</div>
@endif


