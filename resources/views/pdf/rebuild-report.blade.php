@php
    $tag = 'ValeCheck Rebuild · Damage & Numbers Report';
@endphp
@extends('pdf.layout', ['tag' => $tag])

@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $valuation = $check->valuation;
    $damageAnalysis = $check->damageAnalysis;
    $repairEstimate = $check->repairEstimate;
    $bid = $check->bidRecommendation;
    $report = $check->report;

    $recommendationLabels = ['buy' => 'BUY', 'maybe' => 'MAYBE', 'walk_away' => 'WALK AWAY'];
    $totalInvestment = ($check->current_bid ?? $check->asking_price ?? 0) + ($repairEstimate->expected_estimate ?? 0)
        + ($bid->auction_fees ?? 0) + ($bid->transport_cost ?? 0) + ($bid->service_mot_allowance ?? 0);
    $margin = ($bid->expected_resale_value ?? 0) - $totalInvestment;
@endphp

@section('content')
    @include('pdf.partials.cover-page', ['check' => $check, 'vehicle' => $vehicle, 'history' => $history, 'tag' => $tag])

    <h1>{{ $vehicle->description() ?: $check->registration }}</h1>
    <p class="reg">{{ $check->registration }}</p>

    <div class="headline">
        <table class="grid">
            <tr>
                <td width="33%">
                    <div class="label">Deal Score</div>
                    <div class="value">{{ $bid->deal_score ?? '—' }}/100</div>
                </td>
                <td width="33%">
                    <div class="label">Recommendation</div>
                    <div class="value">{{ $recommendationLabels[$bid->recommendation] ?? '—' }}</div>
                </td>
                <td width="33%">
                    <div class="label">Current Bid / Price</div>
                    <div class="value">£{{ number_format($check->current_bid ?? $check->asking_price ?? 0, 0) }}</div>
                </td>
            </tr>
        </table>
        <table class="grid" style="margin-top:10px; border-top:1px solid #33465C; padding-top:8px;">
            <tr>
                <td width="25%"><div class="label">Estimated Repair</div><div>£{{ number_format($repairEstimate->expected_estimate ?? 0, 0) }}</div></td>
                <td width="25%"><div class="label">Expected Resale</div><div>£{{ number_format($bid->expected_resale_value ?? 0, 0) }}</div></td>
                <td width="25%"><div class="label">Recommended Bid</div><div>£{{ number_format($bid->recommended_bid ?? 0, 0) }}</div></td>
                <td width="25%"><div class="label">Absolute Maximum</div><div>£{{ number_format($bid->absolute_maximum ?? 0, 0) }}</div></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Summary</div>
        <p>{{ $report?->headline_summary }}</p>
    </div>

    <table class="grid">
        <tr>
            <td width="50%">
                <div class="section">
                    <div class="section-title">Vehicle History</div>
                    <p class="{{ $history?->isWrittenOff() ? 'warn' : 'ok' }}">
                        {{ $history?->isWrittenOff() ? "Category {$history->write_off_category} recorded" : 'No write-off history recorded' }}
                    </p>
                    @if ($history?->isWrittenOff() && $history->formattedDamageLocations())
                        <p>Damage area: {{ implode(', ', $history->formattedDamageLocations()) }}</p>
                    @endif
                    <p>Previous keepers: {{ $history?->previous_keepers ?? 'Unknown' }}</p>
                    <p>Plate changes: {{ $history?->plate_changes ?? 0 }}</p>
                    @if (! empty($history?->plate_change_history))
                        <ul>
                            @foreach ($history->plate_change_history as $change)
                                <li>{{ isset($change['date']) ? \Illuminate\Support\Carbon::parse($change['date'])->format('d M Y') : 'Unknown date' }}: {{ $change['from'] ?? 'Unknown' }} &rarr; {{ $change['to'] ?? 'Unknown' }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <p>Colour changes: {{ $history?->colour_changes ?? 0 }}</p>
                    @if ($check->user->isDealerSubscriber())
                        <p class="{{ $history?->high_risk_marker ? 'warn' : 'ok' }}">
                            {{ $history?->high_risk_marker ? 'High risk marker found' : 'No high risk marker found' }}
                        </p>
                    @endif
                </div>
            </td>
            <td width="50%">
                <div class="section">
                    <div class="section-title">Market Value</div>
                    <p>Clean value: £{{ number_format($valuation->clean_value ?? 0, 0) }}</p>
                    @if ($valuation?->write_off_category_applied)
                        <p>Salvage-adjusted ({{ $valuation->write_off_category_applied }}): £{{ number_format($valuation->salvage_adjusted_value ?? 0, 0) }}</p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">AI Damage Assessment</div>
        <p>{{ $damageAnalysis?->summary }}</p>
        @if ($damageAnalysis?->findings->isNotEmpty())
            <ul>
                @foreach ($damageAnalysis->findings as $finding)
                    <li>
                        <strong>{{ ucfirst(str_replace('_', ' ', $finding->component)) }}</strong>
                        — {{ $finding->condition }} @if ($finding->severity)({{ $finding->severity }} severity)@endif.
                        {{ $finding->explanation }}
                    </li>
                @endforeach
            </ul>
        @endif
        <p style="color:#999; font-size:9px;">Confidence: {{ ucfirst($damageAnalysis->confidence ?? 'low') }} · {{ $damageAnalysis->images_analysed ?? 0 }} photograph(s) analysed. Photographs cannot reliably reveal concealed mechanical, electrical or structural damage.</p>
    </div>

    @if (! empty($report?->listing_vs_evidence))
        <div class="section">
            <div class="section-title">Listing vs Evidence</div>
            <ul>
                @foreach ($report->listing_vs_evidence as $comparison)
                    <li>
                        <strong>Seller says:</strong> {{ $comparison['claim'] }}<br>
                        <strong>ValeCheck sees:</strong> {{ $comparison['observation'] }}
                        ({{ strtoupper($comparison['confidence'] ?? 'low') }} confidence)
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <table class="grid">
        <tr>
            <td width="33%">
                <div class="section">
                    <div class="section-title">Estimated Repairs</div>
                    <p style="font-size:14px; font-weight:bold;">£{{ number_format($repairEstimate->low_estimate ?? 0, 0) }} – £{{ number_format($repairEstimate->high_estimate ?? 0, 0) }}</p>
                </div>
            </td>
            <td width="33%">
                <div class="section">
                    <div class="section-title">Total Investment</div>
                    <p style="font-size:14px; font-weight:bold;">£{{ number_format($totalInvestment, 0) }}</p>
                </div>
            </td>
            <td width="33%">
                <div class="section">
                    <div class="section-title">Expected Margin</div>
                    <p style="font-size:14px; font-weight:bold;" class="{{ $margin < 0 ? 'warn' : '' }}">£{{ number_format($margin, 0) }}</p>
                </div>
            </td>
        </tr>
    </table>

    @if (! empty($report?->risks))
        <div class="section">
            <div class="section-title">Risks</div>
            <ul>
                @foreach ($report->risks as $risk)
                    <li class="warn">{{ $risk }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! empty($report?->listing_gaps))
        <div class="section">
            <div class="section-title">What The Listing Doesn't Tell You</div>
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

    <div class="disclaimer">
        Vehicle valuations, repair estimates, resale estimates and recommended purchase or bid amounts are estimates
        based on available vehicle, market and image data at the time of analysis, provided for guidance only and not
        a guarantee of value, cost or profitability. AI damage analysis may fail to identify concealed structural,
        mechanical or electrical damage and is not a substitute for a physical inspection by a suitably qualified person.
    </div>
@endsection
