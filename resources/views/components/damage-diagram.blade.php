@props(['locations' => []])

{{--
    Web-only — a real top-down car outline (SVG), not the CSS-only boxes
    this used to be. dompdf can't render SVG reliably (confirmed
    elsewhere in this codebase, see pdf-status-tick.blade.php), so the
    PDF never includes this component at all — it relies on the plain
    "Damage area: ..." text line instead. Built from simple primitives
    (rect/line/circle) rather than hand-authored path data, matching
    section-icon.blade.php's "no complex multi-curve paths" discipline.

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

    // Pin centre coordinates within the 120x200 viewBox.
    $positions = [
        'front-nearside' => [28, 20],
        'front' => [60, 12],
        'front-offside' => [92, 20],
        'nearside' => [14, 100],
        'roof' => [60, 100],
        'offside' => [106, 100],
        'rear-nearside' => [28, 180],
        'rear' => [60, 190],
        'rear-offside' => [92, 180],
    ];

    $pinZones = $isAll ? array_keys($positions) : array_intersect(array_keys($positions), $zones);
@endphp

<div style="max-width:160px;" class="mt-2">
    <svg viewBox="0 0 120 200" width="120" height="200" xmlns="http://www.w3.org/2000/svg" style="opacity: {{ $hasNoData ? '0.5' : '1' }}">
        <rect x="0" y="45" width="12" height="35" rx="4" fill="#4B5563" />
        <rect x="108" y="45" width="12" height="35" rx="4" fill="#4B5563" />
        <rect x="0" y="120" width="12" height="35" rx="4" fill="#4B5563" />
        <rect x="108" y="120" width="12" height="35" rx="4" fill="#4B5563" />

        <rect x="10" y="0" width="100" height="200" rx="45" ry="70" fill="#F3F4F6" stroke="#9CA3AF" stroke-width="2" />
        <line x1="25" y1="35" x2="95" y2="35" stroke="#D1D5DB" stroke-width="2" />
        <line x1="25" y1="165" x2="95" y2="165" stroke="#D1D5DB" stroke-width="2" />

        @if (! $hasNoData)
            @foreach ($pinZones as $zone)
                <circle cx="{{ $positions[$zone][0] }}" cy="{{ $positions[$zone][1] }}" r="7" fill="#DC2626" stroke="#ffffff" stroke-width="2" />
            @endforeach
        @endif
    </svg>

    @if ($hasNoData)
        <p class="text-xs text-gray-400 mt-1">No damage location data provided.</p>
    @endif
    <p class="text-xs text-gray-400 uppercase tracking-wide mt-1">Front of vehicle at top</p>
    @if (! empty($unmapped))
        <p class="text-xs text-gray-500 mt-1">Also reported: {{ implode(', ', $unmapped) }}</p>
    @endif
</div>
