{{-- Free-preview teaser for MOT & Mileage, used only in the unpaid "is this
     your vehicle?" previews (homepage widget + start-check). It replaces
     the full mot-history-table partial there, which otherwise gave away
     the entire MOT history — including every advisory — before checkout.
     Still uses the vehicle's own real MOT history for a one-line pass/fail
     summary, since that data was already fetched for the free preview;
     only the full per-test breakdown is held back. The genuinely paid
     report keeps using mot-history-table.blade.php directly and is
     untouched by this partial. --}}
@php
    $motTests = collect($history?->mot_history ?? []);
    $totalTests = $motTests->count();
    $failedTests = $motTests->filter(fn ($test) => isset($test['result']) && str_contains(strtolower($test['result']), 'fail'))->count();
    $passedTests = $totalTests - $failedTests;
@endphp
<div class="relative bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="calendar" />MOT &amp; Mileage</h3>
    <p class="text-sm text-gray-600 leading-relaxed">
        @if ($totalTests > 0)
            {{ $totalTests }} MOT{{ $totalTests === 1 ? '' : 's' }} on record &mdash; {{ $passedTests }} passed, {{ $failedTests }} failed.
        @else
            No MOT history on record yet.
        @endif
    </p>
    <p class="flex items-center gap-1 text-xs font-bold uppercase tracking-wide mt-3 text-vale-navy">
        <x-section-icon name="lock" size="10" />
        Full history included in Check
    </p>
</div>
