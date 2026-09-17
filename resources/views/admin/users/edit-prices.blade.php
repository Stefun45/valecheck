<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">Admin — Manage Account</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

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

            <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                <p class="text-xs uppercase tracking-widest text-gray-400 mb-4">Free Credits</p>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-vale-light-grey rounded-xl p-4 text-center">
                        <p class="text-xs uppercase tracking-widest text-gray-400">Plus balance</p>
                        <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">{{ $creditBalances['plus'] }}</p>
                    </div>
                    <div class="bg-vale-light-grey rounded-xl p-4 text-center">
                        <p class="text-xs uppercase tracking-widest text-gray-400">Rebuild balance</p>
                        <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">{{ $creditBalances['rebuild'] }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.users.grant-credits', $user) }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="report_type" value="Credit type" />
                        <select id="report_type" name="report_type" class="block mt-1 w-full rounded-md border-gray-300 text-vale-navy shadow-sm focus:border-vale-red focus:ring-vale-red" required>
                            <option value="plus" @selected(old('report_type') === 'plus')>ValeCheck Plus</option>
                            <option value="rebuild" @selected(old('report_type') === 'rebuild')>ValeCheck Rebuild</option>
                        </select>
                        <x-input-error :messages="$errors->get('report_type')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="amount" value="Number of credits" />
                        <x-text-input id="amount" name="amount" type="number" min="1" max="100" class="block mt-1 w-full" value="{{ old('amount', 1) }}" required />
                        <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="note" value="Reason (optional, for your own records)" />
                        <x-text-input id="note" name="note" class="block mt-1 w-full" value="{{ old('note') }}" placeholder="e.g. goodwill after a failed report" />
                        <x-input-error :messages="$errors->get('note')" class="mt-2" />
                    </div>

                    <div class="flex justify-end pt-2">
                        <x-primary-button type="submit">Grant Credits</x-primary-button>
                    </div>
                </form>

                @if ($recentGrants->isNotEmpty())
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <p class="text-xs uppercase tracking-widest text-gray-400 mb-3">Recent Grants</p>
                        <ul class="space-y-2 text-sm">
                            @foreach ($recentGrants as $grant)
                                <li class="flex justify-between gap-4">
                                    <span class="text-gray-500">{{ $grant->created_at->format('d M Y') }} &middot; {{ $grant->note }}</span>
                                    <span class="text-vale-navy font-semibold whitespace-nowrap">+{{ $grant->amount }} {{ ucfirst($grant->report_type) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
