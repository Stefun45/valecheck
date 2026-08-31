@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $valuation = $check->valuation;
    $report = $check->report;

    $askingPrice = $check->asking_price ? (float) $check->asking_price : null;
    $cleanValue = $valuation?->clean_value ? (float) $valuation->clean_value : null;
    $pricePositionPct = ($askingPrice && $cleanValue) ? (($askingPrice - $cleanValue) / $cleanValue) * 100 : null;

    // The "buying a damaged vehicle?" upsell only shows when there's an
    // actual data-backed reason to — never as a blanket sales pitch.
    $suggestsDamage = $history?->isWrittenOff() ?? false;
    $rebuildPrice = app(\App\Services\Pricing\PricingService::class)->forRebuild();
@endphp

<div>
    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-vale-red mb-2">
        <x-application-logo text-class="text-xs" />
        <span>· Plus</span>
    </div>

    <h1 class="font-display font-bold text-2xl text-vale-navy">{{ $vehicle->description() ?: $check->registration }}</h1>
    <p class="text-gray-500 font-mono">{{ $check->registration }}</p>

    <div class="mt-6">
        @include('livewire.vehicle-check.partials.report-status-grid', ['history' => $history])
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div class="bg-white border border-gray-200 rounded-xl p-5 text-center shadow-sm">
            <p class="text-xs uppercase tracking-widest text-gray-400">Estimated Retail Value</p>
            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">{{ $cleanValue ? '£'.number_format($cleanValue, 0) : '—' }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 text-center shadow-sm">
            <p class="text-xs uppercase tracking-widest text-gray-400">Asking Price</p>
            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">{{ $askingPrice ? '£'.number_format($askingPrice, 0) : '—' }}</p>
        </div>
        <div class="bg-vale-light-blue border border-blue-100 rounded-xl p-5 text-center">
            <p class="text-xs uppercase tracking-widest text-vale-navy/60">Price Position</p>
            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">
                @if ($pricePositionPct === null)
                    —
                @else
                    {{ $pricePositionPct > 0 ? '+' : '' }}{{ number_format($pricePositionPct, 0) }}%
                @endif
            </p>
        </div>
    </div>

    <div class="mt-8 bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h2 class="flex items-center gap-1.5 text-sm font-bold uppercase tracking-widest text-gray-400"><x-section-icon name="document" />Summary</h2>
        <p class="text-vale-navy mt-2">{{ $report?->headline_summary }}</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mt-6">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="identity" />Vehicle Summary</h3>
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">VIN</dt><dd class="text-vale-navy font-mono">{{ $vehicle->maskedVin() ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Year</dt><dd class="text-vale-navy">{{ $vehicle->year ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Engine</dt><dd class="text-vale-navy">{{ $vehicle->engine ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Fuel</dt><dd class="text-vale-navy">{{ $vehicle->fuel ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Transmission</dt><dd class="text-vale-navy">{{ $vehicle->transmission ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Colour</dt><dd class="text-vale-navy">{{ $vehicle->colour ?? '—' }}</dd></div>
            </dl>
        </div>

        @include('livewire.vehicle-check.partials.provenance-facts', ['history' => $history, 'check' => $check])

        @include('livewire.vehicle-check.partials.mileage-chart', ['history' => $history])

        @include('livewire.vehicle-check.partials.mot-history-table', ['history' => $history])

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
            <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="chart-bar" />Market Assessment</h3>
            @if ($cleanValue === null)
                <p class="text-gray-400">Valuation unavailable for this vehicle.</p>
            @else
                @php
                    $marketValues = [
                        ['label' => 'Trade value', 'value' => $valuation->trade_value],
                        ['label' => 'Retail value', 'value' => $valuation->retail_value],
                        ['label' => 'Private value', 'value' => $valuation->private_value],
                    ];
                    $maxMarketValue = max(1, ...array_map(fn ($v) => (float) ($v['value'] ?? 0), $marketValues));
                @endphp
                <div class="space-y-2.5">
                    @foreach ($marketValues as $mv)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-400">{{ $mv['label'] }}</span>
                                <span class="text-vale-navy font-semibold">{{ $mv['value'] ? '£'.number_format($mv['value'], 0) : '—' }}</span>
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-vale-navy rounded-full" style="width: {{ $mv['value'] ? max(4, round(($mv['value'] / $maxMarketValue) * 100)) : 0 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-3">Confidence: {{ ucfirst($valuation->confidence ?? 'medium') }}. Estimates are guidance only, not a guarantee of value.</p>
            @endif
        </div>

        @include('livewire.vehicle-check.partials.tax-cost', ['taxCost' => $check->taxCost])

        @include('livewire.vehicle-check.partials.salvage-auction-history', ['salvageAuctionCheck' => $check->salvageAuctionCheck])

        @include('livewire.vehicle-check.partials.keeper-history-facts', ['history' => $history])

        @if (! empty($report?->listing_gaps))
            <div class="bg-red-50 border border-red-200 rounded-xl p-5 sm:col-span-2">
                <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-vale-red mb-3"><x-section-icon name="warning" />Important Warnings</h3>
                <ul class="list-disc list-inside space-y-1 text-vale-navy text-sm">
                    @foreach ($report->listing_gaps as $gap)
                        <li>{{ $gap }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($report?->things_to_check))
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
                <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="document" />Things You Need To Check</h3>
                <ul class="list-disc list-inside space-y-1 text-vale-navy text-sm">
                    @foreach ($report->things_to_check as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @if ($suggestsDamage && config('valecheck.rebuild_enabled'))
        <div class="mt-10 border-2 border-vale-navy rounded-xl p-6 bg-vale-navy text-white text-center shadow-sm">
            <h3 class="font-display font-bold text-lg">Buying a damaged vehicle?</h3>
            <p class="text-gray-300 mt-2">
                This vehicle has a recorded write-off. ValeCheck Rebuild adds AI damage analysis, a repair cost
                estimate, repaired value and a maximum bid — built specifically for damaged and salvage vehicles.
            </p>
            <a href="{{ route('vehicle-checks.start', ['registration' => $check->registration]) }}" wire:navigate class="inline-flex items-center justify-center mt-4 px-5 py-2.5 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600">
                Upgrade to ValeCheck Rebuild — £{{ number_format($rebuildPrice->gross, 2) }}
            </a>
        </div>
    @endif
</div>
