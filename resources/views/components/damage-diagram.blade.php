@props(['locations' => []])

{{--
    Built entirely from a plain HTML table with inline styles — no SVG, no
    CSS grid/flexbox — matching report-verdict-badge/pdf-status-tick's
    "safe primitives only" approach so this one component renders
    identically on the web report and inside dompdf.

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

    $rows = [
        ['front-nearside', 'front', 'front-offside'],
        ['nearside', 'roof', 'offside'],
        ['rear-nearside', 'rear', 'rear-offside'],
    ];

    $labels = [
        'front-nearside' => 'F/NS', 'front' => 'FRONT', 'front-offside' => 'F/OS',
        'nearside' => 'N/S', 'roof' => 'ROOF', 'offside' => 'O/S',
        'rear-nearside' => 'R/NS', 'rear' => 'REAR', 'rear-offside' => 'R/OS',
    ];

    $damageColor = '#DC2626';
    $normalColor = '#F3F4F6';
    $normalBorder = '#D1D5DB';
@endphp

{{--
    Always shown for a written-off vehicle, even when AutoCheck returned no
    location breakdown at all — a real, confirmed case (a genuine Cat S
    record where damage_location_items came back as an empty array), not a
    mapping bug. Showing a greyed-out diagram with a "no data" banner is
    more honest than hiding the diagram outright, which could otherwise
    read as "we forgot to check" rather than "the provider didn't return
    this detail".
--}}
<div style="max-width:220px; margin:8px 0 0; position:relative; opacity:{{ $hasNoData ? '0.6' : '1' }};">
    <table style="width:100%; border-collapse:separate; border-spacing:3px;">
        @foreach ($rows as $row)
            <tr>
                @foreach ($row as $cell)
                    @php $hit = ! $hasNoData && ($isAll || in_array($cell, $zones, true)); @endphp
                    <td style="text-align:center; vertical-align:middle; height:44px; font-size:8px; font-weight:bold; letter-spacing:0.5px; border-radius:5px; background:{{ $hit ? $damageColor : $normalColor }}; color:{{ $hit ? '#ffffff' : '#9CA3AF' }}; border:1px solid {{ $hit ? $damageColor : $normalBorder }};">
                        {{ $labels[$cell] }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
    @if ($hasNoData)
        <div style="position:absolute; top:0; left:0; width:100%; height:16px; background:#6B7280; color:#ffffff; font-size:7px; font-weight:bold; text-align:center; line-height:16px; letter-spacing:0.5px; border-radius:5px 5px 0 0;">
            NO DATA PROVIDED
        </div>
    @endif
    <p style="font-size:8px; color:#9CA3AF; margin:4px 0 0; text-transform:uppercase; letter-spacing:0.5px;">Front of vehicle at top</p>
    @if (! empty($unmapped))
        <p style="font-size:9px; color:#6B7280; margin:4px 0 0;">Also reported: {{ implode(', ', $unmapped) }}</p>
    @endif
</div>
