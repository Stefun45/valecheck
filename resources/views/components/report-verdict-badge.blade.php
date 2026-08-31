@props(['tone' => 'good', 'label' => '', 'size' => 72])

{{--
    Deliberately built the same way as pdf-status-tick — plain CSS shapes
    via inline styles, no SVG, no font glyphs — so this one component
    renders identically in the browser AND in dompdf, with no separate
    "web version" and "PDF version" to keep in sync.
--}}
@php
    $color = match ($tone) {
        'good' => '#16A34A',
        'warning' => '#CA8A04',
        default => '#9CA3AF',
    };
    $circleBorder = max(3, round($size * 0.06));
    $strokeWidth = max(4, round($size * 0.1));
@endphp

<div {{ $attributes->merge(['style' => 'text-align:center;']) }}>
    <div style="width:{{ $size }}px; height:{{ $size }}px; border-radius:50%; border:{{ $circleBorder }}px solid {{ $color }}; position:relative; box-sizing:border-box; margin:0 auto;">
        @if ($tone === 'good')
            @php
                $shortLength = round($size * 0.32);
                $longLength = round($size * 0.58);
            @endphp
            {{-- Only the free (top) end of each stroke is rounded — rounding
                 the pivot end too would carve a notch out of the vertex
                 where the two strokes should meet as a sharp point,
                 making the tick read as incomplete. --}}
            <div style="position:absolute; left:50%; bottom:50%; width:{{ $strokeWidth }}px; height:{{ $shortLength }}px; margin-left:-{{ round($strokeWidth / 2) }}px; background:{{ $color }}; border-radius:{{ round($strokeWidth / 2) }}px {{ round($strokeWidth / 2) }}px 0 0; transform-origin:50% 100%; transform:rotate(-45deg);"></div>
            <div style="position:absolute; left:50%; bottom:50%; width:{{ $strokeWidth }}px; height:{{ $longLength }}px; margin-left:-{{ round($strokeWidth / 2) }}px; background:{{ $color }}; border-radius:{{ round($strokeWidth / 2) }}px {{ round($strokeWidth / 2) }}px 0 0; transform-origin:50% 100%; transform:rotate(45deg);"></div>
        @elseif ($tone === 'warning')
            @php
                $barHeight = round($size * 0.35);
                $dotSize = $strokeWidth;
            @endphp
            <div style="position:absolute; left:50%; top:18%; width:{{ $strokeWidth }}px; height:{{ $barHeight }}px; margin-left:-{{ round($strokeWidth / 2) }}px; background:{{ $color }}; border-radius:{{ round($strokeWidth / 2) }}px;"></div>
            <div style="position:absolute; left:50%; top:68%; width:{{ $dotSize }}px; height:{{ $dotSize }}px; margin-left:-{{ round($dotSize / 2) }}px; background:{{ $color }}; border-radius:50%;"></div>
        @else
            <div style="position:absolute; left:50%; top:50%; width:{{ round($size * 0.4) }}px; height:{{ $strokeWidth }}px; margin-left:-{{ round($size * 0.2) }}px; margin-top:-{{ round($strokeWidth / 2) }}px; background:{{ $color }}; border-radius:{{ round($strokeWidth / 2) }}px;"></div>
        @endif
    </div>
    <p style="margin:8px 0 0; font-weight:bold; color:{{ $color }}; text-transform:uppercase; letter-spacing:1px; font-size:{{ max(11, round($size * 0.17)) }}px;">{{ $label }}</p>
</div>
