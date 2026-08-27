{{-- Shared by pdf/check-report.blade.php and pdf/plus-report.blade.php —
     keep in sync so the two report types never silently drift apart again.
     Decision logic lives in ReportStatusSummary, not here. --}}
<table class="grid" style="margin-bottom: 14px;">
    <tr>
        @foreach (\App\Services\Reports\ReportStatusSummary::forHistory($history) as $box)
            <td width="25%" style="text-align: center; border: 1px solid #DDD; border-radius: 4px; padding: 8px 4px;">
                <div style="font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #888; margin-bottom: 6px;">{{ $box['label'] }}</div>
                <x-status-tick :ok="$box['ok']" :size="24" />
            </td>
        @endforeach
    </tr>
</table>
