{{-- Shared by pdf/check-report.blade.php and pdf/plus-report.blade.php —
     keep in sync so the two report types never silently drift apart again.
     Static output (no expand/collapse — a PDF has no interactivity), so
     advisories are always listed inline under their test row. --}}
<div class="section">
    <div class="section-title">MOT &amp; Mileage</div>
    @if ($history?->mot_history)
        <table class="data">
            <tr><th>Test Date</th><th>Result</th><th>Mileage</th></tr>
            @foreach (array_reverse($history->mot_history) as $test)
                <tr>
                    <td>{{ isset($test['test_date']) ? \Illuminate\Support\Carbon::parse($test['test_date'])->format('d M Y') : '—' }}</td>
                    <td class="{{ str_contains(strtolower($test['result'] ?? ''), 'fail') ? 'warn' : '' }}">{{ ucfirst($test['result'] ?? '—') }}</td>
                    <td>{{ isset($test['mileage']) ? number_format($test['mileage']).' mi' : '—' }}</td>
                </tr>
                @if (! empty($test['advisories']))
                    <tr>
                        <td colspan="3" style="padding-top:0;">
                            <ul style="margin:0; padding-left:14px; font-size:9px; color:#666;">
                                @foreach ($test['advisories'] as $advisory)
                                    <li>{{ $advisory }}</li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>
        @if ($history->mileage_anomaly)
            <p class="warn">Mileage anomaly detected in the MOT history.</p>
        @endif
    @else
        <p>No MOT history available.</p>
    @endif
</div>
