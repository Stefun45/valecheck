{{-- Shared by pdf/check-report.blade.php and pdf/plus-report.blade.php —
     keep in sync so the two report types never silently drift apart again. --}}
<div class="section">
    <div class="section-title">Keeper / Registration History</div>
    <p>Previous keepers: {{ $history?->previous_keepers ?? 'Unknown' }}</p>
    <p>Plate changes: {{ $history?->plate_changes ?? 0 }}</p>
    <p>Colour changes: {{ $history?->colour_changes ?? 0 }}</p>
    <p>Vehicle identity checks: {{ $history?->vehicle_identity_checks ?? 0 }}</p>
    <p>Logbook (V5C) reissues: {{ $history?->v5c_reissues ?? 'Unknown' }}</p>
    <p>Previous searches by other buyers/traders: {{ $history?->previous_searches ?? 'Unknown' }}</p>
    <p>Imported: {{ is_null($history?->imported) ? 'Unavailable' : ($history->imported ? 'Yes' : 'No') }}</p>
    <p>Previously exported: {{ is_null($history?->was_exported) ? 'Unavailable' : ($history->was_exported ? 'Yes' : 'No') }}</p>
    {{-- vrm_matches/vin_matches deliberately not shown — see the web
         partial's comment for why. --}}
</div>
