<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold text-xl text-vale-navy leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if (request('paid'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">Payment received — thank you.</div>
                @include('partials.google-ads-conversion')
            @endif
            @if (request('subscribed'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">Subscription activated — thank you.</div>
            @endif
            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm">{{ session('status') }}</div>
            @endif

            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                <div>
                    <h3 class="font-display font-bold text-lg text-vale-navy">Start a new check</h3>
                    <p class="text-gray-500 text-sm">Enter a registration to check history, damage, value and maximum bid.</p>
                </div>
                <a href="{{ route('vehicle-checks.start') }}" wire:navigate class="inline-flex items-center justify-center px-5 py-2 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600 shrink-0">
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
                        @if ($isSubscribed)
                            <a href="{{ route('billing.portal') }}" class="inline-block mt-2 text-xs font-semibold text-vale-red hover:text-red-600">Manage subscription &rarr;</a>
                        @endif
                    </div>
                @endif
            </div>

            @if ($needsTraderVerification)
                <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
                    @if ($traderVerification?->status === \App\Models\TraderVerification::STATUS_PENDING)
                        <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-2">Trade verification pending</h3>
                        <p class="text-sm text-gray-500">
                            We're reviewing the business details you submitted for {{ $traderVerification->company_name }}.
                            Trade-restricted report content (e.g. high-risk markers) will unlock once approved.
                        </p>
                    @else
                        <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-2">Verify your trade account</h3>
                        @if ($traderVerification?->status === \App\Models\TraderVerification::STATUS_REJECTED)
                            <p class="text-sm text-vale-red mb-3">
                                Your previous submission wasn't approved{{ $traderVerification->notes ? ": {$traderVerification->notes}" : '.' }} Please check your details and resubmit.
                            </p>
                        @else
                            <p class="text-sm text-gray-500 mb-3">
                                Trader and Dealer plans unlock trade-restricted report content once we've verified you're a genuine motor trader. Submit your business details below.
                            </p>
                        @endif
                        <form method="POST" action="{{ route('billing.trader-verification.store') }}" class="grid sm:grid-cols-3 gap-3">
                            @csrf
                            <div>
                                <x-input-label for="company_name" value="Company name" />
                                <x-text-input name="company_name" id="company_name" class="block mt-1 w-full" value="{{ old('company_name', $traderVerification?->company_name) }}" required />
                                <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="company_number" value="Company number" />
                                <x-text-input name="company_number" id="company_number" class="block mt-1 w-full" value="{{ old('company_number', $traderVerification?->company_number) }}" required />
                                <x-input-error :messages="$errors->get('company_number')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="vat_number" value="VAT number (optional)" />
                                <x-text-input name="vat_number" id="vat_number" class="block mt-1 w-full" value="{{ old('vat_number', $traderVerification?->vat_number) }}" />
                                <x-input-error :messages="$errors->get('vat_number')" class="mt-1" />
                            </div>
                            <div class="sm:col-span-3">
                                <x-primary-button type="submit">Submit for review</x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif

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

            @if (config('valecheck.subscriptions_enabled'))
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-widest text-gray-400 mb-3">
                        {{ $isSubscribed ? 'Change plan' : 'Subscribe for regular checks' }}
                    </h3>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach ($subscriptionPlans as $key => $plan)
                            @php $isCurrentPlan = $isSubscribed && $activeSubscriptionUsage?->plan === $key; @endphp
                            <div class="bg-white border {{ $isCurrentPlan ? 'border-vale-red' : 'border-gray-200' }} rounded-xl p-5 shadow-sm flex flex-col">
                                <p class="text-vale-navy font-semibold">{{ $plan['label'] }}</p>
                                <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">£{{ number_format($plan['price']->gross, 2) }}<span class="text-sm text-gray-400">/mo</span></p>
                                <p class="text-xs text-gray-500 mt-1 flex-1">{{ $plan['allowances']['plus'] }} Plus reports/month</p>
                                @if ($isCurrentPlan)
                                    <p class="mt-3 text-center text-xs font-semibold uppercase tracking-widest text-vale-red">Current plan</p>
                                @else
                                    <form method="POST" action="{{ route('billing.subscribe') }}">
                                        @csrf
                                        <input type="hidden" name="plan" value="{{ $key }}">
                                        <button type="submit" class="mt-3 w-full inline-flex justify-center items-center px-4 py-2 bg-vale-red hover:bg-red-600 rounded-full font-semibold text-sm text-white">
                                            {{ $isSubscribed ? 'Switch to this plan' : 'Subscribe' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                        @unless ($isSubscribed)
                            <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm flex flex-col">
                                <p class="text-vale-navy font-semibold">Enterprise</p>
                                <p class="font-display text-2xl font-extrabold text-vale-navy mt-1">Contact us</p>
                                <p class="text-xs text-gray-500 mt-1 flex-1">Custom volume and pricing for high-usage accounts.</p>
                                <a href="{{ route('contact.enterprise') }}" wire:navigate class="mt-3 w-full inline-flex justify-center items-center px-4 py-2 bg-vale-navy hover:bg-vale-navy/90 rounded-full font-semibold text-sm text-white">
                                    Contact Us
                                </a>
                            </div>
                        @endunless
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
