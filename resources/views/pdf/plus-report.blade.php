@php
    $tag = 'ValeCheck Plus · History & Value Report';
@endphp
@extends('pdf.layout', ['tag' => $tag])

@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $valuation = $check->valuation;
    $report = $check->report;

    $askingPrice = $check->asking_price ? (float) $check->asking_price : null;
    $cleanValue = $valuation?->clean_value ? (float) $valuation->clean_value : null;
    $categoryAdjustedLow = $valuation?->category_adjusted_value_low ? (float) $valuation->category_adjusted_value_low : null;
    $salvageAdjustedValue = $valuation?->salvage_adjusted_value ? (float) $valuation->salvage_adjusted_value : null;
    $hasValuation = $cleanValue !== null || $categoryAdjustedLow !== null;
    $effectiveValue = $salvageAdjustedValue ?? $cleanValue;
    $pricePositionPct = ($askingPrice && $effectiveValue) ? (($askingPrice - $effectiveValue) / $effectiveValue) * 100 : null;
    $reportUrl = route('vehicle-checks.show', $check);
@endphp

@section('content')
    @include('pdf.partials.cover-page', ['check' => $check, 'vehicle' => $vehicle, 'history' => $history, 'tag' => $tag])

    <h1>{{ $vehicle->description() ?: $check->registration }}</h1>
    <p class="reg">{{ $check->registration }}</p>

    @include('pdf.partials.report-status-grid', ['history' => $history])

    <table class="grid">
        <tr>
            <td width="{{ $askingPrice ? '33%' : '100%' }}">
                <div class="section">
                    <div class="section-title">{{ $categoryAdjustedLow ? "Est. Value (Cat {$valuation->write_off_category_applied})" : ($salvageAdjustedValue ? "Est. Value (Cat {$valuation->write_off_category_applied} Adjusted)" : 'Dealer Forecourt Value') }}</div>
                    <p style="font-size:16px; font-weight:bold;">
                        @if ($effectiveValue)
                            £{{ number_format($effectiveValue, 0) }}
                        @else
                            —
                        @endif
                    </p>
                    @if ($categoryAdjustedLow)
                        <p style="color:#999; font-size:9px;">A conservative estimate, not the top of the category's range.</p>
                    @endif
                    @if ($salvageAdjustedValue && ! $categoryAdjustedLow)
                        <p style="color:#999; font-size:9px;">Clean value: £{{ number_format($cleanValue, 0) }}</p>
                    @endif
                </div>
            </td>
            @if ($askingPrice)
                <td width="33%">
                    <div class="section">
                        <div class="section-title">Asking Price</div>
                        <p style="font-size:16px; font-weight:bold;">£{{ number_format($askingPrice, 0) }}</p>
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
            @endif
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
    <p style="color:#999; font-size:8px; margin:-6px 0 10px;">Vehicle Summary, Write-Off History, Finance, Stolen / Scrapped — Data provided by Experian.</p>

    @include('pdf.partials.mileage-chart', ['history' => $history])

    @include('pdf.partials.mot-history-table', ['history' => $history])

    <div class="section">
        <div class="section-title">Market Assessment</div>
        @if (! $hasValuation)
            <p>Valuation unavailable for this vehicle.</p>
        @elseif ($categoryAdjustedLow)
            <p class="warn">Category-adjusted retail value (Cat {{ $valuation->write_off_category_applied }}): £{{ number_format($categoryAdjustedLow, 0) }}</p>
            <p style="color:#999; font-size:9px;">A conservative, market-calibrated estimate for this vehicle's write-off category and damage, not a flat percentage guess.</p>
            @if ($valuation->salvage_auction_bid_low)
                <p style="margin-top:6px;">Salvage auction predicted bid: £{{ number_format($valuation->salvage_auction_bid_low, 0) }}&ndash;£{{ number_format($valuation->salvage_auction_bid_high, 0) }} (avg £{{ number_format($valuation->salvage_auction_bid_average, 0) }})</p>
            @endif
            <p style="color:#999; font-size:9px; margin-top:6px;">Confidence: {{ ucfirst($valuation->confidence ?? 'medium') }}. Estimates are guidance only, not a guarantee of value.</p>
        @elseif ($salvageAdjustedValue)
            {{-- Legacy fallback path only — see RetrieveValuation. --}}
            <p class="warn">Salvage-adjusted (Cat {{ $valuation->write_off_category_applied }}): £{{ number_format($salvageAdjustedValue, 0) }}</p>
            <p style="color:#999; font-size:9px;">
                A flat {{ number_format($valuation->discount_applied * 100) }}% has been deducted from the clean value as a rough guide, not a
                guarantee: actual value depends on make/model, age, mileage, specification, desirability, original damage, repair quality,
                documentation, market conditions and buyer perception.
            </p>
        @else
            <table class="data">
                <tr><th>Dealer forecourt</th><th>Trade retail</th><th>Trade average</th><th>Trade poor</th></tr>
                <tr>
                    <td>{{ $valuation->dealer_forecourt ? '£'.number_format($valuation->dealer_forecourt, 0) : '—' }}</td>
                    <td>{{ $valuation->trade_value ? '£'.number_format($valuation->trade_value, 0) : '—' }}</td>
                    <td>{{ $valuation->trade_average ? '£'.number_format($valuation->trade_average, 0) : '—' }}</td>
                    <td>{{ $valuation->trade_poor ? '£'.number_format($valuation->trade_poor, 0) : '—' }}</td>
                </tr>
            </table>
            <table class="data" style="margin-top:6px;">
                <tr><th>Private clean</th><th>Private average</th><th>Part exchange</th><th>Auction value</th></tr>
                <tr>
                    <td>{{ $valuation->private_value ? '£'.number_format($valuation->private_value, 0) : '—' }}</td>
                    <td>{{ $valuation->private_average ? '£'.number_format($valuation->private_average, 0) : '—' }}</td>
                    <td>{{ $valuation->part_exchange ? '£'.number_format($valuation->part_exchange, 0) : '—' }}</td>
                    <td>{{ $valuation->auction_value ? '£'.number_format($valuation->auction_value, 0) : '—' }}</td>
                </tr>
            </table>
            @if ($valuation->list_price)
                <p style="color:#999; font-size:9px; margin-top:6px;">List price when new: £{{ number_format($valuation->list_price, 0) }}.</p>
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
