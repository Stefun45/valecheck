@props(['colour' => null])

@php
    $hex = match (strtoupper($colour ?? '')) {
        'BLACK' => '#18181b',
        'WHITE' => '#e4e4e7',
        'SILVER' => '#a1a1aa',
        'GREY', 'GRAY' => '#71717a',
        'BLUE' => '#3b82f6',
        'RED' => '#dc2626',
        'GREEN' => '#16a34a',
        'YELLOW' => '#eab308',
        'ORANGE' => '#f97316',
        'PURPLE' => '#9333ea',
        'BROWN' => '#78350f',
        'BEIGE', 'CREAM' => '#d6d3d1',
        'GOLD', 'BRONZE' => '#a16207',
        'MAROON' => '#7f1d1d',
        'TURQUOISE' => '#14b8a6',
        'PINK' => '#ec4899',
        default => '#52525b',
    };
@endphp

{{--
    Generic representative silhouette, not a photo of the actual vehicle —
    neither DVLA nor our own data provides real vehicle imagery. Tinted to
    the reported colour purely as a quick visual cue.
--}}
<svg {{ $attributes->merge(['class' => 'h-16 w-28']) }} viewBox="0 0 64 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <path d="M4 22 L4 18 Q4 14 8 14 L18 14 L24 6 Q26 4 30 4 L46 4 Q50 4 52 8 L56 14 L58 14 Q62 14 62 18 L62 22 Z" fill="{{ $hex }}" opacity="0.9"/>
    <path d="M25 13 L29 6.5 Q30 5.5 31.5 5.5 L38 5.5 L38 13 Z" fill="black" opacity="0.15"/>
    <circle cx="16" cy="24" r="5" fill="#0a0a0b"/>
    <circle cx="16" cy="24" r="2" fill="#52525b"/>
    <circle cx="48" cy="24" r="5" fill="#0a0a0b"/>
    <circle cx="48" cy="24" r="2" fill="#52525b"/>
</svg>
