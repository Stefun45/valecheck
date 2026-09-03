@php
    $tag = 'ValeCheck · History Report';
@endphp
@extends('pdf.layout', ['tag' => $tag])

@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $report = $check->report;
@endphp

@section('content')
    @include('pdf.partials.cover-page', ['check' => $check, 'vehicle' => $vehicle, 'history' => $history, 'tag' => $tag])

    <h1>{{ $vehicle->description() ?: $check->registration }}</h1>
    <p class="reg">{{ $check->registration }}</p>

    @include('pdf.partials.report-status-grid', ['history' => $history])

    <div class="section">
        <div class="section-title">Overall History Assessment</div>
        <p>{{ $report?->headline_summary }}</p>
    </div>

    <div class="section">
        <div class="section-title">Vehicle Summary</div>
        <table class="data">
            <tr><td>VIN</td><td>{{ $vehicle->maskedVin() ?? '—' }}</td></tr>
            <tr><td>Year</td><td>{{ $vehicle->year ?? '—' }}</td></tr>
            <tr><td>Engine</td><td>{{ $vehicle->engine ?? '—' }}</td></tr>
            <tr><td>Fuel</td><td>{{ $vehicle->fuel ?? '—' }}</td></tr>
            <tr><td>Transmission</td><td>{{ $vehicle->transmission ?? '—' }}</td></tr>
            <tr><td>Colour</td><td>{{ $vehicle->colour ?? '—' }}</td></tr>
        </table>
    </div>

    <table class="grid">
        <tr>
            @include('pdf.partials.provenance-facts', ['history' => $history, 'check' => $check])
        </tr>
    </table>
    <p style="color:#999; font-size:8px; margin:-6px 0 10px;">Vehicle Summary, Write-Off History, Finance, Stolen / Scrapped — Data provided by Experian.</p>

    @include('pdf.partials.mileage-chart', ['history' => $history])

    @include('pdf.partials.mot-history-table', ['history' => $history])

    @include('pdf.partials.keeper-history-facts', ['history' => $history])

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
