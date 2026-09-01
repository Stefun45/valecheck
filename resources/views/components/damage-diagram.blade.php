@props(['locations' => []])

{{--
    A top-down car outline with a pin marker at the damaged zone(s),
    rather than a grid of labelled boxes — built entirely from plain
    divs with inline styles (rounded rects for the body/wheels, small
    circles for pins, positioned with percentage left/top + a translate
    offset) so it renders identically on the web report and inside
    dompdf, matching report-verdict-badge/pdf-status-tick's "safe
    primitives only" approach. No SVG/CSS grid/flexbox.

    AutoCheck's damage_location_desc format isn't fully confirmed (see
    OneAutoMarketValuationProvider) — rather than hard-matching exact
    strings, each raw value is loosely matched by keyword (front/rear/
    near/off/roof/all) onto one of 9 zones. Anything that doesn't match
    any keyword is never silently dropped — it's listed as plain text
    underneath instead.
--}}
@php
    $normalize = fn (string $raw) => strtolower(preg_replace('/[^a-z]/i', '', $raw));

    $zoneOf = function (string $raw) use ($normalize) {
        $n = $normalize($raw);

        return match (true) {
            str_contains($n, 'front') && str_contains($n, 'near') => 'front-nearside',
            str_contains($n, 'front') && str_contains($n, 'off') => 'front-offside',
            str_contains($n, 'front') => 'front',
            str_contains($n, 'rear') && str_contains($n, 'near') => 'rear-nearside',
            str_contains($n, 'rear') && str_contains($n, 'off') => 'rear-offside',
            str_contains($n, 'rear') => 'rear',
            str_contains($n, 'near') => 'nearside',
            str_contains($n, 'off') => 'offside',
            str_contains($n, 'roof') => 'roof',
            str_contains($n, 'all') => 'all',
            default => null,
        };
    };

    $zones = collect($locations)->map($zoneOf)->filter()->unique()->values()->all();
    $unmapped = collect($locations)->reject(fn ($l) => $zoneOf($l) !== null)->values()->all();
    $isAll = in_array('all', $zones, true);
    $hasNoData = empty($locations);

    // Percentage position of each zone's pin within the 120x230 car body.
    $positions = [
        'front-nearside' => ['top' => 14, 'left' => 18],
        'front' => ['top' => 8, 'left' => 50],
        'front-offside' => ['top' => 14, 'left' => 82],
        'nearside' => ['top' => 50, 'left' => 4],
        'roof' => ['top' => 50, 'left' => 50],
        'offside' => ['top' => 50, 'left' => 96],
        'rear-nearside' => ['top' => 88, 'left' => 18],
        'rear' => ['top' => 94, 'left' => 50],
        'rear-offside' => ['top' => 88, 'left' => 82],
    ];

    $pinZones = $isAll ? array_keys($positions) : array_intersect(array_keys($positions), $zones);

    $damageColor = '#DC2626';
@endphp

<div style="max-width:220px; margin:8px 0 0;">
    <div style="width:150px; height:230px; margin:0 auto; position:relative; opacity:{{ $hasNoData ? '0.55' : '1' }};">
        {{-- Wheels --}}
        <div style="position:absolute; left:-2px; top:38px; width:10px; height:24px; border-radius:4px; background:#6B7280;"></div>
        <div style="position:absolute; right:-2px; top:38px; width:10px; height:24px; border-radius:4px; background:#6B7280;"></div>
        <div style="position:absolute; left:-2px; bottom:38px; width:10px; height:24px; border-radius:4px; background:#6B7280;"></div>
        <div style="position:absolute; right:-2px; bottom:38px; width:10px; height:24px; border-radius:4px; background:#6B7280;"></div>

        {{-- Body --}}
        <div style="position:absolute; left:15px; top:0; width:120px; height:230px; border-radius:60px; background:#F3F4F6; border:2px solid #D1D5DB;">
            <div style="position:absolute; left:20px; top:34px; width:80px; height:2px; background:#D1D5DB;"></div>
            <div style="position:absolute; left:20px; bottom:34px; width:80px; height:2px; background:#D1D5DB;"></div>

            @if ($hasNoData)
                <div style="position:absolute; left:0; top:0; width:100%; height:18px; background:#6B7280; color:#ffffff; font-size:7px; font-weight:bold; text-align:center; line-height:18px; letter-spacing:0.5px; border-radius:58px 58px 0 0;">
                    NO DATA
                </div>
            @else
                @foreach ($pinZones as $zone)
                    @php $pos = $positions[$zone]; @endphp
                    <div style="position:absolute; left:{{ $pos['left'] }}%; top:{{ $pos['top'] }}%; width:14px; height:14px; margin-left:-7px; margin-top:-7px; border-radius:50%; background:{{ $damageColor }}; border:2px solid #ffffff;"></div>
                @endforeach
            @endif
        </div>
    </div>

    <p style="font-size:8px; color:#9CA3AF; margin:6px 0 0; text-align:center; text-transform:uppercase; letter-spacing:0.5px;">Front of vehicle at top</p>
    @if (! empty($unmapped))
        <p style="font-size:9px; color:#6B7280; margin:4px 0 0;">Also reported: {{ implode(', ', $unmapped) }}</p>
    @endif
</div>
