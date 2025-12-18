@props([
    'message',
    'isCurrentUser' => false
])

<div class="flex gap-3 items-start {{ $isCurrentUser ? 'flex-row-reverse' : '' }}">
    {{-- Avatar --}}
    <div class="message-avatar flex-shrink-0 {{ $isCurrentUser ? 'bg-offside-secondary' : 'bg-offside-primary' }}">
        {{ strtoupper(substr($message->user->name, 0, 2)) }}
    </div>

    {{-- Message Content --}}
    <div class="flex-1 min-w-0 {{ $isCurrentUser ? 'items-end' : 'items-start' }} flex flex-col">
        {{-- Header --}}
        <div class="flex items-center gap-2 mb-1 {{ $isCurrentUser ? 'flex-row-reverse' : '' }}">
            <span class="text-xs font-semibold text-gray-800 {{ $isCurrentUser ? 'text-offside-primary' : '' }}">
                {{ $isCurrentUser ? 'Tú' : $message->user->name }}
            </span>
            <span class="text-[10px] text-gray-500">
                {{ $message->created_at->diffForHumans() }}
            </span>
        </div>

        {{-- Message Text --}}
        <div class="max-w-[85%] {{ $isCurrentUser ? 'ml-auto' : 'mr-auto' }}">
            <div class="px-3 py-2 rounded-lg text-sm leading-relaxed {{ $isCurrentUser ? 'bg-offside-primary text-white rounded-tr-none' : 'bg-gray-100 text-gray-800 rounded-tl-none' }}">
                {{ $message->message }}
            </div>
        </div>

        {{-- Read indicator (optional) --}}
        @if($isCurrentUser && isset($message->read_at))
            <span class="text-[9px] text-gray-400 mt-0.5">
                <i class="fas fa-check-double"></i> Leído
            </span>
        @endif
    </div>
</div>
