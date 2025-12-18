@props([
    'show' => false,
    'message' => '',
    'type' => 'warning', // warning, info, success, error
    'icon' => null
])

@if($show)
    <div class="notification-banner {{ $type }}" role="alert">
        @if($icon)
            <i class="fas fa-{{ $icon }} mr-2"></i>
        @else
            @switch($type)
                @case('warning')
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    @break
                @case('info')
                    <i class="fas fa-info-circle mr-2"></i>
                    @break
                @case('success')
                    <i class="fas fa-check-circle mr-2"></i>
                    @break
                @case('error')
                    <i class="fas fa-times-circle mr-2"></i>
                    @break
            @endswitch
        @endif

        @if($slot->isEmpty())
            {{ $message }}
        @else
            {{ $slot }}
        @endif
    </div>
@endif
