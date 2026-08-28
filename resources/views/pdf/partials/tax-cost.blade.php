<div class="section">
    <div class="section-title">Tax Cost</div>
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
