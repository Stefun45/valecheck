<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="document" />Tax Cost</h3>
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
