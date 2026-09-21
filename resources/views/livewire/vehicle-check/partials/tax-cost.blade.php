<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="document" />Running Costs</h3>

    @if ($co2Band)
        <div class="mb-4 pb-4 border-b border-gray-100">
            <div class="flex items-center justify-between mb-1.5 text-sm">
                <span class="text-gray-500">CO2 emissions</span>
                <span class="text-vale-navy font-semibold">{{ $co2Gkm }} g/km &middot; Band {{ $co2Band['letter'] }}</span>
            </div>
            <div class="flex gap-0.5" role="img" aria-label="CO2 band {{ $co2Band['letter'] }} of {{ count($co2BandColours) }}">
                @foreach ($co2BandColours as $index => $colour)
                    <div
                        class="h-2 flex-1 rounded-sm {{ $index === $co2Band['index'] ? 'ring-2 ring-offset-1 ring-vale-navy' : '' }}"
                        style="background-color: {{ $colour }}"
                    ></div>
                @endforeach
            </div>
        </div>
    @endif

    <h4 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Tax Cost</h4>
    @if (! $taxCost?->available)
        <p class="text-gray-400">Tax cost unavailable for this vehicle.</p>
    @else
        <dl class="grid sm:grid-cols-2 gap-x-4 gap-y-1 text-sm">
            <div class="flex justify-between"><dt class="text-gray-500">Annual rate</dt><dd class="text-vale-navy font-semibold">{{ $taxCost->annual_rate ? '£'.number_format($taxCost->annual_rate, 2) : '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-500">Six month rate</dt><dd class="text-vale-navy font-semibold">{{ $taxCost->six_month_rate ? '£'.number_format($taxCost->six_month_rate, 2) : '—' }}</dd></div>
            @if ($taxCost->tax_class)
                <div class="flex justify-between sm:col-span-2"><dt class="text-gray-500">Tax class</dt><dd class="text-vale-navy">{{ $taxCost->tax_class }}</dd></div>
            @endif
        </dl>
    @endif
</div>
