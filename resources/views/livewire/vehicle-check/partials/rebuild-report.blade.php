@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $valuation = $check->valuation;
    $damageAnalysis = $check->damageAnalysis;
    $repairEstimate = $check->repairEstimate;
    $bid = $check->bidRecommendation;
    $report = $check->report;

    $recommendationStyles = [
        'buy' => 'bg-green-50 text-green-700 border-green-200',
        'maybe' => 'bg-amber-50 text-amber-700 border-amber-200',
        'walk_away' => 'bg-red-50 text-vale-red border-red-200',
    ];
    $recommendationLabels = ['buy' => 'BUY', 'maybe' => 'MAYBE', 'walk_away' => 'WALK AWAY'];
@endphp

<div>
    @include('livewire.vehicle-check.partials.report-header', ['check' => $check, 'vehicle' => $vehicle, 'history' => $history, 'productLabel' => 'Rebuild'])

    {{-- The commercial decision, front and centre — this is what Rebuild exists for. --}}
    <div class="mt-6 bg-vale-navy rounded-xl p-6 text-white shadow-sm">
        <div class="grid sm:grid-cols-3 gap-4">
            <div class="text-center">
                <p class="text-xs uppercase tracking-widest text-gray-400">Deal Score</p>
                <p class="font-display text-4xl font-extrabold mt-1">{{ $bid->deal_score ?? '—' }}<span class="text-lg text-gray-400">/100</span></p>
            </div>
            <div class="rounded-xl p-3 text-center border {{ $recommendationStyles[$bid->recommendation] ?? 'border-gray-700' }}">
                <p class="text-xs uppercase tracking-widest opacity-70">Recommendation</p>
                <p class="font-display text-2xl font-extrabold mt-1">{{ $recommendationLabels[$bid->recommendation] ?? '—' }}</p>
            </div>
            <div class="text-center">
                <p class="text-xs uppercase tracking-widest text-gray-400">Current Bid / Price</p>
                <p class="font-display text-2xl font-extrabold mt-1">£{{ number_format($check->current_bid ?? $check->asking_price ?? 0, 0) }}</p>
                @php $priceSource = $check->listing_data_sources['current_bid'] ?? $check->listing_data_sources['asking_price'] ?? null; @endphp
                @if ($priceSource)
                    <p class="text-[10px] text-gray-400 mt-1">Source: {{ $priceSource === 'imported' ? 'Listing' : 'Manual entry' }}</p>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4 pt-4 border-t border-white/10 text-center">
            <div>
                <p class="text-xs uppercase tracking-widest text-gray-400">Estimated Repair</p>
                <p class="font-display text-xl font-bold mt-1">£{{ number_format($repairEstimate->expected_estimate ?? 0, 0) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-widest text-gray-400">Expected Resale</p>
                <p class="font-display text-xl font-bold mt-1">£{{ number_format($bid->expected_resale_value ?? 0, 0) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-widest text-gray-400">Recommended Bid</p>
                <p class="font-display text-xl font-bold mt-1">£{{ number_format($bid->recommended_bid ?? 0, 0) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-widest text-vale-red">Absolute Maximum</p>
                <p class="font-display text-xl font-bold mt-1">£{{ number_format($bid->absolute_maximum ?? 0, 0) }}</p>
            </div>
        </div>
    </div>

    <div class="mt-8 bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400">Summary</h2>
        <p class="text-vale-navy mt-2">{{ $report?->headline_summary }}</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mt-6">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Vehicle History</h3>
            <p class="{{ $history?->isWrittenOff() ? 'text-vale-red font-semibold' : 'text-vale-navy' }}">
                {{ $history?->isWrittenOff() ? "Category {$history->write_off_category} recorded" : 'No write-off history recorded' }}
            </p>
            @if ($history?->isWrittenOff() && $history->formattedDamageLocations())
                <p class="text-sm text-gray-500 mt-1">Damage area: {{ implode(', ', $history->formattedDamageLocations()) }}</p>
            @endif
            <p class="{{ $history?->finance_marker ? 'text-vale-red font-semibold' : 'text-vale-navy' }} mt-1">
                {{ $history?->finance_marker ? 'Finance marker detected' : 'No finance marker found' }}
            </p>
            <p class="text-vale-navy mt-1">Previous keepers: {{ $history?->previous_keepers ?? 'Unknown' }}</p>
            <p class="text-vale-navy mt-1">Plate changes: {{ $history?->plate_changes ?? 0 }}</p>
            @if (! empty($history?->plate_change_history))
                <ul class="text-xs text-gray-500 mt-1 space-y-0.5 list-disc list-inside">
                    @foreach ($history->plate_change_history as $change)
                        <li>{{ isset($change['date']) ? \Illuminate\Support\Carbon::parse($change['date'])->format('d M Y') : 'Unknown date' }}: {{ $change['from'] ?? 'Unknown' }} &rarr; {{ $change['to'] ?? 'Unknown' }}</li>
                    @endforeach
                </ul>
            @endif
            <p class="text-vale-navy mt-1">Colour changes: {{ $history?->colour_changes ?? 0 }}</p>
            @if ($check->user->isDealerSubscriber())
                <p class="{{ $history?->high_risk_marker ? 'text-vale-red font-semibold' : 'text-vale-navy' }} mt-1">
                    {{ $history?->high_risk_marker ? 'High risk marker found' : 'No high risk marker found' }}
                </p>
            @endif
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Market Value</h3>
            <p class="text-vale-navy">Clean value: £{{ number_format($valuation->clean_value ?? 0, 0) }}</p>
            @if ($valuation?->write_off_category_applied)
                <p class="text-vale-navy mt-1">Salvage-adjusted ({{ $valuation->write_off_category_applied }}): £{{ number_format($valuation->salvage_adjusted_value ?? 0, 0) }}</p>
            @endif
            <p class="text-xs text-gray-400 mt-2">Confidence: {{ ucfirst($valuation->confidence ?? 'medium') }}</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">AI Damage Assessment</h3>
            <p class="text-vale-navy">{{ $damageAnalysis?->summary }}</p>
            @if ($damageAnalysis?->findings->isNotEmpty())
                <ul class="mt-3 space-y-2">
                    @foreach ($damageAnalysis->findings as $finding)
                        <li class="flex items-start gap-2 text-sm">
                            <span class="mt-1 h-2 w-2 rounded-full shrink-0 {{ $finding->isDamaged() ? 'bg-vale-red' : 'bg-green-600' }}"></span>
                            <span class="text-vale-navy">
                                <span class="font-semibold capitalize">{{ str_replace('_', ' ', $finding->component) }}</span>
                                — {{ $finding->condition }} @if($finding->severity) ({{ $finding->severity }} severity) @endif.
                                <span class="text-gray-500">{{ $finding->explanation }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
            <p class="text-xs text-gray-400 mt-3">Confidence: {{ ucfirst($damageAnalysis->confidence ?? 'low') }} · {{ $damageAnalysis->images_analysed ?? 0 }} photograph(s) analysed. Photographs cannot reliably reveal concealed mechanical, electrical or structural damage.</p>
        </div>

        @if (! empty($report?->listing_vs_evidence))
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Listing vs Evidence</h3>
                <ul class="space-y-3">
                    @foreach ($report->listing_vs_evidence as $comparison)
                        @php
                            $verdictDot = match ($comparison['verdict'] ?? 'inconclusive') {
                                'supported' => 'bg-green-600',
                                'contradicted' => 'bg-vale-red',
                                default => 'bg-gray-300',
                            };
                            $confidenceStyles = [
                                'high' => 'bg-green-50 text-green-700 border-green-200',
                                'medium' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'low' => 'bg-gray-50 text-gray-500 border-gray-200',
                            ];
                        @endphp
                        <li class="flex items-start gap-2 text-sm">
                            <span class="mt-1 h-2 w-2 rounded-full shrink-0 {{ $verdictDot }}"></span>
                            <div class="flex-1">
                                <p class="text-vale-navy"><span class="font-semibold">Seller says:</span> {{ $comparison['claim'] }}</p>
                                <p class="text-gray-600 mt-0.5"><span class="font-semibold">ValeCheck sees:</span> {{ $comparison['observation'] }}</p>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full border shrink-0 {{ $confidenceStyles[$comparison['confidence'] ?? 'low'] ?? $confidenceStyles['low'] }}">
                                {{ strtoupper($comparison['confidence'] ?? 'low') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
                <p class="text-[10px] text-gray-400 mt-3">ValeCheck analysis — compares the seller's own listing text against what is visible in the supplied photographs. Not a substitute for a physical inspection.</p>
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Estimated Repairs</h3>
            <p class="font-display text-2xl font-bold text-vale-navy">£{{ number_format($repairEstimate->low_estimate ?? 0, 0) }} – £{{ number_format($repairEstimate->high_estimate ?? 0, 0) }}</p>
            <p class="text-sm text-gray-500 mt-1">Expected: £{{ number_format($repairEstimate->expected_estimate ?? 0, 0) }}</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Estimated Total Investment</h3>
            @php $totalInvestment = ($check->current_bid ?? $check->asking_price ?? 0) + ($repairEstimate->expected_estimate ?? 0) + ($bid->auction_fees ?? 0) + ($bid->transport_cost ?? 0) + ($bid->service_mot_allowance ?? 0); @endphp
            <p class="font-display text-2xl font-bold text-vale-navy">£{{ number_format($totalInvestment, 0) }}</p>
            <p class="text-sm text-gray-500 mt-1">Purchase + repairs + fees + transport + service/MOT</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Estimated Resale Value</h3>
            <p class="font-display text-2xl font-bold text-vale-navy">£{{ number_format($bid->expected_resale_value ?? 0, 0) }}</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Expected Margin</h3>
            @php $margin = ($bid->expected_resale_value ?? 0) - $totalInvestment; @endphp
            <p class="font-display text-2xl font-bold {{ $margin >= 0 ? 'text-vale-navy' : 'text-vale-red' }}">£{{ number_format($margin, 0) }}</p>
        </div>

        @if (! empty($report?->risks))
            <div class="bg-red-50 border border-red-200 rounded-xl p-5 sm:col-span-2">
                <h3 class="text-xs font-bold uppercase tracking-widest text-vale-red mb-3">Risks</h3>
                <ul class="list-disc list-inside space-y-1 text-vale-navy text-sm">
                    @foreach ($report->risks as $risk)
                        <li>{{ $risk }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($report?->listing_gaps))
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">What The Listing Doesn't Tell You</h3>
                <ul class="list-disc list-inside space-y-1 text-vale-navy text-sm">
                    @foreach ($report->listing_gaps as $gap)
                        <li>{{ $gap }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($report?->things_to_check))
            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Things You Need To Check</h3>
                <ul class="list-disc list-inside space-y-1 text-vale-navy text-sm">
                    @foreach ($report->things_to_check as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">AI Confidence &amp; Deal Score Breakdown</h3>
            <p class="text-sm text-gray-500">{{ $bid->score_explanation }}</p>
        </div>
    </div>

    <p class="text-xs text-gray-400 mt-8 leading-relaxed">
        Vehicle valuations, repair estimates, resale estimates and recommended purchase or bid amounts are estimates
        based on available vehicle, market and image data at the time of analysis. They are provided for guidance and
        informational purposes only and are not a guarantee of vehicle value, repair cost, profitability or future
        resale price. AI damage analysis is based on the information and images supplied and may fail to identify
        concealed structural, mechanical or electrical damage. It is not a substitute for a physical inspection by a
        suitably qualified person.
    </p>
</div>
