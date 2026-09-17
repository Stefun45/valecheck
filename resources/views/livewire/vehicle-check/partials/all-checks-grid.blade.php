{{-- Shared by check-report.blade.php and plus-report.blade.php — keep in
     sync so the two report types never silently drift apart again.
     Decision logic lives in ReportStatusSummary, not here. --}}
@php
    $allChecks = \App\Services\Reports\ReportStatusSummary::allChecks($history, $salvageCheck ?? null);
    $badge = fn (string $status) => match ($status) {
        'fail' => ['bg-red-50 text-vale-red', 'Yes'],
        'pass' => ['bg-green-50 text-green-700', 'No'],
        default => ['bg-gray-100 text-gray-500', 'Unavailable'],
    };
@endphp

@if (! empty($allChecks))
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
        <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="shield" />All Checks</h3>
        <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2">
            @foreach ($allChecks as $check)
                @php([$badgeClass, $badgeLabel] = $badge($check['status']))
                <div class="flex items-center justify-between py-1">
                    <span class="text-sm text-vale-navy">{{ $check['label'] }}</span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $badgeClass }}">{{ $badgeLabel }}</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
