@php
    $discountCode = $discountCode ?? null;
    $selectedProducts = old('applicable_products', $discountCode?->applicable_products ?? []);
@endphp

<div>
    <x-input-label for="type" value="Type" />
    <select id="type" name="type" class="block mt-1 w-full rounded-md border-gray-300 text-vale-navy shadow-sm focus:border-vale-red focus:ring-vale-red" required>
        <option value="percentage" @selected(old('type', $discountCode?->type) === 'percentage')>Percentage off</option>
        <option value="fixed" @selected(old('type', $discountCode?->type) === 'fixed')>Fixed amount off (£)</option>
    </select>
    <x-input-error :messages="$errors->get('type')" class="mt-2" />
</div>

<div>
    <x-input-label for="value" value="Value" />
    <x-text-input id="value" name="value" type="number" step="0.01" min="0.01" class="block mt-1 w-full" value="{{ old('value', $discountCode?->value) }}" required />
    <x-input-error :messages="$errors->get('value')" class="mt-2" />
</div>

<div>
    <x-input-label value="Applies to" />
    <div class="flex gap-4 mt-2">
        @foreach (['check' => 'ValeCheck', 'plus' => 'ValeCheck Plus', 'rebuild' => 'ValeCheck Rebuild'] as $key => $label)
            <label class="flex items-center gap-2 text-sm text-vale-navy">
                <input type="checkbox" name="applicable_products[]" value="{{ $key }}" @checked(in_array($key, $selectedProducts ?: [], true))>
                {{ $label }}
            </label>
        @endforeach
    </div>
    <p class="text-xs text-gray-400 mt-1">Leave all unchecked to apply to every product.</p>
    <x-input-error :messages="$errors->get('applicable_products')" class="mt-2" />
</div>

<div>
    <x-input-label for="max_redemptions" value="Maximum redemptions (optional)" />
    <x-text-input id="max_redemptions" name="max_redemptions" type="number" min="1" class="block mt-1 w-full" value="{{ old('max_redemptions', $discountCode?->max_redemptions) }}" />
    <x-input-error :messages="$errors->get('max_redemptions')" class="mt-2" />
</div>

<div>
    <x-input-label for="expires_at" value="Expires (optional)" />
    <x-text-input id="expires_at" name="expires_at" type="date" class="block mt-1 w-full" value="{{ old('expires_at', $discountCode?->expires_at?->format('Y-m-d')) }}" />
    <x-input-error :messages="$errors->get('expires_at')" class="mt-2" />
</div>
