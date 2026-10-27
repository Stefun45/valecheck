<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @include('partials.google-ads-tag')
        <title>{{ config('app.name', 'ValeCheck') }} for Trade &amp; Dealers</title>
        @include('partials.seo-meta', [
            'title' => config('app.name', 'ValeCheck').' for Trade & Dealers',
            'description' => 'Fast UK vehicle history, provenance and damage checks built for dealers, traders and auction buyers — including salvage and Cat N/S stock. Trade verification and volume credit plans launching soon.',
            'noindex' => false,
        ])

        @include('partials.favicons')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|montserrat:600,700,800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white text-vale-navy">

        @include('partials.site-promotion-banner')

        <header class="sticky top-0 z-20 border-b border-gray-200 bg-white/90 backdrop-blur">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2">
                <x-application-logo text-class="text-base sm:text-lg" />
                <nav class="flex items-center gap-2 sm:gap-4 text-sm shrink-0">
                    @auth
                        <a href="{{ route('dashboard') }}" class="font-medium text-vale-navy hover:text-vale-red whitespace-nowrap">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-vale-navy hover:text-vale-red whitespace-nowrap">Sign in</a>
                        <a href="{{ route('vehicle-checks.start') }}" class="inline-flex items-center px-3 sm:px-5 py-1.5 sm:py-2 bg-vale-red rounded-full font-semibold text-xs sm:text-sm text-white hover:bg-red-600 transition whitespace-nowrap">Check Your Vehicle</a>
                    @endauth
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden vale-hero-wash">
            <div class="absolute inset-x-0 top-0 h-1 vale-racing-stripe"></div>

            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 text-center">
                <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.2em] text-vale-navy/70 border border-gray-200 rounded-full px-4 py-1.5 bg-white">
                    <span class="h-1.5 w-1.5 rounded-full bg-vale-red"></span>
                    For Trade &amp; Dealers
                </span>

                <h1 class="font-display font-extrabold leading-tight mt-6 text-4xl sm:text-6xl">
                    <span class="text-vale-navy">KNOW BEFORE</span><br>
                    <span class="text-vale-red">YOU BID.</span>
                </h1>

                <p class="mt-4 text-gray-500 max-w-xl mx-auto">
                    Fast, accurate vehicle checks built for how the trade actually buys — forecourt part-exchanges,
                    auction lots, and salvage stock alike.
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('vehicle-checks.start') }}" class="inline-flex items-center px-6 py-3 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600 transition">
                        Check Your First Vehicle
                    </a>
                    <a href="#trade-plans" class="inline-flex items-center px-6 py-3 border-2 border-vale-navy rounded-full font-semibold text-sm text-vale-navy hover:bg-gray-50 transition">
                        See Trade Plans
                    </a>
                </div>
            </div>
        </section>

        <!-- Built for how you buy -->
        <section class="border-t border-gray-100 py-16">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-display font-bold text-3xl sm:text-4xl text-center text-vale-navy">Built For How You Buy</h2>
                <p class="text-gray-500 mt-3 max-w-2xl mx-auto text-center">
                    Every report works on any UK plate — including the damaged, written-off and salvage stock other
                    checks gloss over.
                </p>

                <div class="grid sm:grid-cols-3 gap-6 mt-10">
                    @php
                        $useCases = [
                            ['icon' => 'trending-up', 'title' => 'At the auction', 'body' => 'Run a check from the catalogue before you bid. Results in seconds, not a trip to the viewing day.'],
                            ['icon' => 'warning', 'title' => 'Salvage & Cat N/S stock', 'body' => config('valecheck.rebuild_enabled')
                                ? 'ValeCheck Rebuild adds AI damage analysis, a repair cost estimate and a recommended maximum bid — built specifically for damaged vehicles.'
                                : 'Full write-off category history and provenance, so damaged stock is never a guessing game.'],
                            ['icon' => 'chart-bar', 'title' => 'Part-exchange & forecourt', 'body' => 'Trade and retail valuations alongside history and provenance, so you can price stock with confidence.'],
                        ];
                    @endphp
                    @foreach ($useCases as $case)
                        <div class="bg-white border border-gray-200 rounded-xl p-6 vale-card">
                            <x-section-icon :name="$case['icon']" size="28" class="text-vale-red mb-3" />
                            <h3 class="text-vale-navy font-bold text-lg">{{ $case['title'] }}</h3>
                            <p class="text-gray-500 text-sm mt-2">{{ $case['body'] }}</p>
                        </div>
                    @endforeach
                </div>

                <p class="text-center mt-10">
                    <a href="{{ route('sample-report') }}" class="text-sm font-semibold text-vale-navy hover:text-vale-red underline">See a sample report &rarr;</a>
                </p>
            </div>
        </section>

        <!-- Trade verification -->
        <section class="border-t border-gray-100 py-16 bg-vale-light-grey">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="font-display font-bold text-3xl sm:text-4xl text-vale-navy">Trade Verification</h2>
                <p class="text-gray-500 mt-3">
                    Available now. Add your company name, company number and (optionally) VAT number from your
                    account dashboard, and we'll verify it against public records. Once approved, verified trade
                    accounts see additional risk markers on reports that aren't shown to standard checks.
                </p>
                <p class="mt-6">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 bg-vale-navy rounded-full font-semibold text-sm text-white hover:bg-vale-navy/90 transition">
                            Get Verified
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 bg-vale-navy rounded-full font-semibold text-sm text-white hover:bg-vale-navy/90 transition">
                            Create A Free Account
                        </a>
                    @endauth
                </p>
            </div>
        </section>

        <!-- Trade plans (coming soon) -->
        <section id="trade-plans" class="border-t border-gray-100 py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <span class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-vale-red border border-red-100 bg-red-50 rounded-full px-4 py-1.5">
                        Coming Soon
                    </span>
                    <h2 class="font-display font-bold text-3xl sm:text-4xl text-vale-navy mt-4">Trade Credit Plans</h2>
                    <p class="text-gray-500 mt-3 max-w-xl mx-auto">
                        Monthly plans for traders and dealers who check vehicles regularly — a set number of ValeCheck
                        Plus reports included every month, at a fraction of the one-off price, with extra credits
                        whenever you need them. Prices shown exclude VAT.
                    </p>
                </div>

                @if ($plans->isNotEmpty())
                    <div class="mt-10 overflow-x-auto">
                        <table class="w-full text-sm border-separate border-spacing-y-2">
                            <thead>
                                <tr class="text-left text-xs font-bold uppercase tracking-widest text-gray-400">
                                    <th class="px-4 py-2">Plan</th>
                                    <th class="px-4 py-2">Monthly (ex VAT)</th>
                                    <th class="px-4 py-2">Reports included</th>
                                    <th class="px-4 py-2">Extra report (ex VAT)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($plans as $row)
                                    <tr class="bg-white border border-gray-200 vale-card">
                                        <td class="px-4 py-3 rounded-l-xl font-semibold text-vale-navy">
                                            {{ $row['plan']->name }}
                                            @if ($row['plan']->group === \App\Models\SubscriptionPlan::GROUP_DEALER)
                                                <span class="ml-1.5 text-[10px] font-bold uppercase tracking-wide text-gray-400">Verification required</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-vale-navy">£{{ number_format($row['price']->net, 2) }}</td>
                                        <td class="px-4 py-3 text-vale-navy">{{ number_format($row['plan']->monthly_credits) }}</td>
                                        <td class="px-4 py-3 rounded-r-xl text-vale-navy">£{{ number_format($row['plan']->additional_credit_net, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>

        <!-- Register interest -->
        <section class="border-t border-gray-100 py-16 bg-vale-light-grey">
            <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-display font-bold text-3xl text-center text-vale-navy">Register Your Interest</h2>
                <p class="text-gray-500 mt-3 text-center text-sm">
                    Leave your details and we'll let you know the moment trade plans go live.
                </p>

                @if (session('status'))
                    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl p-4 text-sm mt-6">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('trade.interest.submit') }}" class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm space-y-4 mt-6">
                    @csrf

                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" class="block mt-1 w-full" value="{{ old('name') }}" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="block mt-1 w-full" value="{{ old('email') }}" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="company" value="Company (optional)" />
                        <x-text-input id="company" name="company" class="block mt-1 w-full" value="{{ old('company') }}" />
                        <x-input-error :messages="$errors->get('company')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="message" value="Anything else we should know? (optional)" />
                        <textarea id="message" name="message" rows="3" class="block mt-1 w-full border-gray-300 focus:border-vale-navy focus:ring-vale-navy rounded-md shadow-sm">{{ old('message') }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    </div>

                    <x-primary-button class="w-full justify-center">
                        Register Interest
                    </x-primary-button>
                </form>
            </div>
        </section>

        @include('partials.footer')
        @include('partials.cookie-consent-banner')
    </body>
</html>
