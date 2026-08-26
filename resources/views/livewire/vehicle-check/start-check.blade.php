<div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    @if ($step === 'choose')
        <div class="text-center mb-8">
            <h1 class="font-display font-bold text-2xl text-vale-navy">Which check do you need?</h1>
            @if ($registration)
                <p class="text-gray-500 mt-1">Registration: <span class="font-mono text-vale-navy">{{ $registration }}</span></p>
            @endif
        </div>

        <div class="mb-6">
            <x-input-label for="registration" value="Registration" />
            <div class="flex flex-col sm:flex-row gap-3 mt-1">
                <x-text-input wire:model="registration" id="registration" class="block w-full uppercase font-mono" placeholder="AB12 CDE" />
                @unless ($vehicleConfirmed)
                    <x-primary-button type="button" wire:click="lookupVehicle" wire:loading.attr="disabled" wire:target="lookupVehicle" class="justify-center whitespace-nowrap">
                        <span wire:loading.remove wire:target="lookupVehicle">Check Vehicle</span>
                        <span wire:loading wire:target="lookupVehicle">Checking...</span>
                    </x-primary-button>
                @endunless
            </div>
            <x-input-error :messages="$errors->get('registration')" class="mt-2" />

            @if ($previewStatus === 'not_found')
                <p class="text-xs text-gray-400 mt-2">We couldn't find a quick preview for that plate, but it's still checkable below.</p>
            @elseif ($previewStatus === 'unavailable')
                <p class="text-xs text-gray-400 mt-2">The quick preview is temporarily unavailable, but you can still continue below.</p>
            @endif
        </div>

        @if ($previewStatus === 'found' && ! $vehicleConfirmed)
            <div class="relative bg-white border border-gray-200 rounded-xl p-6 text-left shadow-sm mb-8">
                @if ($this->usingMockPreviewData())
                    <span class="absolute -top-2.5 left-4 bg-amber-100 text-amber-700 text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full border border-amber-200">Simulated Data</span>
                @endif

                <div class="flex items-center gap-4">
                    <x-vehicle-silhouette :colour="$vehiclePreview['colour'] ?? null" class="h-14 w-24 shrink-0" />
                    <div class="flex-1 min-w-0">
                        <p class="text-vale-navy font-bold">
                            {{ $vehiclePreview['year'] ?? '' }} {{ ucwords(strtolower($vehiclePreview['make'] ?? 'Unknown make')) }} {{ ucwords(strtolower($vehiclePreview['model'] ?? '')) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ ucwords(strtolower($vehiclePreview['colour'] ?? '')) }}
                            @if($vehiclePreview['fuel_type'] ?? null) &middot; {{ ucwords(strtolower($vehiclePreview['fuel_type'])) }} @endif
                        </p>
                        <p class="text-xs mt-1">
                            <span class="{{ ($vehiclePreview['tax_status'] ?? null) === 'Taxed' ? 'text-green-600' : 'text-vale-red' }}">Tax: {{ $vehiclePreview['tax_status'] ?? 'Unknown' }}</span>
                            <span class="text-gray-300 mx-1">&middot;</span>
                            <span class="{{ ($vehiclePreview['mot_status'] ?? null) === 'Valid' ? 'text-green-600' : 'text-vale-red' }}">MOT: {{ $vehiclePreview['mot_status'] ?? 'Unknown' }}</span>
                        </p>
                    </div>
                </div>

                <p class="text-sm text-vale-navy font-semibold mt-4">Is this your vehicle?</p>
                <div class="flex gap-3 mt-2">
                    <x-primary-button wire:click="confirmVehicle(true)">Yes, that's it</x-primary-button>
                    <button type="button" wire:click="confirmVehicle(false)" class="inline-flex items-center justify-center px-5 py-2 border border-gray-300 text-gray-600 rounded-full font-semibold text-sm hover:bg-gray-50 transition">
                        No, try again
                    </button>
                </div>
            </div>
        @endif

        @if ($vehicleConfirmed)
            <div class="grid {{ config('valecheck.rebuild_enabled') ? 'sm:grid-cols-3' : 'sm:grid-cols-2 max-w-2xl mx-auto' }} gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm flex flex-col">
                    <h2 class="font-display font-bold text-lg text-vale-navy">ValeCheck</h2>
                    <p class="text-xs uppercase tracking-widest text-gray-400 mt-1">Check the history.</p>
                    <p class="font-display text-3xl font-extrabold text-vale-navy mt-3">£{{ number_format($checkPrice->gross, 2) }}</p>
                    <p class="text-sm text-gray-500 mt-2 flex-1">Vehicle history and provenance — write-off status, finance, mileage, MOT and keeper history.</p>
                    <button type="button" wire:click="choose('check')" class="mt-4 w-full inline-flex justify-center items-center px-4 py-2.5 bg-white hover:bg-gray-50 border-2 border-vale-navy rounded-full font-semibold text-sm text-vale-navy transition">
                        Check The History
                    </button>
                </div>

                <div class="bg-vale-light-blue border border-blue-100 rounded-xl p-6 shadow-sm flex flex-col">
                    <h2 class="font-display font-bold text-lg text-vale-navy">ValeCheck Plus</h2>
                    <p class="text-xs uppercase tracking-widest text-vale-navy/60 mt-1">Know the history. Know the value.</p>
                    <p class="font-display text-3xl font-extrabold text-vale-navy mt-3">£{{ number_format($plusPrice->gross, 2) }}</p>
                    <p class="text-sm text-vale-navy/80 mt-2 flex-1">Everything in ValeCheck, plus market valuation, retail/trade value and a resale estimate — is the asking price fair?</p>
                    <button type="button" wire:click="choose('plus')" class="mt-4 w-full inline-flex justify-center items-center px-4 py-2.5 bg-vale-navy hover:bg-vale-navy/90 border-2 border-vale-navy rounded-full font-semibold text-sm text-white transition">
                        Check The Value
                    </button>
                </div>

                @if (config('valecheck.rebuild_enabled'))
                    <div class="bg-vale-navy rounded-xl p-6 relative text-white shadow-sm flex flex-col">
                        <h2 class="font-display font-bold text-lg">ValeCheck <span class="text-vale-red">Rebuild</span></h2>
                        <p class="text-xs uppercase tracking-widest text-gray-400 mt-1">Know the damage. Know the numbers.</p>
                        <p class="font-display text-3xl font-extrabold mt-3">£{{ number_format($rebuildPrice->gross, 2) }}</p>
                        @if ($rebuildBalance > 0)
                            <p class="text-sm text-vale-red font-semibold mt-1">You have {{ $rebuildBalance }} credit(s) — this report is included.</p>
                        @endif
                        <p class="text-sm text-gray-300 mt-2 flex-1">For damaged, salvage or repairable vehicles — AI damage analysis, repair cost, repaired value, maximum bid and a Buy/Maybe/Walk Away deal score.</p>
                        <x-primary-button type="button" wire:click="choose('rebuild')" class="mt-4 w-full justify-center">
                            Rebuild It
                        </x-primary-button>
                    </div>
                @endif
            </div>
        @endif
    @endif

    @if ($step === 'details')
        <div class="mb-8">
            <h1 class="font-display font-bold text-2xl text-vale-navy">Tell us about the listing</h1>
            <p class="text-gray-500 mt-1">The more detail you give us, the more accurate your {{ $type === 'rebuild' ? 'ValeCheck Rebuild' : 'ValeCheck Plus' }} report.</p>
        </div>

        <form wire:submit="submit" class="space-y-6">
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="mileage" value="Mileage" />
                    <x-text-input wire:model="mileage" id="mileage" type="number" class="block mt-1 w-full" />
                    <x-input-error :messages="$errors->get('mileage')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="asking_price" value="Asking price (£)" />
                    <x-text-input wire:model="asking_price" id="asking_price" type="number" step="0.01" class="block mt-1 w-full" />
                </div>
                @if ($type === 'rebuild')
                    <div>
                        <x-input-label for="current_bid" value="Current bid (£)" />
                        <x-text-input wire:model="current_bid" id="current_bid" type="number" step="0.01" class="block mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label for="auction_name" value="Auction / marketplace name" />
                        <x-text-input wire:model="auction_name" id="auction_name" class="block mt-1 w-full" />
                    </div>
                @endif
            </div>

            <div>
                <x-input-label for="listing_url" value="Listing URL" />
                <div class="flex flex-col sm:flex-row gap-3 mt-1">
                    <x-text-input wire:model="listing_url" id="listing_url" class="block w-full" placeholder="https://..." />
                    <x-primary-button type="button" wire:click="importListing" wire:loading.attr="disabled" wire:target="importListing,submit" class="justify-center whitespace-nowrap">
                        <span wire:loading.remove wire:target="importListing">Import Listing</span>
                        <span wire:loading wire:target="importListing">Importing...</span>
                    </x-primary-button>
                </div>
                <x-input-error :messages="$errors->get('listing_url')" class="mt-2" />
                <p class="text-xs text-gray-400 mt-2">Optional — paste a public listing URL and we'll try to pre-fill the details below. Manual entry always works too.</p>

                @if ($importStatus === 'importing')
                    <div wire:poll.2s="refreshImportStatus" class="mt-3 text-sm text-gray-500">Importing listing&hellip;</div>
                @elseif ($importStatus === 'unavailable')
                    <p class="text-xs text-amber-600 mt-2">Automatic import is temporarily unavailable. You can continue manually below.</p>
                @elseif ($importStatus === 'found' && $importPreview)
                    <div class="mt-3 bg-green-50 border border-green-200 rounded-xl p-4">
                        <p class="text-sm font-bold text-green-800">
                            {{ $importPreview['status'] === 'success' ? 'Listing found' : 'Listing partially found' }}
                        </p>
                        <ul class="mt-2 space-y-1 text-sm text-vale-navy">
                            @foreach (['title' => 'Title', 'make' => 'Make', 'model' => 'Model', 'year' => 'Year', 'mileage' => 'Mileage', 'asking_price' => 'Asking price', 'current_bid' => 'Current bid', 'vin' => 'VIN', 'description' => 'Description'] as $key => $label)
                                @php $field = $importPreview['fields'][$key] ?? ['found' => false, 'value' => null]; @endphp
                                <li class="flex items-start gap-2">
                                    <span class="{{ $field['found'] ? 'text-green-600' : 'text-gray-300' }}">{{ $field['found'] ? '✓' : '⚠' }}</span>
                                    <span>{{ $label }}@if ($field['found']): {{ \Illuminate\Support\Str::limit((string) $field['value'], 60) }}@endif</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ $importPreview['image_count'] }} image(s) found.
                            @if ($importPreview['images_capped'])
                                We found more than {{ config('valecheck.listing_import.max_images') }} images — the first {{ config('valecheck.listing_import.max_images') }} have been imported.
                            @endif
                        </p>
                        <div class="flex gap-3 mt-3">
                            <button type="button" wire:click="useImportedData" class="inline-flex items-center justify-center px-4 py-2 bg-vale-navy text-white rounded-full font-semibold text-xs hover:bg-vale-navy/90 transition">
                                Use Imported Data
                            </button>
                            <button type="button" wire:click="$set('importStatus', 'idle')" class="text-xs text-gray-500 hover:text-vale-navy">
                                Edit / Add Information
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-2">Source: Listing import. Not verified by ValeCheck or the vehicle history provider.</p>
                    </div>
                @elseif ($importStatus === 'failed' && $importPreview)
                    <p class="text-xs text-amber-600 mt-2">
                        We couldn't automatically import this listing. You can continue manually below.
                    </p>
                @endif
            </div>

            <div>
                <x-input-label for="listing_description" value="Listing description" />
                <textarea wire:model="listing_description" id="listing_description" rows="4" class="block mt-1 w-full rounded-md border-gray-300 text-vale-navy shadow-sm focus:border-vale-red focus:ring-vale-red"></textarea>
            </div>

            @if ($type === 'rebuild')
                <div>
                    <x-input-label value="Photographs" />

                    <div
                        x-data="{ isDragging: false }"
                        x-on:dragover.prevent="isDragging = true"
                        x-on:dragleave.prevent="isDragging = false"
                        x-on:drop.prevent="
                            isDragging = false;
                            $refs.imageInput.files = $event.dataTransfer.files;
                            $refs.imageInput.dispatchEvent(new Event('change'));
                        "
                        @click="$refs.imageInput.click()"
                        :class="isDragging ? 'border-vale-red bg-red-50' : 'border-gray-300 hover:border-gray-400'"
                        class="mt-1 border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition bg-white"
                    >
                        <input type="file" x-ref="imageInput" wire:model="images" multiple accept="image/*" class="hidden">
                        <p class="text-sm text-gray-600">
                            <span class="text-vale-red font-semibold">Click to upload</span> or drag and drop photographs here
                        </p>
                        <p class="text-xs text-gray-400 mt-1">Up to {{ config('valecheck.ai.max_images') }} photographs. More angles (exterior, interior, underside) give a more confident assessment.</p>

                        <div wire:loading wire:target="images" class="text-xs text-vale-red mt-2">Uploading...</div>
                    </div>

                    <x-input-error :messages="$errors->get('images')" class="mt-2" />
                    <x-input-error :messages="$errors->get('images.*')" class="mt-2" />

                    @if ($images)
                        <div class="flex flex-wrap gap-2 mt-3">
                            @foreach ($images as $image)
                                <img src="{{ $image->temporaryUrl() }}" class="h-16 w-16 object-cover rounded-lg border border-gray-200">
                            @endforeach
                        </div>
                    @endif

                    @if ($importedImageIds)
                        <div class="flex flex-wrap gap-2 mt-3">
                            @foreach ($this->importedImages() as $image)
                                <div class="relative">
                                    <img src="{{ $image->temporaryUrl() }}" class="h-16 w-16 object-cover rounded-lg border border-gray-200">
                                    <span class="absolute -top-1 -right-1 bg-vale-navy text-white text-[9px] font-semibold px-1 rounded">Imported</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            @include('livewire.vehicle-check.partials.discount-code-field')

            <div class="flex justify-between items-center pt-2">
                <button type="button" wire:click="$set('step', 'choose')" class="text-sm text-gray-500 hover:text-vale-navy">Back</button>
                <x-primary-button type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Get my {{ $type === 'rebuild' ? 'ValeCheck Rebuild' : 'ValeCheck Plus' }} report</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </x-primary-button>
            </div>
        </form>
    @endif

    @if ($step === 'confirm')
        <div class="text-center">
            <h1 class="font-display font-bold text-2xl text-vale-navy">Confirm your ValeCheck report</h1>
            <p class="text-gray-500 mt-2">Registration: <span class="font-mono text-vale-navy">{{ $registration }}</span></p>

            @if ($discountStatus === 'found' && $discountPreview)
                <p class="font-display text-3xl font-extrabold text-vale-navy mt-4">
                    <span class="line-through text-gray-300 text-xl mr-2">£{{ number_format($discountPreview['original_price'], 2) }}</span>
                    £{{ number_format($discountPreview['discounted_price'], 2) }}
                </p>
            @else
                <p class="font-display text-3xl font-extrabold text-vale-navy mt-4">£{{ number_format($checkPrice->gross, 2) }}</p>
            @endif
            <p class="text-xs text-gray-400 mt-1">Includes VAT ({{ number_format($checkPrice->vat, 2) }} VAT at {{ $checkPrice->vatRate * 100 }}%)</p>

            <div class="max-w-xs mx-auto mt-6 text-left">
                @include('livewire.vehicle-check.partials.discount-code-field')
            </div>

            <div class="flex justify-center gap-4 mt-6">
                <button type="button" wire:click="$set('step', 'choose')" class="text-sm text-gray-500 hover:text-vale-navy">Back</button>
                <x-primary-button wire:click="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Continue to payment</span>
                    <span wire:loading wire:target="submit">Please wait...</span>
                </x-primary-button>
            </div>
        </div>
    @endif

    @if ($step === 'auth-required')
        <div class="max-w-md mx-auto text-center">
            <h1 class="font-display font-bold text-2xl text-vale-navy">Almost there</h1>
            <p class="text-gray-500 mt-2">
                Create a free account to get your {{ config("valecheck.pricing.{$type}.label") }} report for
                <span class="font-mono text-vale-navy">{{ $registration }}</span>.
            </p>

            <div class="flex justify-center gap-3 mt-6">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-5 py-2.5 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600 transition">
                    Create free account
                </a>
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-5 py-2.5 border border-gray-300 text-gray-600 rounded-full font-semibold text-sm hover:bg-gray-50 transition">
                    Log in
                </a>
            </div>

            <button type="button" wire:click="$set('step', {{ $type === 'check' ? "'confirm'" : "'details'" }})" class="text-sm text-gray-400 hover:text-vale-navy mt-6">
                &larr; Back
            </button>
        </div>
    @endif
</div>
