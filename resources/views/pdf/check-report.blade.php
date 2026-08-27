@extends('pdf.layout', ['tag' => 'ValeCheck · History Report'])

@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $report = $check->report;
@endphp

@section('content')
    <h1>{{ $vehicle->description() ?: $check->registration }}</h1>
    <p class="reg">{{ $check->registration }}</p>

    @include('pdf.partials.report-status-grid', ['history' => $history])

    <div class="section">
        <div class="section-title">Overall History Assessment</div>
        <p>{{ $report?->headline_summary }}</p>
    </div>

    <table class="grid">
        <tr>
            <td width="50%">
                <div class="section">
                    <div class="section-title">Vehicle Summary</div>
                    <table class="data">
                        <tr><td>VIN</td><td>{{ $vehicle->vin ?? '—' }}</td></tr>
                        <tr><td>Year</td><td>{{ $vehicle->year ?? '—' }}</td></tr>
                        <tr><td>Engine</td><td>{{ $vehicle->engine ?? '—' }}</td></tr>
                        <tr><td>Fuel</td><td>{{ $vehicle->fuel ?? '—' }}</td></tr>
                        <tr><td>Transmission</td><td>{{ $vehicle->transmission ?? '—' }}</td></tr>
                        <tr><td>Colour</td><td>{{ $vehicle->colour ?? '—' }}</td></tr>
                    </table>
                </div>
            </td>
            <td width="50%">
                <div class="section">
                    <div class="section-title">Write-Off History</div>
                    @if ($history?->isWrittenOff())
                        <p class="warn">Category {{ $history->write_off_category }} recorded</p>
                        <p>Date: {{ optional($history->write_off_date)->format('d M Y') ?? 'Unknown' }}</p>
                    @else
                        <p class="ok">No write-off history recorded.</p>
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="section">
                    <div class="section-title">Finance</div>
                    <p class="{{ $history?->finance_marker ? 'warn' : 'ok' }}">
                        {{ $history?->finance_marker ? 'Finance marker detected' : 'No finance marker found' }}
                    </p>
                </div>
            </td>
            <td>
                <div class="section">
                    <div class="section-title">Stolen / Scrapped</div>
                    <p class="{{ $history?->stolen_marker ? 'warn' : 'ok' }}">Stolen: {{ $history?->stolen_marker ? 'Marker found' : 'No marker found' }}</p>
                    <p class="{{ $history?->scrapped_marker ? 'warn' : 'ok' }}">Scrapped: {{ $history?->scrapped_marker ? 'Marker found' : 'No marker found' }}</p>
                </div>
            </td>
        </tr>
    </table>

    @include('pdf.partials.mileage-chart', ['history' => $history])

    @include('pdf.partials.mot-history-table', ['history' => $history])

    <div class="section">
        <div class="section-title">Keeper / Registration History</div>
        <p>Previous keepers: {{ $history?->previous_keepers ?? 'Unknown' }}</p>
        <p>Plate changes: {{ $history?->plate_changes ?? 0 }}</p>
        <p>Imported: {{ $history?->imported ? 'Yes' : 'No' }}</p>
    </div>

    @if (! empty($report?->listing_gaps))
        <div class="section">
            <div class="section-title">Important Warnings</div>
            <ul>
                @foreach ($report->listing_gaps as $gap)
                    <li>{{ $gap }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
