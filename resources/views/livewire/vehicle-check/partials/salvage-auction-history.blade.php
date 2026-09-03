<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2">
    <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-3"><x-section-icon name="shield" />Salvage Auction History</h3>
    @if (! $salvageAuctionCheck?->record_found)
        <p class="text-gray-400">No salvage auction record found for this vehicle.</p>
    @else
        <div class="space-y-4">
            @foreach ($salvageAuctionCheck->records as $record)
                <div class="border border-gray-100 rounded-lg p-4">
                    <p class="text-vale-navy font-semibold">{{ $record['lotDescription'] ?? 'Salvage auction listing' }}</p>
                    <dl class="grid sm:grid-cols-2 gap-x-4 gap-y-1 text-sm mt-2">
                        <div class="flex justify-between"><dt class="text-gray-500">Auction date</dt><dd class="text-vale-navy">{{ $record['lotDate'] ? \Illuminate\Support\Carbon::parse($record['lotDate'])->format('d M Y') : '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Mileage at auction</dt><dd class="text-vale-navy">{{ $record['mileage'] ? number_format($record['mileage']).' mi' : '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Primary damage</dt><dd class="text-vale-navy">{{ $record['primaryDamageDescription'] ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Secondary damage</dt><dd class="text-vale-navy">{{ $record['secondaryDamageDescription'] ?? '—' }}</dd></div>
                        <div class="flex justify-between sm:col-span-2"><dt class="text-gray-500">Location</dt><dd class="text-vale-navy">{{ $record['location'] ?? '—' }}</dd></div>
                    </dl>
                    @if (! empty($record['imageUrls']))
                        {{-- Linked out, never embedded — avoids image
                             licensing issues, and Experian doesn't want
                             photographs embedded in reports using their
                             data. --}}
                        <div class="flex flex-wrap gap-3 mt-3">
                            @foreach ($record['imageUrls'] as $index => $imageUrl)
                                <a href="{{ $imageUrl }}" target="_blank" rel="noopener" class="text-xs text-vale-red hover:text-red-600 underline">View photo {{ $index + 1 }} &#8599;</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-3">Salvage auction photographs, where available, are linked to their original source rather than shown here, as taken at the time of listing.</p>
    @endif
</div>
