@props([
    'image' => 'mobi-empty.png',
    'title',
    'description' => null,
    'imageAlt' => 'MobilityCloud assistant',
    'compact' => false,
])

@php
    $padding = $compact ? '2rem 1rem' : '2.5rem 1rem';
    $imageWidth = $compact ? '118px' : '148px';
@endphp

<div {{ $attributes->merge([
    'class' => 'mc-empty-state fi-section rounded-xl bg-white text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10',
    'style' => 'padding:' . $padding . ';',
]) }}>
    <img
        src="{{ asset('brand/' . $image) }}"
        alt="{{ $imageAlt }}"
        loading="lazy"
        style="width:min({{ $imageWidth }},42vw);height:auto;margin:0 auto .95rem;filter:drop-shadow(0 18px 28px rgba(37,99,235,.16));"
    >

    <h3 class="text-gray-950 dark:text-white" style="font-size:1rem;font-weight:750;margin:0 0 .28rem;">
        {{ $title }}
    </h3>

    @if($description)
        <p class="text-gray-500 dark:text-gray-400" style="font-size:.875rem;line-height:1.55;margin:0 auto {{ trim($slot) !== '' ? '1rem' : '0' }};max-width:36rem;">
            {{ $description }}
        </p>
    @endif

    @if(trim($slot) !== '')
        <div style="display:flex;gap:.55rem;justify-content:center;align-items:center;flex-wrap:wrap;">
            {{ $slot }}
        </div>
    @endif
</div>
