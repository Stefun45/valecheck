@props(['locations' => []])

{{--
    Web-only - real licensed line-art photography-style diagrams (SVG),
    not hand-drawn shapes. dompdf can't render SVG reliably (confirmed
    elsewhere in this codebase, see pdf-status-tick.blade.php), so the
    PDF never includes this component at all - it relies on the plain
    "Damage area: ..." text line instead.

    Assets: resources sourced from a Shutterstock vector (licence held
    by ValeCheck), cropped into five independent angle files under
    public/images/damage-diagram/ - front, rear, side, front-three-
    quarter, rear-three-quarter. Only one angle is ever shown at once;
    there's no single view that can show every possible zone at once,
    so we pick whichever angle actually covers the reported zone(s) and
    fall back to a generic three-quarter shot (no pins) for anything
    that doesn't cleanly fit one angle. Nothing is ever hidden from the
    customer by this - the full "Damage area: ..." text line elsewhere
    on the report always lists every reported location regardless of
    what this illustration can show.

    Nearside/offside placement on the front and rear images is derived
    from camera-facing geometry, not guessed: nearside is the kerb side
    (the driver's own left, sat facing forward). A front-on photo faces
    the car - like two people facing each other, left/right swap - so
    nearside (the car's own left) falls on the RIGHT of a front photo.
    A rear photo looks the same way the car faces - like standing
    behind someone - so nearside falls on the LEFT of a rear photo.

    The side-view asset is a plain, near-symmetric profile silhouette;
    there's no reliable visual cue (in outline art with no visible fuel
    flap/exhaust asymmetry) to know for certain which physical side it
    depicts. Rather than guess, we treat it as generic and define our
    own consistent rule: shown as downloaded = nearside, horizontally
    mirrored = offside. The on-screen text label is what actually
    states which side it is - the image is illustrative only, exactly
    like the always-present "Damage area: ..." text line next to it.

    AutoCheck's damage_location_desc format isn't fully confirmed (see
    OneAutoMarketValuationProvider) - rather than hard-matching exact
    strings, each raw value is loosely matched by keyword (front/rear/
    near/off/roof/all) onto one of 9 zones. Anything that doesn't match
    any keyword is never silently dropped - it's listed as plain text
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

    $zoneLabel = fn (string $zone) => ucwords(str_replace('-', ' ', $zone));

    $zones = collect($locations)->map($zoneOf)->filter()->unique()->values()->all();
    $unmapped = collect($locations)->reject(fn ($l) => $zoneOf($l) !== null)->values()->all();
    $isAll = in_array('all', $zones, true);
    $hasNoData = empty($locations);

    // Pin position as [left%, top%] within each image's own box.
    $frontPins = ['front-offside' => [12, 62], 'front' => [50, 40], 'front-nearside' => [88, 62]];
    $rearPins = ['rear-nearside' => [12, 68], 'rear' => [50, 40], 'rear-offside' => [88, 68]];
    // Nearside/offside sit a little apart (not identical) so that the
    // rare case of both being reported together still shows two
    // distinct pins instead of one hiding the other.
    $sidePins = ['nearside' => [42, 60], 'offside' => [56, 60], 'roof' => [45, 15]];

    $frontZones = array_intersect($zones, array_keys($frontPins));
    $rearZones = array_intersect($zones, array_keys($rearPins));
    $sideZones = array_intersect($zones, array_keys($sidePins));

    if (! $isAll && ! empty($frontZones)) {
        $view = 'front';
        $image = 'front.svg';
        $shownZones = $frontZones;
        $pins = array_intersect_key($frontPins, array_flip($shownZones));
        $mirror = false;
    } elseif (! $isAll && ! empty($rearZones)) {
        $view = 'rear';
        $image = 'rear.svg';
        $shownZones = $rearZones;
        $pins = array_intersect_key($rearPins, array_flip($shownZones));
        $mirror = false;
    } elseif (! $isAll && ! empty($sideZones)) {
        $view = 'side';
        $image = 'side.svg';
        $shownZones = $sideZones;
        $pins = array_intersect_key($sidePins, array_flip($shownZones));
        // Nearside and offside can't both be shown correctly on one
        // profile image - if both are reported, default to the
        // unmirrored (nearside) image and leave offside to the text
        // note below rather than mirror incorrectly for either.
        $mirror = in_array('offside', $sideZones, true) && ! in_array('nearside', $sideZones, true);
    } else {
        $view = 'generic';
        $image = 'front-three-quarter.svg';
        $pins = [];
        $shownZones = [];
        $mirror = false;
    }

    $otherZones = array_diff($zones, $shownZones, ['all']);
    $alsoReported = implode(', ', array_merge(array_map($zoneLabel, $otherZones), $unmapped));
@endphp

<div class="mt-2" style="max-width:260px;">
    <div class="relative" style="{{ $mirror ? 'transform:scaleX(-1);' : '' }} opacity:{{ $hasNoData ? '0.4' : '1' }};" data-view="{{ $view }}">
        <img src="{{ asset('images/damage-diagram/'.$image) }}" alt="Diagram of the vehicle's {{ $view }}" class="block w-full h-auto select-none" draggable="false">

        @if (! $hasNoData)
            @foreach ($pins as $zone => [$left, $top])
                <span
                    data-zone="{{ $zone }}"
                    title="{{ $zoneLabel($zone) }}"
                    class="absolute rounded-full bg-vale-red border-2 border-white shadow"
                    style="left:{{ $left }}%; top:{{ $top }}%; width:14px; height:14px; margin-left:-7px; margin-top:-7px;"
                ></span>
            @endforeach
        @endif
    </div>

    @if ($hasNoData)
        <p class="text-xs text-gray-400 mt-1">No damage location data provided.</p>
    @endif
    <p class="text-xs text-gray-400 mt-1">Illustrative diagram - actual damage may vary.</p>
    @if ($alsoReported !== '')
        <p class="text-xs text-gray-500 mt-1">Also reported: {{ $alsoReported }}</p>
    @endif
</div>
