<div>
    <x-input-label for="discount_code" value="Discount code (optional)" />
    <div class="flex flex-col sm:flex-row gap-3 mt-1">
        <x-text-input wire:model="discount_code" id="discount_code" class="block w-full uppercase" placeholder="SAVE10" />
        <button type="button" wire:click="applyDiscountCode" wire:loading.attr="disabled" wire:target="applyDiscountCode" class="inline-flex items-center justify-center px-5 py-2 border-2 border-vale-navy rounded-full font-semibold text-sm text-vale-navy hover:bg-gray-50 transition whitespace-nowrap">
            <span wire:loading.remove wire:target="applyDiscountCode">Apply</span>
            <span wire:loading wire:target="applyDiscountCode">Checking...</span>
        </button>
    </div>
    <x-input-error :messages="$errors->get('discount_code')" class="mt-2" />

    @if ($discountStatus === 'found' && $discountPreview)
        <p class="text-sm text-green-700 mt-2 font-semibold">
            Code applied — £{{ number_format($discountPreview['original_price'], 2) }} &rarr; £{{ number_format($discountPreview['discounted_price'], 2) }}
        </p>
    @elseif ($discountStatus === 'invalid')
        <p class="text-xs text-vale-red mt-2">This code isn't valid or has expired.</p>
    @endif
</div>
