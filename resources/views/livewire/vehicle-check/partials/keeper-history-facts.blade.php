{{-- Shared by check-report.blade.php and plus-report.blade.php — keep in
     sync so the two report types never silently drift apart again. --}}
<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="user" />Keeper / Registration History</h3>
    <p class="text-vale-navy">Previous keepers: {{ $history?->previous_keepers ?? 'Unknown' }}</p>
    <p class="text-vale-navy mt-1">Plate changes: {{ $history?->plate_changes ?? 0 }}</p>
    @if (! empty($history?->plate_change_history))
        <ul class="text-xs text-gray-500 mt-1 space-y-0.5 list-disc list-inside">
            @foreach ($history->plate_change_history as $change)
                <li>{{ isset($change['date']) ? \Illuminate\Support\Carbon::parse($change['date'])->format('d M Y') : 'Unknown date' }}: {{ $change['from'] ?? 'Unknown' }} &rarr; {{ $change['to'] ?? 'Unknown' }}</li>
            @endforeach
        </ul>
    @endif
    <p class="text-vale-navy mt-1">Colour changes: {{ $history?->colour_changes ?? 0 }}</p>
    <p class="text-vale-navy mt-1">Vehicle identity checks: {{ $history?->vehicle_identity_checks ?? 0 }}</p>
    <p class="text-vale-navy mt-1">Logbook (V5C) reissues: {{ $history?->v5c_reissues ?? 'Unknown' }}</p>
    {{-- previous_searches is still captured and stored, just not shown —
         removed from the report display on request. --}}
    <p class="text-vale-navy mt-1">Imported: {{ is_null($history?->imported) ? 'Unavailable' : ($history->imported ? 'Yes' : 'No') }}</p>
    <p class="text-vale-navy mt-1">Previously exported: {{ is_null($history?->was_exported) ? 'Unavailable' : ($history->was_exported ? 'Yes' : 'No') }}</p>
    {{-- vrm_matches/vin_matches are deliberately not shown here — we don't
         have a confirmed definition from One Auto of what makes these
         false, and the one real example we've seen was false on a
         genuinely legitimate vehicle (most likely explained by its own
         recorded plate-change history). Still captured and stored below,
         just not displayed until that's understood. --}}
</div>
