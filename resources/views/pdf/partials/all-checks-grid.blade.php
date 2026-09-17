@php
    $allChecks = \App\Services\Reports\ReportStatusSummary::allChecks($history, $salvageCheck ?? null);
    $label = fn (string $status) => match ($status) {
        'fail' => 'Yes',
        'pass' => 'No',
        default => 'Unavailable',
    };
@endphp

@if (! empty($allChecks))
    <div class="section">
        <div class="section-title">All Checks</div>
        <table class="data">
            @foreach ($allChecks as $check)
                <tr><td>{{ $check['label'] }}</td><td>{{ $label($check['status']) }}</td></tr>
            @endforeach
        </table>
    </div>
@endif
