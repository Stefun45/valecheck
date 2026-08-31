@extends('pdf.layout', ['tag' => 'ValeCheck Plus · History &amp; Value Report'])

@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $valuation = $check->valuation;
    $report = $check->report;

    $askingPrice = $check->asking_price ? (float) $check->asking_price : null;
    $cleanValue = $valuation?->clean_value ? (float) $valuation->clean_value : null;
    $salvageAdjustedValue = $valuation?->salvage_adjusted_value ? (float) $valuation->salvage_adjusted_value : null;
    $effectiveValue = $salvageAdjustedValue ?? $cleanValue;
    $pricePositionPct = ($askingPrice && $effectiveValue) ? (($askingPrice - $effectiveValue) / $effectiveValue) * 100 : null;
    $reportUrl = route('vehicle-checks.show', $check);
@endphp

@section('content')
    <h1>{{ $vehicle->description() ?: $check->registration }}</h1>
    <p class="reg">{{ $check->registration }}</p>

    @include('pdf.partials.report-status-grid', ['history' => $history])

    <table class="grid">
        <tr>
            <td width="33%">
                <div class="section">
                    <div class="section-title">{{ $salvageAdjustedValue ? "Est. Value (Cat {$valuation->write_off_category_applied} Adjusted)" : 'Estimated Retail Value' }}</div>
                    <p style="font-size:16px; font-weight:bold;">{{ $effectiveValue ? '£'.number_format($effectiveValue, 0) : '—' }}</p>
                    @if ($salvageAdjustedValue)
                        <p style="color:#999; font-size:9px;">Clean value: £{{ number_format($cleanValue, 0) }}</p>
                    @endif
                </div>
            </td>
            <td width="33%">
                <div class="section">
                    <div class="section-title">Asking Price</div>
                    <p style="font-size:16px; font-weight:bold;">{{ $askingPrice ? '£'.number_format($askingPrice, 0) : '—' }}</p>
                </div>
            </td>
            <td width="33%">
                <div class="section">
                    <div class="section-title">Price Position</div>
                    <p style="font-size:16px; font-weight:bold;">
                        @if ($pricePositionPct === null)
                            —
                        @else
                            {{ $pricePositionPct > 0 ? '+' : '' }}{{ number_format($pricePositionPct, 0) }}%
                        @endif
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Summary</div>
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
    <p style="color:#999; font-size:8px; margin:-6px 0 10px;">Vehicle identity and provenance data provided by Experian.</p>

    @include('pdf.partials.mileage-chart', ['history' => $history])

    @include('pdf.partials.mot-history-table', ['history' => $history])

    <div class="section">
        <div class="section-title">Market Assessment</div>
        @if ($cleanValue === null)
            <p>Valuation unavailable for this vehicle.</p>
        @else
            <table class="data">
                <tr><th>Trade value</th><th>Retail value</th><th>Private value</th></tr>
                <tr>
                    <td>{{ $valuation->trade_value ? '£'.number_format($valuation->trade_value, 0) : '—' }}</td>
                    <td>{{ $valuation->retail_value ? '£'.number_format($valuation->retail_value, 0) : '—' }}</td>
                    <td>{{ $valuation->private_value ? '£'.number_format($valuation->private_value, 0) : '—' }}</td>
                </tr>
            </table>
            @if ($salvageAdjustedValue)
                <p class="warn" style="margin-top:6px;">Salvage-adjusted (Cat {{ $valuation->write_off_category_applied }}): £{{ number_format($salvageAdjustedValue, 0) }}</p>
                <p style="color:#999; font-size:9px;">
                    This vehicle has a recorded write-off — the values above assume no damage history. A flat
                    {{ number_format($valuation->discount_applied * 100) }}% has been deducted as a rough guide, not a guarantee: actual value depends
                    on make/model, age, mileage, specification, desirability, original damage, repair quality, documentation, market conditions and
                    buyer perception.
                </p>
            @endif
            <p style="color:#999; font-size:9px; margin-top:6px;">Confidence: {{ ucfirst($valuation->confidence ?? 'medium') }}. Estimates are guidance only, not a guarantee of value.</p>
        @endif
    </div>

    @include('pdf.partials.tax-cost', ['taxCost' => $check->taxCost])

    @include('pdf.partials.salvage-auction-history', ['salvageAuctionCheck' => $check->salvageAuctionCheck, 'reportUrl' => $reportUrl])

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

    @if (! empty($report?->things_to_check))
        <div class="section">
            <div class="section-title">Things You Need To Check</div>
            <ul>
                @foreach ($report->things_to_check as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
