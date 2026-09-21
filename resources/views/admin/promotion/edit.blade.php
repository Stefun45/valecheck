<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin - Site-Wide Promotion</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-sm text-gray-500 mb-6">
                    Set an explicit discounted price for any plan you want on offer right now - no code needed for
                    the customer. It takes effect immediately everywhere a price is shown (the check wizard, the
                    homepage, checkout, upsell banners). It never reduces an account with a bespoke custom price
                    (Manage Account, Custom Pricing), and a customer can't also apply a discount code on a plan
                    while its promotion is active - the two would otherwise stack.
                </p>

                <form method="POST" action="{{ route('admin.promotion.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @foreach ([
                        'check' => 'ValeCheck',
                        'plus' => 'ValeCheck Plus',
                        'rebuild' => 'ValeCheck Rebuild',
                        'plus_upgrade' => 'Upgrade to ValeCheck Plus',
                    ] as $type => $label)
                        @php $promotion = $promotions[$type]; @endphp
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <span class="font-semibold text-vale-navy">{{ $label }}</span>
                                <span class="text-sm text-gray-400">Standard price: £{{ number_format($standardPrices[$type] ?? 0, 2) }}</span>
                            </div>

                            <div class="flex items-center gap-3 mb-3">
                                <input type="hidden" name="promotions[{{ $type }}][is_active]" value="0">
                                <input
                                    type="checkbox"
                                    id="active_{{ $type }}"
                                    name="promotions[{{ $type }}][is_active]"
                                    value="1"
                                    @checked(old("promotions.{$type}.is_active", $promotion->is_active))
                                    class="rounded border-gray-300 text-vale-red focus:ring-vale-red"
                                >
                                <x-input-label for="active_{{ $type }}" value="On offer" class="mb-0" />
                            </div>

                            <div>
                                <x-input-label for="discounted_{{ $type }}" value="Discounted price" />
                                <div class="relative mt-1 max-w-xs">
                                    <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                                    <x-text-input
                                        id="discounted_{{ $type }}"
                                        name="promotions[{{ $type }}][discounted_gross]"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="block w-full pl-7"
                                        value="{{ old("promotions.{$type}.discounted_gross", $promotion->discounted_gross) }}"
                                    />
                                </div>
                                <x-input-error :messages="$errors->get('promotions.'.$type.'.discounted_gross')" class="mt-2" />
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:text-vale-navy">Cancel</a>
                        <x-primary-button type="submit">Save Promotion</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
