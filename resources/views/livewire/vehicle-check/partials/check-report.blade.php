@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $report = $check->report;
    $upgradePrice = app(\App\Services\Pricing\PricingService::class)->forProduct('plus_upgrade');
@endphp

<div>
    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-vale-red mb-2">
        <x-application-logo text-class="text-xs" />
        <span>· Check</span>
    </div>

    <h1 class="font-display font-bold text-2xl text-vale-navy">{{ $vehicle->description() ?: $check->registration }}</h1>
    <p class="text-gray-500 font-mono">{{ $check->registration }}</p>

    <div class="mt-6">
        @include('livewire.vehicle-check.partials.report-status-grid', ['history' => $history])
    </div>

    <div class="mt-2 bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h2 class="flex items-center gap-1.5 text-sm font-bold uppercase tracking-widest text-gray-400"><x-section-icon name="document" />Overall History Assessment</h2>
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
            <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="user" />Keeper / Registration History</h3>
            <p class="text-vale-navy">Previous keepers: {{ $history?->previous_keepers ?? 'Unknown' }}</p>
            <p class="text-vale-navy mt-1">Plate changes: {{ $history?->plate_changes ?? 0 }}</p>
            <p class="text-vale-navy mt-1">Imported: {{ is_null($history?->imported) ? 'Unavailable' : ($history->imported ? 'Yes' : 'No') }}</p>
        </div>

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
    </div>

    @if ($check->isUpgradeable())
        <div class="mt-10 border-2 border-vale-red rounded-xl p-6 bg-white text-center shadow-sm">
            <h3 class="font-display font-bold text-lg text-vale-navy">Want to know what it's actually worth?</h3>
            <p class="text-gray-500 mt-2">Upgrade this report to ValeCheck Plus for market valuation, real tax cost and salvage auction history — no need to check the vehicle again.</p>
            <a href="{{ route('checkout.vehicle-check.upgrade', $check) }}" class="inline-flex items-center justify-center mt-4 px-5 py-2.5 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600">
                Upgrade to ValeCheck Plus — £{{ number_format($upgradePrice->gross, 2) }}
            </a>
        </div>
    @endif
</div>
