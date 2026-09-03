<div>
    @if ($status !== 'found')
        <form wire:submit="check" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 flex items-stretch rounded-full border border-gray-300 bg-white overflow-hidden focus-within:border-vale-red focus-within:ring-1 focus-within:ring-vale-red shadow-sm">
                <span class="flex items-center gap-1.5 px-3 bg-vale-navy text-white text-xs font-bold">
                    <svg viewBox="0 0 60 30" class="h-[14px] w-[28px] rounded-[2px] shrink-0" aria-hidden="true">
                        <rect width="60" height="30" fill="#012169"/>
                        <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
                        <path d="M0,0 L27,13.5 M33,13.5 L60,0 M0,30 L27,16.5 M33,16.5 L60,30" stroke="#C8102E" stroke-width="4"/>
                        <path d="M30,0 V30 M0,15 H60" stroke="#fff" stroke-width="10"/>
                        <path d="M30,0 V30 M0,15 H60" stroke="#C8102E" stroke-width="6"/>
                    </svg>
                    GB
                </span>
                <input type="text" id="registration" wire:model="registration" name="registration" placeholder="AB12 CDE" required
                    class="flex-1 border-0 uppercase font-mono text-lg text-center sm:text-left text-vale-navy placeholder-gray-400 focus:ring-0 px-3 py-3">
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="check"
                class="inline-flex items-center justify-center px-6 py-3 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600 transition disabled:opacity-60">
                <span wire:loading.remove wire:target="check">Check Vehicle for Free &rarr;</span>
                <span wire:loading wire:target="check">Checking...</span>
            </button>
        </form>
    @endif

    @if ($status === 'invalid')
        <p class="text-xs text-vale-red mt-3">That doesn't look like a valid UK registration.</p>
    @endif

    @if ($status === 'found' && $preview)
        <div class="relative bg-white border border-gray-200 rounded-xl p-5 text-left shadow-sm">
            @if ($this->usingMockData())
                <span class="absolute -top-2.5 left-4 bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full border border-amber-200">Simulated Data</span>
            @endif

            <div class="flex items-center gap-4">
                <x-vehicle-silhouette :colour="$preview['colour']" class="h-14 w-24 shrink-0" />
                <div class="flex-1 min-w-0">
                    <p class="text-vale-navy font-bold">
                        {{ $preview['year'] }} {{ ucwords(strtolower($preview['make'] ?? 'Unknown make')) }} {{ ucwords(strtolower($preview['model'] ?? '')) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ ucwords(strtolower($preview['colour'] ?? '')) }}
                        @if($preview['fuel_type']) &middot; {{ ucwords(strtolower($preview['fuel_type'])) }} @endif
                        @if($preview['engine_capacity']) &middot; {{ number_format($preview['engine_capacity']) }}cc @endif
                    </p>
                    <p class="text-xs mt-1">
                        <span class="{{ $preview['tax_status'] === 'Taxed' ? 'text-green-600' : 'text-vale-red' }}">Tax: {{ $preview['tax_status'] ?? 'Unknown' }}</span>
                        <span class="text-gray-300 mx-1">&middot;</span>
                        <span class="{{ $preview['mot_status'] === 'Valid' ? 'text-green-600' : 'text-vale-red' }}">MOT: {{ $preview['mot_status'] ?? 'Unknown' }}</span>
                    </p>
                </div>
            </div>

            @if (! empty($preview['mot_history']))
                @php
                    $previewHistory = new \App\Models\VehicleHistory(['mot_history' => $preview['mot_history'], 'mileage_anomaly' => false]);
                @endphp
                <div class="grid sm:grid-cols-2 gap-4 mt-4">
                    @include('livewire.vehicle-check.partials.mileage-chart', ['history' => $previewHistory])
                    @include('livewire.vehicle-check.partials.mot-history-table', ['history' => $previewHistory])
                </div>
            @endif

            <p class="text-sm text-vale-navy font-semibold mt-4">Is this your vehicle?</p>
            <div class="flex gap-3 mt-2">
                <button type="button" wire:click="confirm" class="inline-flex items-center justify-center px-5 py-2 bg-vale-red rounded-full font-semibold text-xs text-white hover:bg-red-600 transition">
                    Yes, that's it &rarr;
                </button>
                <button type="button" wire:click="reject" class="inline-flex items-center justify-center px-5 py-2 border border-gray-300 text-gray-600 rounded-full font-semibold text-xs hover:bg-gray-50 transition">
                    No, try again
                </button>
            </div>

            <p class="text-xs text-gray-400 mt-3">
                @if ($this->usingMockData())
                    Simulated for local development — no registration lookup provider is configured yet.
                @else
                    This is a quick look, not the full report. History, damage analysis, valuation and an estimated maximum bid are calculated after checkout.
                @endif
            </p>
        </div>
    @endif

    @if ($status === 'not_found')
        <p class="text-xs text-gray-500 mt-3">We couldn't find a quick preview for that plate, but it may still be checkable in the full report — <button type="button" wire:click="confirm" class="underline hover:text-vale-navy">continue anyway</button>.</p>
    @endif

    @if ($status === 'unavailable')
        <p class="text-xs text-gray-500 mt-3">The quick preview is temporarily unavailable — you can still <button type="button" wire:click="confirm" class="underline hover:text-vale-navy">continue to the full check</button>.</p>
    @endif
</div>
