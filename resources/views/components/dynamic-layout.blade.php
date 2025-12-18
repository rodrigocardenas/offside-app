@php
    $layoutName = $layout ?? 'mobile-light-layout';
@endphp

@if($layoutName === 'mobile-dark-layout')
    <x-mobile-dark-layout>
        {{ $slot }}
    </x-mobile-dark-layout>
@else
    <x-mobile-light-layout>
        {{ $slot }}
    </x-mobile-light-layout>
@endif
