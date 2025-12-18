@props([
    'groupId',
    'messages' => collect(),
    'maxHeight' => 'max-h-[400px]'
])

<div class="mx-4 mt-4 bg-white rounded-2xl border border-gray-300 shadow-card">
    {{-- Header --}}
    <div class="flex items-center gap-2 p-4 border-b border-gray-200">
        <i class="fas fa-comments text-offside-primary text-lg"></i>
        <h3 class="text-base font-semibold text-gray-800">Chat del Grupo</h3>
        <span class="ml-auto text-xs text-gray-500">{{ $messages->count() }} mensajes</span>
    </div>

    {{-- Messages Container --}}
    <div id="chat-messages-{{ $groupId }}"
         class="{{ $maxHeight }} overflow-y-auto p-4 space-y-3"
         data-group-id="{{ $groupId }}">

        @forelse($messages as $message)
            <x-chat.chat-message
                :message="$message"
                :is-current-user="$message->user_id == auth()->id()"
            />
        @empty
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-comments text-4xl mb-3 opacity-50"></i>
                <p class="text-sm">No hay mensajes aún</p>
                <p class="text-xs mt-1">¡Sé el primero en escribir!</p>
            </div>
        @endforelse
    </div>

    {{-- Input --}}
    <x-chat.chat-input :group-id="$groupId" />
</div>

{{-- Auto-scroll script --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatContainer = document.getElementById('chat-messages-{{ $groupId }}');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    });
</script>
@endpush
