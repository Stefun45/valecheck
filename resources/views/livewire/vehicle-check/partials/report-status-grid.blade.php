{{-- Shared by check-report.blade.php and plus-report.blade.php — keep in
     sync so the two report types never silently drift apart again.
     Decision logic lives in ReportStatusSummary, not here. --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach (\App\Services\Reports\ReportStatusSummary::forHistory($history) as $box)
        <div class="bg-white border border-gray-200 rounded-xl p-4 text-center shadow-sm">
            <p class="text-xs uppercase tracking-widest text-gray-400 mb-2">{{ $box['label'] }}</p>
            <x-status-tick :ok="$box['ok']" class="mx-auto" />
        </div>
    @endforeach
</div>
