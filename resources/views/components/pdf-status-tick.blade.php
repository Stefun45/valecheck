@props(['ok' => true, 'size' => 24])

{{--
    dompdf does not reliably render inline SVG — confirmed empirically by
    comparing rendered PDF output byte-for-byte: the SVG version of this
    tick (status-tick.blade.php, used on the web report) produces a PDF
    content stream byte-identical to a completely blank page. This is a
    pure-CSS equivalent (rotated bordered divs, no SVG, no glyphs whose
    font coverage can't be verified) built specifically for dompdf.
--}}
@php
    $color = $ok ? '#16A34A' : '#CA8A04';
    $border = max(2, round($size * 0.09));
    $barThickness = max(2, round($size * 0.09));
@endphp

<div style="width:{{ $size }}px; height:{{ $size }}px; border-radius:50%; border:{{ $border }}px solid {{ $color }}; position:relative; box-sizing:border-box;">
    @if ($ok)
        @php
            $barLength = round($size * 0.45);
        @endphp
        <div style="position:absolute; left:{{ round($size * 0.27) }}px; top:{{ round($size * 0.42) }}px; width:{{ $barLength }}px; height:{{ round($barLength / 2) }}px; border-left:{{ $barThickness }}px solid {{ $color }}; border-bottom:{{ $barThickness }}px solid {{ $color }}; transform:rotate(-45deg);"></div>
    @else
        @php
            $barLength = round($size * 0.55);
        @endphp
        <div style="position:absolute; left:{{ round(($size - $barThickness) / 2) }}px; top:{{ round(($size - $barLength) / 2) }}px; width:{{ $barThickness }}px; height:{{ $barLength }}px; background:{{ $color }}; transform:rotate(45deg);"></div>
        <div style="position:absolute; left:{{ round(($size - $barThickness) / 2) }}px; top:{{ round(($size - $barLength) / 2) }}px; width:{{ $barThickness }}px; height:{{ $barLength }}px; background:{{ $color }}; transform:rotate(-45deg);"></div>
    @endif
</div>
