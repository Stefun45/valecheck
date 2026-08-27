<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (request('paid'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">Payment received — thank you.</div>
            @endif
            @if (request('subscribed'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">Subscription activated — thank you.</div>
            @endif

            <div class="flex justify-between items-center">
                <div>
                    <h3 class="font-display font-bold text-lg text-vale-navy">Start a new check</h3>
                    <p class="text-gray-500 text-sm">Enter a registration to check history, damage, value and maximum bid.</p>
                </div>
                <a href="{{ route('vehicle-checks.start') }}" wire:navigate class="inline-flex items-center px-5 py-2 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600">
                    New Check
                </a>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs uppercase tracking-widest text-gray-400">Total Plus balance</p>
                    <p class="font-display text-3xl font-extrabold text-vale-navy mt-1">{{ $plusBalance }}</p>
                </div>
                @if (config('valecheck.subscriptions_enabled'))
                    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                        <p class="text-xs uppercase tracking-widest text-gray-400">Subscription</p>
                        @if ($activeSubscriptionUsage)
                            <p class="font-display text-lg font-bold text-vale-navy mt-1 capitalize">{{ $activeSubscriptionUsage->plan }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $activeSubscriptionUsage->used }} / {{ $activeSubscriptionUsage->allowance ?? '∞' }} used this period
                            </p>
                        @else
                            <p class="font-display text-lg font-bold text-gray-400 mt-1">None</p>
                        @endif
                    </div>
                @endif
            </div>

            <div>
                <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Buy ValeCheck Plus credits</h3>
                <div class="grid sm:grid-cols-3 gap-4">
                    @foreach ($creditPacks as $key => $pack)
                        <form method="POST" action="{{ route('billing.credit-pack') }}" class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                            @csrf
                            <input type="hidden" name="pack" value="{{ $key }}">
                            <p class="text-vale-navy font-semibold">{{ $pack['label'] }}</p>
                            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">£{{ number_format($pack['price']->gross, 2) }}</p>
                            @if (($pack['discount'] ?? 0) > 0)
                                <p class="text-xs text-vale-red font-semibold mt-1">Save {{ (int) round($pack['discount'] * 100) }}%</p>
                            @endif
                            <button type="submit" class="mt-3 w-full inline-flex justify-center items-center px-4 py-2 bg-white hover:bg-gray-50 border-2 border-vale-navy rounded-full font-semibold text-sm text-vale-navy">
                                Buy
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>

            @if (config('valecheck.subscriptions_enabled') && ! $isSubscribed)
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">Subscribe for regular checks</h3>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach ($subscriptionPlans as $key => $plan)
                            <form method="POST" action="{{ route('billing.subscribe') }}" class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm flex flex-col">
                                @csrf
                                <input type="hidden" name="plan" value="{{ $key }}">
                                <p class="text-vale-navy font-semibold">{{ $plan['label'] }}</p>
                                <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">£{{ number_format($plan['price']->gross, 2) }}<span class="text-sm text-gray-400">/mo</span></p>
                                <p class="text-xs text-gray-500 mt-1 flex-1">{{ $plan['allowances']['plus'] }} Plus reports/month</p>
                                <button type="submit" class="mt-3 w-full inline-flex justify-center items-center px-4 py-2 bg-vale-red hover:bg-red-600 rounded-full font-semibold text-sm text-white">
                                    Subscribe
                                </button>
                            </form>
                        @endforeach
                        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm flex flex-col">
                            <p class="text-vale-navy font-semibold">Enterprise</p>
                            <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">Contact us</p>
                            <p class="text-xs text-gray-500 mt-1 flex-1">Custom volume and pricing for high-usage accounts.</p>
                            <a href="{{ route('contact.enterprise') }}" wire:navigate class="mt-3 w-full inline-flex justify-center items-center px-4 py-2 bg-vale-navy hover:bg-vale-navy/90 rounded-full font-semibold text-sm text-white">
                                Contact Us
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <div>
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400">Recent checks</h3>
                    <a href="{{ route('reports.index') }}" wire:navigate class="text-xs font-semibold text-vale-red hover:text-red-600">View all &rarr;</a>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100 shadow-sm">
                    @forelse ($recentChecks as $check)
                        <a href="{{ route('vehicle-checks.show', $check) }}" wire:navigate class="flex justify-between items-center p-4 hover:bg-gray-50 transition">
                            <div>
                                <p class="text-vale-navy font-mono">{{ $check->registration }}</p>
                                <p class="text-xs text-gray-400">{{ $check->vehicle?->description() }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs uppercase tracking-widest text-gray-400">{{ ucfirst($check->type) }}</p>
                                <p class="text-sm font-semibold capitalize {{ $check->status === 'failed' ? 'text-vale-red' : 'text-vale-navy' }}">{{ $check->status }}</p>
                            </div>
                        </a>
                    @empty
                        <p class="p-4 text-gray-400 text-sm">No checks yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
