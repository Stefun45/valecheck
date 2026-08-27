@props(['ok' => true, 'size' => 32])

{{-- Same ring+tick shape as the brand mark (application-logo.blade.php),
     recoloured to signal status instead of the brand navy/red. Sized via
     width/height attributes rather than Tailwind classes so it renders
     identically in dompdf, which doesn't process Tailwind. --}}
@php
    $color = $ok ? '#16A34A' : '#CA8A04';
@endphp

<svg viewBox="0 0 40 40" width="{{ $size }}" height="{{ $size }}" fill="none" {{ $attributes }} aria-hidden="true">
    <circle cx="20" cy="20" r="17.5" fill="white" stroke="{{ $color }}" stroke-width="2.5"/>
    <path d="M11 20.5L16.5 27L29 12" stroke="{{ $color }}" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
