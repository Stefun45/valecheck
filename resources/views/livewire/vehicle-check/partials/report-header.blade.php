{{-- Shared by check-report.blade.php, plus-report.blade.php and
     rebuild-report.blade.php — keep in sync so the three report types
     never silently drift apart again. --}}
@php
    $verdict = \App\Services\Reports\ReportStatusSummary::verdict($history);
@endphp

<div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-vale-red mb-2">
    <x-application-logo text-class="text-xs" />
    <span>&middot; {{ $productLabel }}</span>
</div>

<div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-4 min-w-0">
        <x-vehicle-silhouette :colour="$vehicle->colour" class="h-14 w-24 shrink-0" />
        <div class="min-w-0">
            <h1 class="font-display font-bold text-2xl text-vale-navy truncate">{{ $vehicle->description() ?: $check->registration }}</h1>
            <p class="text-gray-500 font-mono">{{ $check->registration }}</p>
        </div>
    </div>
    <x-report-verdict-badge :tone="$verdict['tone']" :label="$verdict['label']" :size="64" class="shrink-0" />
</div>

<div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-400 mt-4">
    <span class="inline-flex items-center gap-1"><x-section-icon name="shield" :size="12" />Data provided by Experian</span>
    <span class="inline-flex items-center gap-1"><x-section-icon name="identity" :size="12" />ICO Registered</span>
    <span class="inline-flex items-center gap-1"><x-section-icon name="document" :size="12" />DVLA &amp; DVSA Verified</span>
</div>
