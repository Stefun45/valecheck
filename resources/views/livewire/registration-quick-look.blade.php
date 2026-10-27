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

        {{-- Mobile-WEB plate scan — shown only on an actual mobile-sized viewport
             on the ordinary website, and never inside the native app itself, since
             that has its own on-device scanner elsewhere. Uploads the photo
             straight to the plate-scan endpoint, which reads it with Claude and
             never stores the photo. Mirrors start-check.blade.php's equivalent
             block, adapted to this component's registration/check(). --}}
        <div
            x-data="{ scanning: false, scanError: null }"
            x-cloak
            :class="window.Capacitor?.isNativePlatform?.() ? 'hidden' : ''"
            class="mt-3 sm:hidden"
        >
            <input
                type="file"
                accept="image/*"
                capture="environment"
                x-ref="plateScanPhotoInput"
                class="hidden"
                @change="
                    const file = $event.target.files[0];
                    if (! file) return;

                    scanning = true;
                    scanError = null;

                    const formData = new FormData();
                    formData.append('photo', file);

                    try {
                        const response = await fetch('{{ route('plate-scan') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });
                        const result = await response.json();

                        if (result.success) {
                            $wire.registration = result.registration;
                            $wire.check();
                        } else {
                            scanError = result.message || 'We couldn\'t read a number plate in that photo. Please type it in instead.';
                        }
                    } catch (e) {
                        scanError = 'We couldn\'t read a number plate in that photo. Please type it in instead.';
                    } finally {
                        scanning = false;
                        $event.target.value = '';
                    }
                "
            >

            <button
                type="button"
                @click="$refs.plateScanPhotoInput.click()"
                :disabled="scanning"
                class="inline-flex items-center justify-center gap-1.5 px-4 py-2 border border-gray-300 rounded-full text-sm font-medium text-vale-navy hover:bg-gray-50 disabled:opacity-50"
            >
                <x-section-icon name="camera" />
                <span x-show="! scanning">Scan Number Plate</span>
                <span x-show="scanning" x-cloak>Reading plate&hellip;</span>
            </button>

            <p x-show="scanError" x-cloak x-text="scanError" class="text-xs text-vale-red mt-2"></p>
        </div>
    @endif

    @if ($status === 'invalid')
        <p class="text-xs text-vale-red mt-3">That doesn't look like a valid UK registration.</p>
    @endif

    @if ($status === 'rate_limited')
        <p class="text-xs text-gray-500 mt-3">You've made a lot of quick checks in a short space of time — please try again in a little while.</p>
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
                    @include('livewire.vehicle-check.partials.mileage-chart-teaser', ['history' => $previewHistory])
                    @include('livewire.vehicle-check.partials.mot-history-teaser', ['history' => $previewHistory])
                </div>
            @endif

            <p class="text-sm text-vale-navy font-semibold mt-4">Is this your vehicle?</p>
            <div class="flex gap-3 mt-2">
                <button type="button" wire:click="confirm" class="inline-flex items-center justify-center px-5 py-2 bg-vale-red rounded-full font-semibold text-xs text-white hover:bg-red-600 transition">
                    Yes, that's it &mdash; see report options &rarr;
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
