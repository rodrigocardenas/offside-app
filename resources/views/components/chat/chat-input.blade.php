@props([
    'groupId',
    'placeholder' => 'Escribe un mensaje...'
])

<div class="p-3 border-t border-gray-200 bg-gray-50">
    <form id="chat-form-{{ $groupId }}"
          class="flex gap-2"
          onsubmit="sendChatMessage(event, {{ $groupId }}); return false;">

        @csrf

        <input
            type="text"
            id="chat-input-{{ $groupId }}"
            name="message"
            placeholder="{{ $placeholder }}"
            class="chat-input-field"
            required
            maxlength="500"
            autocomplete="off">

        <button
            type="submit"
            id="chat-send-btn-{{ $groupId }}"
            class="chat-send-button">
            <i class="fas fa-paper-plane"></i>
        </button>
    </form>

    {{-- Character counter (optional) --}}
    <div class="mt-1 text-right">
        <span id="char-counter-{{ $groupId }}" class="text-[10px] text-gray-400">
            0/500
        </span>
    </div>
</div>

@push('scripts')
<script>
    // Character counter
    const input{{ $groupId }} = document.getElementById('chat-input-{{ $groupId }}');
    const counter{{ $groupId }} = document.getElementById('char-counter-{{ $groupId }}');

    if (input{{ $groupId }} && counter{{ $groupId }}) {
        input{{ $groupId }}.addEventListener('input', function() {
            counter{{ $groupId }}.textContent = this.value.length + '/500';
        });
    }
</script>
@endpush
