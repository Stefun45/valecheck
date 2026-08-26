@extends('pdf.layout', ['tag' => 'ValeCheck Plus · History &amp; Value Report'])

@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $valuation = $check->valuation;
    $report = $check->report;

    $askingPrice = $check->asking_price ? (float) $check->asking_price : null;
    $cleanValue = $valuation?->clean_value ? (float) $valuation->clean_value : null;
    $pricePositionPct = ($askingPrice && $cleanValue) ? (($askingPrice - $cleanValue) / $cleanValue) * 100 : null;
@endphp

@section('content')
    <h1>{{ $vehicle->description() ?: $check->registration }}</h1>
    <p class="reg">{{ $check->registration }}</p>

    <table class="grid">
        <tr>
            <td width="33%">
                <div class="section">
                    <div class="section-title">Estimated Retail Value</div>
                    <p style="font-size:16px; font-weight:bold;">£{{ number_format($cleanValue ?? 0, 0) }}</p>
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

    <table class="grid">
        <tr>
            <td width="50%">
                <div class="section">
                    <div class="section-title">Write-Off History</div>
                    @if ($history?->isWrittenOff())
                        <p class="warn">Category {{ $history->write_off_category }} recorded</p>
                    @else
                        <p class="ok">No write-off history recorded.</p>
                    @endif
                </div>
            </td>
            <td width="50%">
                <div class="section">
                    <div class="section-title">Finance</div>
                    <p class="{{ $history?->finance_marker ? 'warn' : 'ok' }}">
                        {{ $history?->finance_marker ? 'Finance marker detected' : 'No finance marker found' }}
                    </p>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Market Assessment</div>
        <table class="data">
            <tr><th>Trade value</th><th>Retail value</th><th>Private value</th></tr>
            <tr>
                <td>£{{ number_format($valuation->trade_value ?? 0, 0) }}</td>
                <td>£{{ number_format($valuation->retail_value ?? 0, 0) }}</td>
                <td>£{{ number_format($valuation->private_value ?? 0, 0) }}</td>
            </tr>
        </table>
        <p style="color:#999; font-size:9px; margin-top:6px;">Confidence: {{ ucfirst($valuation->confidence ?? 'medium') }}. Estimates are guidance only, not a guarantee of value.</p>
    </div>

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
