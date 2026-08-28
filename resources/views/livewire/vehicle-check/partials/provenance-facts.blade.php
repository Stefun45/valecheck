{{-- Shared by check-report.blade.php and plus-report.blade.php — keep in
     sync so the two report types never silently drift apart again.

     Each fact is genuinely three-way: unavailable (provider didn't return
     this section — grey, never read as "clean"), found (red), or checked
     and clean (navy). Collapsing "unavailable" into "clean" is exactly
     the bug that took VehicleMatic out of production. --}}
<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="warning" />Write-Off History</h3>
    @if (is_null($history))
        <p class="text-gray-400">Write-off data unavailable.</p>
    @elseif ($history->isWrittenOff())
        <p class="text-vale-red font-semibold">Category {{ $history->write_off_category }} recorded</p>
        <p class="text-sm text-gray-500 mt-1">Date: {{ optional($history->write_off_date)->format('d M Y') ?? 'Unknown' }}</p>
    @else
        <p class="text-vale-navy">No write-off history recorded.</p>
    @endif
</div>

<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="finance" />Finance</h3>
    @if (is_null($history?->finance_marker))
        <p class="text-gray-400">Finance data unavailable.</p>
    @elseif ($history->finance_marker)
        <p class="text-vale-red font-semibold">Finance marker detected</p>
    @else
        <p class="text-vale-navy">No finance marker found</p>
    @endif
</div>

<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="shield" />Stolen / Scrapped</h3>
    @if (is_null($history?->stolen_marker))
        <p class="text-gray-400">Stolen check unavailable.</p>
    @elseif ($history->stolen_marker)
        <p class="text-vale-red font-semibold">Stolen: Marker found</p>
    @else
        <p class="text-vale-navy">Stolen: No marker found</p>
    @endif
    @if (is_null($history?->scrapped_marker))
        <p class="text-gray-400 mt-1">Scrapped check unavailable.</p>
    @elseif ($history->scrapped_marker)
        <p class="text-vale-red font-semibold mt-1">Scrapped: Marker found</p>
    @else
        <p class="text-vale-navy mt-1">Scrapped: No marker found</p>
    @endif
</div>

<p class="text-xs text-gray-400 sm:col-span-2">Vehicle identity and provenance data provided by Experian.</p>
