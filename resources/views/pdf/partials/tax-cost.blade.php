<div class="section">
    <div class="section-title">Running Costs</div>

    @if ($co2Band)
        <p style="font-size:10px; margin-bottom:4px;">CO2 emissions: {{ $co2Gkm }} g/km &middot; Band {{ $co2Band['letter'] }}</p>
        <table style="width:100%; border-collapse:collapse; margin-bottom:10px;">
            <tr>
                @foreach ($co2BandColours as $index => $colour)
                    <td style="background-color: {{ $colour }}; height:8px; padding:0; {{ $index === $co2Band['index'] ? 'border:1.5px solid #0f172a;' : '' }}"></td>
                @endforeach
            </tr>
        </table>
    @endif

    <div class="section-title" style="font-size:11px;">Tax Cost</div>
    @if (! $taxCost?->available)
        <p>Tax cost unavailable for this vehicle.</p>
    @else
        <table class="data">
            <tr><td>Annual rate</td><td>{{ $taxCost->annual_rate ? '£'.number_format($taxCost->annual_rate, 2) : '—' }}</td></tr>
            <tr><td>Six month rate</td><td>{{ $taxCost->six_month_rate ? '£'.number_format($taxCost->six_month_rate, 2) : '—' }}</td></tr>
            @if ($taxCost->tax_class)
                <tr><td>Tax class</td><td>{{ $taxCost->tax_class }}</td></tr>
            @endif
        </table>
    @endif
</div>
