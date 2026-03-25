@props([
    'user',
    'size' => 'small', // small, medium, large
    'showName' => false,
    'href' => null,
    'class' => '',
    'imgClass' => 'w-10 h-10 rounded-full object-cover'
])

@php
    // Get avatar URL from user model
    $avatarUrl = $user->getAvatarUrl($size);
    $srcset = $user->avatar_provider === 'cloudflare' ? $user->getAvatarSrcset() : '';
    
    // Size mapping
    $sizeMap = [
        'small' => 'w-10 h-10',
        'medium' => 'w-16 h-16',
        'large' => 'w-24 h-24',
    ];
    
    $defaultImgClass = $sizeMap[$size] ?? $sizeMap['small'];
    $finalImgClass = $imgClass ?: ($defaultImgClass . ' rounded-full object-cover');
@endphp

@if($href)
    <a href="{{ $href }}" class="inline-flex items-center gap-2 {{ $class }}">
        @if($user->avatar)
            <img 
                src="{{ $avatarUrl }}"
                @if($srcset) srcset="{{ $srcset }}" @endif
                alt="{{ $user->name }}"
                class="{{ $finalImgClass }}"
                loading="lazy"
            >
        @else
            <div class="{{ $finalImgClass }} bg-gradient-to-br from-teal-400 to-teal-500 flex items-center justify-center text-white font-semibold text-lg">
                {{ substr($user->name, 0, 1) }}
            </div>
        @endif
        
        @if($showName)
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</span>
        @endif
    </a>
@else
    <div class="inline-flex items-center gap-2 {{ $class }}">
        @if($user->avatar)
            <img 
                src="{{ $avatarUrl }}"
                @if($srcset) srcset="{{ $srcset }}" @endif
                alt="{{ $user->name }}"
                class="{{ $finalImgClass }}"
                loading="lazy"
            >
        @else
            <div class="{{ $finalImgClass }} bg-gradient-to-br from-teal-400 to-teal-500 flex items-center justify-center text-white font-semibold text-lg">
                {{ substr($user->name, 0, 1) }}
            </div>
        @endif
        
        @if($showName)
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</span>
        @endif
    </div>
@endif
