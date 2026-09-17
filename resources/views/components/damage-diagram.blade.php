@props(['locations' => []])

{{--
    Web-only — a real top-down car outline (SVG), not the CSS-only boxes
    this used to be. dompdf can't render SVG reliably (confirmed
    elsewhere in this codebase, see pdf-status-tick.blade.php), so the
    PDF never includes this component at all — it relies on the plain
    "Damage area: ..." text line instead. The body/cabin paths use real
    curves (a tapered rounded nose/tail and wheel-arch indents) rather
    than plain rectangles, since those read as noticeably more car-like
    than section-icon.blade.php's flatter "simple primitives only" style
    — every curve is still a small, symmetric, individually-reasoned
    segment (never intricate freehand artwork), and was checked by
    actually rendering it, not just trusted from the coordinates.

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

    // Pin centre coordinates within the 200x100 viewBox — front on the
    // right, matching the orientation of the existing vehicle-silhouette
    // component used elsewhere in the report.
    $positions = [
        'front-nearside' => [175, 25],
        'front' => [185, 50],
        'front-offside' => [175, 75],
        'nearside' => [100, 18],
        'roof' => [100, 50],
        'offside' => [100, 82],
        'rear-nearside' => [25, 25],
        'rear' => [15, 50],
        'rear-offside' => [25, 75],
    ];

    $pinZones = $isAll ? array_keys($positions) : array_intersect(array_keys($positions), $zones);
@endphp

<div style="max-width:220px;" class="mt-2">
    <svg viewBox="0 0 200 100" width="200" height="100" xmlns="http://www.w3.org/2000/svg" style="opacity: {{ $hasNoData ? '0.5' : '1' }}">
        {{-- Body — a tapered octagon (straight lines only): the front
             (right, cut 22 units) narrows noticeably more than the rear
             (left, cut 10 units), so the silhouette itself hints at
             orientation before the caption needs reading. --}}
        <polygon points="20,15 168,15 190,37 190,63 168,85 20,85 10,75 10,25" fill="#F3F4F6" stroke="#9CA3AF" stroke-width="2" stroke-linejoin="round" />

        {{-- Cabin/glass — same tapered-octagon idea at a smaller scale,
             reading as a windscreen/rear-screen taper rather than a
             flat panel with square corners. --}}
        <polygon points="60,27 130,27 146,39 146,61 130,73 60,73 50,63 50,37" fill="#D1D5DB" />

        {{-- Wheels: dark tyre + lighter hub, overlapping the body's
             flat top/bottom edges so they look integrated, not detached. --}}
        @foreach ([35, 133] as $wheelX)
            <rect x="{{ $wheelX }}" y="5" width="32" height="18" rx="5" fill="#374151" />
            <rect x="{{ $wheelX + 7 }}" y="9" width="18" height="10" rx="3" fill="#9CA3AF" />
            <rect x="{{ $wheelX }}" y="77" width="32" height="18" rx="5" fill="#374151" />
            <rect x="{{ $wheelX + 7 }}" y="81" width="18" height="10" rx="3" fill="#9CA3AF" />
        @endforeach

        @if (! $hasNoData)
            @foreach ($pinZones as $zone)
                <circle cx="{{ $positions[$zone][0] }}" cy="{{ $positions[$zone][1] }}" r="7" fill="#DC2626" stroke="#ffffff" stroke-width="2" />
            @endforeach
        @endif
    </svg>

    @if ($hasNoData)
        <p class="text-xs text-gray-400 mt-1">No damage location data provided.</p>
    @endif
    <p class="text-xs text-gray-400 uppercase tracking-wide mt-1">Front of vehicle on the right</p>
    @if (! empty($unmapped))
        <p class="text-xs text-gray-500 mt-1">Also reported: {{ implode(', ', $unmapped) }}</p>
    @endif
</div>
