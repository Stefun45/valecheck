<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — Pricing</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm text-gray-500 mb-6">
                    Prices are VAT-inclusive and take effect immediately — anywhere the price is shown (landing page,
                    the check wizard, checkout, upsell banners) reads from here. Existing pending/completed checks
                    already charged are not affected.
                </p>

                <form method="POST" action="{{ route('admin.pricing.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="check" value="ValeCheck" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                            <x-text-input id="check" name="check" type="number" step="0.01" min="0.01" class="block w-full pl-7" value="{{ old('check', $prices['check'] ?? '') }}" required />
                        </div>
                        <x-input-error :messages="$errors->get('check')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="plus" value="ValeCheck Plus" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                            <x-text-input id="plus" name="plus" type="number" step="0.01" min="0.01" class="block w-full pl-7" value="{{ old('plus', $prices['plus'] ?? '') }}" required />
                        </div>
                        <x-input-error :messages="$errors->get('plus')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="rebuild" value="ValeCheck Rebuild" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                            <x-text-input id="rebuild" name="rebuild" type="number" step="0.01" min="0.01" class="block w-full pl-7" value="{{ old('rebuild', $prices['rebuild'] ?? '') }}" required />
                        </div>
                        <x-input-error :messages="$errors->get('rebuild')" class="mt-2" />
                        @unless (config('valecheck.rebuild_enabled'))
                            <p class="text-xs text-amber-600 mt-2">Rebuild is currently hidden from the storefront — this price will apply once it's switched back on.</p>
                        @endunless
                    </div>

                    <div>
                        <x-input-label for="plus_upgrade" value="Upgrade to ValeCheck Plus" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                            <x-text-input id="plus_upgrade" name="plus_upgrade" type="number" step="0.01" min="0.01" class="block w-full pl-7" value="{{ old('plus_upgrade', $prices['plus_upgrade'] ?? '') }}" required />
                        </div>
                        <x-input-error :messages="$errors->get('plus_upgrade')" class="mt-2" />
                        <p class="text-xs text-gray-500 mt-2">Shown on a completed ValeCheck report to upgrade that same report to Plus, without checking the vehicle again.</p>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:text-vale-navy">Cancel</a>
                        <x-primary-button type="submit">Save Prices</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
