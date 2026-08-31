{{-- Shared by check-report.blade.php and plus-report.blade.php — keep in
     sync so the two report types never silently drift apart again. --}}
<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="user" />Keeper / Registration History</h3>
    <p class="text-vale-navy">Previous keepers: {{ $history?->previous_keepers ?? 'Unknown' }}</p>
    <p class="text-vale-navy mt-1">Plate changes: {{ $history?->plate_changes ?? 0 }}</p>
    <p class="text-vale-navy mt-1">Colour changes: {{ $history?->colour_changes ?? 0 }}</p>
    <p class="text-vale-navy mt-1">Vehicle identity checks: {{ $history?->vehicle_identity_checks ?? 0 }}</p>
    <p class="text-vale-navy mt-1">Logbook (V5C) reissues: {{ $history?->v5c_reissues ?? 'Unknown' }}</p>
    <p class="text-vale-navy mt-1">Previous searches by other buyers/traders: {{ $history?->previous_searches ?? 'Unknown' }}</p>
    <p class="text-vale-navy mt-1">Imported: {{ is_null($history?->imported) ? 'Unavailable' : ($history->imported ? 'Yes' : 'No') }}</p>
    <p class="text-vale-navy mt-1">Previously exported: {{ is_null($history?->was_exported) ? 'Unavailable' : ($history->was_exported ? 'Yes' : 'No') }}</p>
    <p class="mt-1 {{ is_null($history?->vrm_matches) ? 'text-gray-400' : ($history->vrm_matches ? 'text-vale-navy' : 'text-vale-red font-semibold') }}">
        Registration matches records: {{ is_null($history?->vrm_matches) ? 'Unavailable' : ($history->vrm_matches ? 'Yes' : 'No') }}
    </p>
    <p class="mt-1 {{ is_null($history?->vin_matches) ? 'text-gray-400' : ($history->vin_matches ? 'text-vale-navy' : 'text-vale-red font-semibold') }}">
        VIN matches records: {{ is_null($history?->vin_matches) ? 'Unavailable' : ($history->vin_matches ? 'Yes' : 'No') }}
    </p>
</div>
