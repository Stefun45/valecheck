{{-- Free-preview teaser for Mileage Over Time, used only in the unpaid "is
     this your vehicle?" previews (homepage widget + start-check). It
     replaces the full mileage-chart partial there, which otherwise plotted
     every real recorded mileage reading before checkout. Derives a simple,
     honest trend line from the same mileage/test_date pairs the real chart
     uses — never a fabricated anomaly score — falling back to a plain
     reading count when the trend isn't clearly one thing or the other. The
     genuinely paid report keeps using mileage-chart.blade.php directly and
     is untouched by this partial. --}}
@php
    $points = collect($history?->mot_history ?? [])
        ->filter(fn ($test) => isset($test['mileage'], $test['test_date']))
        ->sortBy('test_date')
        ->values();
    $readingCount = $points->count();

    if ($readingCount === 0) {
        $trendSummary = 'No mileage readings on record yet.';
    } elseif ($readingCount === 1) {
        $trendSummary = '1 mileage reading recorded.';
    } else {
        $drops = 0;
        for ($i = 1; $i < $readingCount; $i++) {
            if ($points[$i]['mileage'] < $points[$i - 1]['mileage']) {
                $drops++;
            }
        }

        $trendSummary = match (true) {
            $drops === 0 => "Mileage trend: increasing consistently across {$readingCount} MOTs.",
            $drops === 1 => "Mileage trend: one reading looks inconsistent across {$readingCount} MOTs.",
            default => "{$readingCount} mileage readings recorded.",
        };
    }
@endphp
<div class="relative bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="trending-up" />Mileage Over Time</h3>
    <p class="text-sm text-gray-600 leading-relaxed">{{ $trendSummary }}</p>
    <p class="flex items-center gap-1 text-xs font-bold uppercase tracking-wide mt-3 text-vale-navy">
        <x-section-icon name="lock" size="10" />
        Full history included in Check
    </p>
</div>
