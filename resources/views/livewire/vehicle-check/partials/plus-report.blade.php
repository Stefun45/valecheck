@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $valuation = $check->valuation;
    $report = $check->report;

    $askingPrice = $check->asking_price ? (float) $check->asking_price : null;
    $cleanValue = $valuation?->clean_value ? (float) $valuation->clean_value : null;
    $categoryAdjustedLow = $valuation?->category_adjusted_value_low ? (float) $valuation->category_adjusted_value_low : null;
    $categoryAdjustedHigh = $valuation?->category_adjusted_value_high ? (float) $valuation->category_adjusted_value_high : null;
    $salvageAdjustedValue = $valuation?->salvage_adjusted_value ? (float) $valuation->salvage_adjusted_value : null;
    $hasValuation = $cleanValue !== null || $categoryAdjustedLow !== null;
    // A write-off vehicle's realistic worth is the category-adjusted (or,
    // failing that, flat-percentage-adjusted) figure, not the "as if it
    // had no history" clean value — comparing an asking price against the
    // wrong one could tell a customer an overpriced write-off looks fair.
    $effectiveValue = $salvageAdjustedValue ?? $cleanValue;
    $pricePositionPct = ($askingPrice && $effectiveValue) ? (($askingPrice - $effectiveValue) / $effectiveValue) * 100 : null;

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
            <p class="text-xs uppercase tracking-widest text-gray-400">{{ $categoryAdjustedLow ? "Est. Value (Cat {$valuation->write_off_category_applied})" : ($salvageAdjustedValue ? "Est. Value (Cat {$valuation->write_off_category_applied} Adjusted)" : 'Dealer Forecourt Value') }}</p>
            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">
                @if ($categoryAdjustedLow)
                    £{{ number_format($categoryAdjustedLow, 0) }}&ndash;£{{ number_format($categoryAdjustedHigh, 0) }}
                @elseif ($effectiveValue)
                    £{{ number_format($effectiveValue, 0) }}
                @else
                    —
                @endif
            </p>
            @if ($salvageAdjustedValue && ! $categoryAdjustedLow)
                <p class="text-xs text-gray-400 mt-1">Clean value: £{{ number_format($cleanValue, 0) }}</p>
            @endif
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
            @if (! $hasValuation)
                <p class="text-gray-400">Valuation unavailable for this vehicle.</p>
            @elseif ($categoryAdjustedLow)
                <div class="bg-red-50 border border-red-100 rounded-lg p-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-vale-red font-semibold">Category-adjusted retail value (Cat {{ $valuation->write_off_category_applied }})</span>
                        <span class="text-vale-red font-bold">£{{ number_format($categoryAdjustedLow, 0) }}&ndash;£{{ number_format($categoryAdjustedHigh, 0) }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">A real market-calibrated range for this vehicle's write-off category and damage, not a flat percentage guess.</p>
                </div>
                @if ($valuation->salvage_auction_bid_low)
                    <div class="flex justify-between text-sm mt-4">
                        <span class="text-gray-400">Salvage auction predicted bid</span>
                        <span class="text-vale-navy font-semibold">
                            £{{ number_format($valuation->salvage_auction_bid_low, 0) }}&ndash;£{{ number_format($valuation->salvage_auction_bid_high, 0) }}
                            <span class="text-xs text-gray-400">(avg £{{ number_format($valuation->salvage_auction_bid_average, 0) }})</span>
                        </span>
                    </div>
                @endif
                <p class="text-xs text-gray-400 mt-3">Confidence: {{ ucfirst($valuation->confidence ?? 'medium') }}. Estimates are guidance only, not a guarantee of value.</p>
            @elseif ($salvageAdjustedValue)
                {{-- Legacy fallback path only — see RetrieveValuation. --}}
                <div class="flex justify-between text-sm">
                    <span class="text-vale-red font-semibold">Salvage-adjusted (Cat {{ $valuation->write_off_category_applied }})</span>
                    <span class="text-vale-red font-bold">£{{ number_format($salvageAdjustedValue, 0) }}</span>
                </div>
                <p class="text-xs text-gray-400 mt-1">
                    A flat {{ number_format($valuation->discount_applied * 100) }}% has been deducted from the clean value as a rough guide, not a
                    guarantee: actual value depends on make/model, age, mileage, specification, desirability, original damage, repair quality,
                    documentation, market conditions and buyer perception.
                </p>
            @else
                @php
                    $marketValues = [
                        ['label' => 'Dealer forecourt', 'value' => $valuation->dealer_forecourt],
                        ['label' => 'Trade retail', 'value' => $valuation->trade_value],
                        ['label' => 'Trade average', 'value' => $valuation->trade_average],
                        ['label' => 'Trade poor', 'value' => $valuation->trade_poor],
                        ['label' => 'Private clean', 'value' => $valuation->private_value],
                        ['label' => 'Private average', 'value' => $valuation->private_average],
                        ['label' => 'Part exchange', 'value' => $valuation->part_exchange],
                        ['label' => 'Auction value', 'value' => $valuation->auction_value],
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
                @if ($valuation->list_price)
                    <p class="text-xs text-gray-400 mt-3">List price when new: £{{ number_format($valuation->list_price, 0) }}.</p>
                @endif
                <p class="text-xs text-gray-400 mt-1">Confidence: {{ ucfirst($valuation->confidence ?? 'medium') }}. Estimates are guidance only, not a guarantee of value.</p>
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
