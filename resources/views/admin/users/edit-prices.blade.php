<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — Custom Pricing</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-xs uppercase tracking-widest text-gray-400 mb-1">Account</p>
                <p class="text-vale-navy font-semibold mb-6">{{ $user->name }} &middot; {{ $user->email }}</p>

                <p class="text-sm text-gray-500 mb-6">
                    Set what this one account pays per report, overriding the standard price. Leave a field blank
                    to charge this account the standard price shown as its placeholder. Set a price to <strong>0</strong>
                    to skip the formal checkout entirely — the check goes straight to processing with no Stripe
                    payment involved.
                </p>

                <form method="POST" action="{{ route('admin.users.update-prices', $user) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    @foreach (['check' => 'ValeCheck', 'plus' => 'ValeCheck Plus', 'rebuild' => 'ValeCheck Rebuild'] as $type => $label)
                        <div>
                            <x-input-label :for="$type" :value="$label" />
                            <div class="relative mt-1">
                                <span class="absolute inset-y-0 left-3 flex items-center text-gray-400">£</span>
                                <x-text-input
                                    :id="$type"
                                    :name="$type"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="block w-full pl-7"
                                    value="{{ old($type, $overrides[$type] ?? '') }}"
                                    placeholder="{{ number_format($standardPrices[$type] ?? 0, 2) }} (standard)"
                                />
                            </div>
                            <x-input-error :messages="$errors->get($type)" class="mt-2" />
                        </div>
                    @endforeach

                    <div class="flex justify-between items-center pt-2">
                        <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-500 hover:text-vale-navy">Cancel</a>
                        <x-primary-button type="submit">Save Custom Prices</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
