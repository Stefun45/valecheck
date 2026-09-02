@props(['ok' => true, 'size' => 24])

{{--
    dompdf does not reliably render inline SVG — confirmed empirically by
    comparing rendered PDF output byte-for-byte: the SVG version of this
    tick (status-tick.blade.php, used on the web report) produces a PDF
    content stream byte-identical to a completely blank page. This is a
    pure-CSS equivalent built specifically for dompdf.

    Each stroke is built independently and pivoted around its own bottom
    edge (transform-origin: 50% 100%), positioned so that bottom edge sits
    exactly at the circle's centre (left:50%, bottom:50%, shifted back by
    half its own width). Rotating around that fixed point means the
    vertex never moves regardless of stroke length or angle — a
    guaranteed-centred construction, not one relying on eyeballed offsets.
--}}
@php
    $color = $ok ? '#16A34A' : '#CA8A04';
    $circleBorder = max(2, round($size * 0.09));
    $strokeWidth = max(3, round($size * 0.16));
@endphp

<div style="width:{{ $size }}px; height:{{ $size }}px; border-radius:50%; border:{{ $circleBorder }}px solid {{ $color }}; position:relative; box-sizing:border-box; margin:0 auto;">
    @if ($ok)
        @php
            $shortLength = round($size * 0.32);
            $longLength = round($size * 0.58);
        @endphp
        {{-- Short stroke: vertex to upper-left. Only the free (top) end is
             rounded — rounding the pivot end too carves a notch out of the
             vertex where the two strokes should meet as a sharp point,
             making the tick read as incomplete. --}}
        <div style="position:absolute; left:50%; bottom:50%; width:{{ $strokeWidth }}px; height:{{ $shortLength }}px; margin-left:-{{ round($strokeWidth / 2) }}px; background:{{ $color }}; border-radius:{{ round($strokeWidth / 2) }}px {{ round($strokeWidth / 2) }}px 0 0; transform-origin:50% 100%; transform:rotate(-45deg);"></div>
        {{-- Long stroke: vertex to upper-right. --}}
        <div style="position:absolute; left:50%; bottom:50%; width:{{ $strokeWidth }}px; height:{{ $longLength }}px; margin-left:-{{ round($strokeWidth / 2) }}px; background:{{ $color }}; border-radius:{{ round($strokeWidth / 2) }}px {{ round($strokeWidth / 2) }}px 0 0; transform-origin:50% 100%; transform:rotate(45deg);"></div>
    @else
        @php
            $crossLength = round($size * 0.55);
        @endphp
        <div style="position:absolute; left:50%; top:50%; width:{{ $strokeWidth }}px; height:{{ $crossLength }}px; margin-left:-{{ round($strokeWidth / 2) }}px; margin-top:-{{ round($crossLength / 2) }}px; background:{{ $color }}; border-radius:{{ round($strokeWidth / 2) }}px; transform-origin:50% 50%; transform:rotate(45deg);"></div>
        <div style="position:absolute; left:50%; top:50%; width:{{ $strokeWidth }}px; height:{{ $crossLength }}px; margin-left:-{{ round($strokeWidth / 2) }}px; margin-top:-{{ round($crossLength / 2) }}px; background:{{ $color }}; border-radius:{{ round($strokeWidth / 2) }}px; transform-origin:50% 50%; transform:rotate(-45deg);"></div>
    @endif
</div>
