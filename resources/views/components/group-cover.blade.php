@props([
    'group',
    'size' => 'medium', // small, medium, large
    'class' => '',
    'imgClass' => ''
])

@php
    // Get cover URL from group model
    $coverUrl = $group->getCoverImageUrl($size);
    $srcset = $group->cover_provider === 'cloudflare' ? $group->getCoverImageSrcset() : '';
    
    // Size mapping for different contexts
    $sizeMap = [
        'small' => 'h-20',      // Thumbnail
        'medium' => 'h-40',     // List view
        'large' => 'h-64',      // Detail view
    ];
    
    $defaultHeight = $sizeMap[$size] ?? $sizeMap['medium'];
    $finalImgClass = $imgClass ?: ($defaultHeight . ' w-full object-cover');
@endphp

@if($group->cover_image || $group->cover_cloudflare_id)
    <img 
        src="{{ $coverUrl }}"
        @if($srcset) srcset="{{ $srcset }}" @endif
        alt="{{ $group->name }} cover"
        class="rounded-lg {{ $finalImgClass }} {{ $class }}"
        loading="lazy"
    >
@else
    <div class="rounded-lg {{ $finalImgClass }} {{ $class }} bg-gradient-to-br from-slate-300 to-slate-400 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center">
        <div class="text-center">
            <svg class="w-16 h-16 mx-auto text-slate-500 dark:text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">{{ $group->name }}</p>
        </div>
    </div>
@endif
