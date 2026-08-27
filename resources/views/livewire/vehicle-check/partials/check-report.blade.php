@php
    $vehicle = $check->vehicle;
    $history = $check->history;
    $report = $check->report;
    $plusPrice = app(\App\Services\Pricing\PricingService::class)->forPlus();
@endphp

<div>
    <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-vale-red mb-2">
        <x-application-logo text-class="text-xs" />
        <span>· Check</span>
    </div>

    <h1 class="font-display font-bold text-2xl text-vale-navy">{{ $vehicle->description() ?: $check->registration }}</h1>
    <p class="text-gray-500 font-mono">{{ $check->registration }}</p>

    <div class="mt-8 bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400">Overall History Assessment</h2>
        <p class="text-vale-navy mt-2">{{ $report?->headline_summary }}</p>
    </div>

    <div class="grid sm:grid-cols-2 gap-4 mt-6">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Vehicle Summary</h3>
            <dl class="space-y-1 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">VIN</dt><dd class="text-vale-navy font-mono">{{ $vehicle->vin ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Year</dt><dd class="text-vale-navy">{{ $vehicle->year ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Engine</dt><dd class="text-vale-navy">{{ $vehicle->engine ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Fuel</dt><dd class="text-vale-navy">{{ $vehicle->fuel ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Transmission</dt><dd class="text-vale-navy">{{ $vehicle->transmission ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Colour</dt><dd class="text-vale-navy">{{ $vehicle->colour ?? '—' }}</dd></div>
            </dl>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Write-Off History</h3>
            @if ($history?->isWrittenOff())
                <p class="text-vale-red font-semibold">Category {{ $history->write_off_category }} recorded</p>
                <p class="text-sm text-gray-500 mt-1">Date: {{ optional($history->write_off_date)->format('d M Y') ?? 'Unknown' }}</p>
            @else
                <p class="text-vale-navy">No write-off history recorded.</p>
            @endif
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Finance</h3>
            <p class="{{ $history?->finance_marker ? 'text-vale-red font-semibold' : 'text-vale-navy' }}">
                {{ $history?->finance_marker ? 'Finance marker detected' : 'No finance marker found' }}
            </p>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Stolen / Scrapped</h3>
            <p class="{{ $history?->stolen_marker ? 'text-vale-red font-semibold' : 'text-vale-navy' }}">
                Stolen: {{ $history?->stolen_marker ? 'Marker found' : 'No marker found' }}
            </p>
            <p class="{{ $history?->scrapped_marker ? 'text-vale-red font-semibold' : 'text-vale-navy' }} mt-1">
                Scrapped: {{ $history?->scrapped_marker ? 'Marker found' : 'No marker found' }}
            </p>
        </div>

        @include('livewire.vehicle-check.partials.mileage-chart', ['history' => $history])

        @include('livewire.vehicle-check.partials.mot-history-table', ['history' => $history])

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Keeper / Registration History</h3>
            <p class="text-vale-navy">Previous keepers: {{ $history?->previous_keepers ?? 'Unknown' }}</p>
            <p class="text-vale-navy mt-1">Plate changes: {{ $history?->plate_changes ?? 0 }}</p>
            <p class="text-vale-navy mt-1">Imported: {{ $history?->imported ? 'Yes' : 'No' }}</p>
        </div>

        @if (! empty($report?->listing_gaps))
            <div class="bg-red-50 border border-red-200 rounded-xl p-5 sm:col-span-2">
                <h3 class="text-xs font-bold uppercase tracking-widest text-vale-red mb-3">Important Warnings</h3>
                <ul class="list-disc list-inside space-y-1 text-vale-navy text-sm">
                    @foreach ($report->listing_gaps as $gap)
                        <li>{{ $gap }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="mt-10 border-2 border-vale-red rounded-xl p-6 bg-white text-center shadow-sm">
        <h3 class="font-display font-bold text-lg text-vale-navy">Want to know what it's actually worth?</h3>
        <p class="text-gray-500 mt-2">ValeCheck Plus adds market valuation, retail/trade value and a resale estimate — is the asking price fair?</p>
        <a href="{{ route('vehicle-checks.start', ['registration' => $check->registration]) }}" wire:navigate class="inline-flex items-center justify-center mt-4 px-5 py-2.5 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600">
            Upgrade to ValeCheck Plus — £{{ number_format($plusPrice->gross, 2) }}
        </a>
    </div>
</div>
