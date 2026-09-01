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

    // Pin centre coordinates within the 100x200 viewBox.
    $positions = [
        'front-nearside' => [25, 25],
        'front' => [50, 15],
        'front-offside' => [75, 25],
        'nearside' => [18, 100],
        'roof' => [50, 100],
        'offside' => [82, 100],
        'rear-nearside' => [25, 175],
        'rear' => [50, 185],
        'rear-offside' => [75, 175],
    ];

    $pinZones = $isAll ? array_keys($positions) : array_intersect(array_keys($positions), $zones);
@endphp

<div style="max-width:130px;" class="mt-2">
    <svg viewBox="0 0 100 200" width="100" height="200" xmlns="http://www.w3.org/2000/svg" style="opacity: {{ $hasNoData ? '0.5' : '1' }}">
        {{-- Body — moderate corner rounding leaves real flat side walls
             (unlike a heavily-rounded ellipse) for the wheels to sit
             flush against, so they read as attached rather than
             floating. --}}
        <rect x="15" y="10" width="70" height="180" rx="22" fill="#F3F4F6" stroke="#9CA3AF" stroke-width="2" />

        {{-- Cabin/roof glass panel — a distinct shaded shape rather
             than bare lines floating in empty space. --}}
        <rect x="27" y="52" width="46" height="96" rx="14" fill="#D1D5DB" />

        {{-- Wheels: dark tyre + lighter hub, overlapping the body's
             flat sides so they look integrated, not detached. --}}
        @foreach ([35, 133] as $wheelY)
            <rect x="5" y="{{ $wheelY }}" width="18" height="32" rx="5" fill="#374151" />
            <rect x="9" y="{{ $wheelY + 7 }}" width="10" height="18" rx="3" fill="#9CA3AF" />
            <rect x="77" y="{{ $wheelY }}" width="18" height="32" rx="5" fill="#374151" />
            <rect x="81" y="{{ $wheelY + 7 }}" width="10" height="18" rx="3" fill="#9CA3AF" />
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
    <p class="text-xs text-gray-400 uppercase tracking-wide mt-1">Front of vehicle at top</p>
    @if (! empty($unmapped))
        <p class="text-xs text-gray-500 mt-1">Also reported: {{ implode(', ', $unmapped) }}</p>
    @endif
</div>
