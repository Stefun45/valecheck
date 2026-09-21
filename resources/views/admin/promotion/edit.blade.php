<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin - Site-Wide Promotion</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm text-gray-500 mb-6">
                    A temporary discount applied automatically to everyone - no code needed. It takes effect
                    immediately everywhere a price is shown (the check wizard, checkout, upsell banners) and applies
                    to ValeCheck, ValeCheck Plus, ValeCheck Rebuild, the Plus upgrade and credit packs. It never
                    reduces an account with a bespoke custom price (Manage Account → Custom Pricing), and a customer
                    can't also apply a discount code while this is active - the two would otherwise stack.
                </p>

                <form method="POST" action="{{ route('admin.promotion.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input
                            type="checkbox"
                            id="is_active"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', $promotion->is_active))
                            class="rounded border-gray-300 text-vale-red focus:ring-vale-red"
                        >
                        <x-input-label for="is_active" value="Promotion is active" class="mb-0" />
                    </div>

                    <div>
                        <x-input-label for="percentage" value="Discount percentage" />
                        <div class="relative mt-1">
                            <x-text-input id="percentage" name="percentage" type="number" step="0.01" min="0" max="95" class="block w-full pr-8" value="{{ old('percentage', $promotion->percentage) }}" required />
                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400">%</span>
                        </div>
                        <x-input-error :messages="$errors->get('percentage')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="label" value="Banner text" />
                        <x-text-input id="label" name="label" type="text" class="block mt-1 w-full" value="{{ old('label', $promotion->label) }}" placeholder="Launch Offer" />
                        <p class="text-xs text-gray-500 mt-2">Shown to customers in the site-wide banner (e.g. "Launch Offer: 20% off every report").</p>
                    </div>

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:text-vale-navy">Cancel</a>
                        <x-primary-button type="submit">Save Promotion</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
