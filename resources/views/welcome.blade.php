<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'ValeCheck') }} — Know Before You Buy</title>

        @include('partials.favicons')

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|montserrat:600,700,800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-white text-vale-navy">

        <header class="sticky top-0 z-20 border-b border-gray-200 bg-white/90 backdrop-blur">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2">
                <x-application-logo text-class="text-base sm:text-lg" />
                <nav class="flex items-center gap-2 sm:gap-4 text-sm shrink-0">
                    @auth
                        <a href="{{ route('dashboard') }}" class="font-medium text-vale-navy hover:text-vale-red whitespace-nowrap">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="font-medium text-vale-navy hover:text-vale-red whitespace-nowrap">Sign in</a>
                        <a href="{{ route('register') }}" class="hidden sm:inline font-medium text-vale-navy hover:text-vale-red whitespace-nowrap">Register</a>
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
                    UK Vehicle History &amp; Value
                </span>

                <h1 class="font-display font-extrabold leading-tight mt-6 text-4xl sm:text-6xl">
                    <span class="text-vale-navy">KNOW BEFORE</span><br>
                    <span class="text-vale-red">YOU BUY.</span>
                </h1>

                <p class="mt-4 text-gray-500 max-w-xl mx-auto">
                    Get the full history, condition and value before you make your decision.
                </p>

                <div class="mt-8 max-w-lg mx-auto text-left">
                    <livewire:registration-quick-look />
                </div>

                <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 mt-8 text-xs font-medium text-gray-500">
                    <span class="inline-flex items-center gap-1.5"><span class="text-vale-red">✓</span> DVLA Data</span>
                    <span class="inline-flex items-center gap-1.5"><span class="text-vale-red">✓</span> Secure</span>
                    <span class="inline-flex items-center gap-1.5"><span class="text-vale-red">✓</span> Instant Results</span>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="border-t border-gray-100 py-16">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-display font-bold text-3xl sm:text-4xl text-center text-vale-navy">How It Works</h2>
                <div class="grid sm:grid-cols-3 gap-6 mt-10">
                    @php
                        $steps = [
                            ['n' => '01', 'title' => 'Enter the registration', 'body' => 'Any UK plate — private sale, dealer, auction or salvage listing.'],
                            ['n' => '02', 'title' => 'We check everything', 'body' => 'History, provenance, mileage, photographs and market value — all in one pass.'],
                            ['n' => '03', 'title' => 'Get a straight answer', 'body' => config('valecheck.rebuild_enabled')
                                ? 'BUY, MAYBE or WALK AWAY, with an estimated maximum you should ever bid.'
                                : 'A clear report on history, provenance and — with ValeCheck Plus — what it\'s actually worth.'],
                        ];
                    @endphp
                    @foreach ($steps as $step)
                        <div class="relative bg-white border border-gray-200 rounded-xl p-6 vale-card">
                            <span class="font-display text-5xl font-extrabold text-vale-light-grey">{{ $step['n'] }}</span>
                            <h3 class="text-vale-navy font-bold text-lg mt-2">{{ $step['title'] }}</h3>
                            <p class="text-gray-500 text-sm mt-2">{{ $step['body'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Why ValeCheck -->
        <section class="border-t border-gray-100 py-16 bg-vale-light-grey">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="font-display font-bold text-3xl sm:text-4xl text-vale-navy">Check Every Car Before You Buy.</h2>
                <p class="text-gray-500 mt-3 max-w-2xl mx-auto">
                    Because the listing only tells you what the seller wants you to know. ValeCheck looks deeper —
                    for private buyers, dealer purchases, AutoTrader and eBay listings, auctions and salvage alike.
                    Don't take the listing at face value.
                </p>

                @php
                    $features = [
                        ['label' => 'History', 'icon' => '<path d="M8 4h8a2 2 0 0 1 2 2v13a1 1 0 0 1-1.4.9L12 18l-4.6 1.9A1 1 0 0 1 6 19V6a2 2 0 0 1 2-2Z"/><path d="M9 8h6M9 11h6"/>'],
                        ['label' => 'Mileage', 'icon' => '<path d="M4 15a8 8 0 1 1 16 0"/><path d="M12 15l4-5"/><circle cx="12" cy="15" r="1"/>'],
                        ['label' => 'Write-off', 'icon' => '<path d="M12 4l9 15H3L12 4z"/><path d="M12 10v4"/><circle cx="12" cy="17" r="0.8" fill="currentColor" stroke="none"/>'],
                        ['label' => 'Finance', 'icon' => '<circle cx="12" cy="12" r="8.5"/><path d="M14 9.5c0-1-1-1.5-2-1.5s-2 .5-2 1.5c0 2 4 1.3 4 3.3 0 1-1 1.7-2 1.7s-2-.7-2-1.7M12 7v1.2M12 15.8V17"/>'],
                        ['label' => 'Theft', 'icon' => '<rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>'],
                        ['label' => 'Damage', 'icon' => '<path d="M4 17l3-7h10l3 7"/><path d="M4 17h16v2H4z"/><circle cx="8" cy="19.5" r="1.2"/><circle cx="16" cy="19.5" r="1.2"/>'],
                        ['label' => 'Value', 'icon' => '<rect x="3" y="7" width="18" height="12" rx="2"/><circle cx="12" cy="13" r="2.5"/><path d="M3 10h18"/>'],
                        ['label' => 'Repairs', 'icon' => '<path d="M14.5 6.5a3 3 0 0 0-4.2 4.2L4 17l3 3 6.3-6.3a3 3 0 0 0 4.2-4.2l-2.1 2.1-2-2 2.1-2.1z"/>'],
                        ['label' => 'Risk', 'icon' => '<path d="M12 4l7 3v5c0 4.5-3 7.5-7 8-4-.5-7-3.5-7-8V7l7-3z"/><path d="M9 12l2 2 4-4"/>'],
                    ];
                @endphp
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-10 text-left">
                    @foreach ($features as $feature)
                        <div class="bg-white border border-gray-200 rounded-xl p-5 vale-card">
                            <svg viewBox="0 0 24 24" fill="none" stroke="#10243A" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-7 w-7 mb-3">
                                {!! $feature['icon'] !!}
                            </svg>
                            <span class="text-vale-navy font-semibold">{{ $feature['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-6">Vehicle Summary, Write-Off History, Finance, Stolen / Scrapped, and Keeper Registration History — Data provided by Experian.</p>
            </div>
        </section>

        <!-- What the listing doesn't tell you -->
        <section class="border-t border-gray-100 py-16">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-display font-bold text-3xl sm:text-4xl text-center text-vale-navy">What The Listing Doesn't Tell You</h2>
                <div class="grid sm:grid-cols-2 gap-4 mt-10">
                    @php
                        $examples = [
                            '"Minor front-end damage."' => 'What does that actually mean?',
                            '"Cat N."' => 'What is it really worth?',
                            '"One careful owner."' => 'Does the history agree?',
                            '"Bargain price."' => 'Is it actually a bargain?',
                        ];
                    @endphp
                    @foreach ($examples as $quote => $question)
                        <div class="relative border border-gray-200 rounded-xl p-5 pl-6 vale-card overflow-hidden bg-white">
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-vale-red"></div>
                            <p class="text-gray-400 italic">{{ $quote }}</p>
                            <p class="text-vale-navy font-semibold mt-2 text-lg">{{ $question }}</p>
                        </div>
                    @endforeach
                </div>
                <p class="text-center mt-10">
                    <a href="{{ route('vehicle-checks.start') }}" class="inline-flex items-center px-6 py-3 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600 transition">
                        Check Before You Buy
                    </a>
                </p>
            </div>
        </section>

        <!-- Pricing -->
        <section class="border-t border-gray-100 py-16 bg-vale-light-grey">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-display font-bold text-3xl sm:text-4xl text-center text-vale-navy">Which Check Do You Need?</h2>
                <p class="text-gray-500 mt-3 max-w-2xl mx-auto text-center">
                    @if (config('valecheck.rebuild_enabled'))
                        Three products for three different needs — not basic, better, best.
                    @else
                        Two products for two different needs — not basic and better.
                    @endif
                </p>

                <!-- Progression strip -->
                <div class="hidden sm:flex items-center justify-center gap-3 mt-10 text-xs font-bold uppercase tracking-widest text-gray-400">
                    <span>£{{ number_format($checkPrice->gross, 2) }} Check It</span>
                    <span class="text-vale-red">&rarr;</span>
                    <span>£{{ number_format($plusPrice->gross, 2) }} Value It</span>
                    @if (config('valecheck.rebuild_enabled'))
                        <span class="text-vale-red">&rarr;</span>
                        <span>£{{ number_format($rebuildPrice->gross, 2) }} Rebuild It</span>
                    @endif
                </div>

                <div class="grid {{ config('valecheck.rebuild_enabled') ? 'sm:grid-cols-3' : 'sm:grid-cols-2 max-w-3xl mx-auto' }} gap-6 mt-6 items-stretch">
                    <div class="bg-white border border-gray-200 rounded-xl p-8 vale-card flex flex-col">
                        <h3 class="text-xl font-bold font-display text-vale-navy">ValeCheck</h3>
                        <p class="text-xs uppercase tracking-widest text-gray-400 mt-1">Check the history.</p>
                        <p class="font-display text-4xl font-extrabold mt-4 text-vale-navy">£{{ number_format($checkPrice->gross, 2) }}</p>
                        <p class="text-gray-500 mt-3 text-sm">For buyers who want to understand a vehicle's background.</p>
                        <ul class="mt-5 space-y-1.5 text-sm text-gray-600 flex-1">
                            @foreach (['Vehicle history', 'Provenance', 'Write-off history', 'Finance', 'Stolen/scrapped markers', 'MOT & mileage', 'Keeper/registration history'] as $feature)
                                <li class="flex items-center gap-2"><span class="text-vale-red font-black">✓</span> {{ $feature }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('vehicle-checks.start') }}" class="mt-6 inline-flex items-center justify-center w-full px-4 py-2.5 border-2 border-vale-navy rounded-full font-semibold text-sm text-vale-navy hover:bg-gray-50 transition">
                            Check The History
                        </a>
                    </div>

                    <div class="bg-vale-light-blue border border-blue-100 rounded-xl p-8 vale-card flex flex-col">
                        <h3 class="text-xl font-bold font-display text-vale-navy">ValeCheck Plus</h3>
                        <p class="text-xs uppercase tracking-widest text-vale-navy/60 mt-1">Know the history. Know the value.</p>
                        <p class="font-display text-4xl font-extrabold mt-4 text-vale-navy">£{{ number_format($plusPrice->gross, 2) }}</p>
                        <p class="text-vale-navy/80 mt-3 text-sm">For buyers who want to know whether the car is worth the asking price.</p>
                        <p class="text-xs text-vale-navy/60 mt-3 font-semibold">Everything in ValeCheck, plus:</p>
                        <ul class="mt-2 space-y-1.5 text-sm text-vale-navy flex-1">
                            @foreach (['Market valuation', 'Retail value', 'Trade value', 'Comparable vehicles', 'Market assessment', 'Resale estimate'] as $feature)
                                <li class="flex items-center gap-2"><span class="text-vale-red font-black">✓</span> {{ $feature }}</li>
                            @endforeach
                        </ul>
                        <a href="{{ route('vehicle-checks.start') }}" class="mt-6 inline-flex items-center justify-center w-full px-4 py-2.5 bg-vale-navy rounded-full font-semibold text-sm text-white hover:bg-vale-navy/90 transition">
                            Check The Value
                        </a>
                    </div>

                    @if (config('valecheck.rebuild_enabled'))
                        <div class="bg-vale-navy border border-vale-navy rounded-xl p-8 relative vale-card text-white flex flex-col">
                            <span class="absolute -top-3 right-6 bg-vale-red text-white text-xs font-bold uppercase px-3 py-1 rounded-full">Specialist</span>
                            <h3 class="text-xl font-bold font-display">ValeCheck <span class="text-vale-red">Rebuild</span></h3>
                            <p class="text-xs uppercase tracking-widest text-gray-400 mt-1">Know the damage. Know the numbers.</p>
                            <p class="font-display text-4xl font-extrabold mt-4">£{{ number_format($rebuildPrice->gross, 2) }}</p>
                            <p class="text-gray-300 mt-3 text-sm">For buyers considering damaged, salvage or repairable vehicles.</p>
                            <p class="text-xs text-gray-400 mt-3 font-semibold">Everything in ValeCheck Plus, plus:</p>
                            <ul class="mt-2 space-y-1.5 text-sm flex-1">
                                @foreach (['AI damage analysis', 'Repair assessment', 'Parts requirements', 'Repair cost estimate', 'Repaired vehicle value', 'Total investment', 'Resale estimate', 'Recommended purchase price', 'Estimated max bid', 'Deal score', 'BUY / MAYBE / WALK AWAY'] as $feature)
                                    <li class="flex items-center gap-2"><span class="text-vale-red font-black">✓</span> {{ $feature }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ route('vehicle-checks.start') }}" class="mt-6 inline-flex items-center justify-center w-full px-4 py-2.5 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600 transition">
                                Rebuild It
                            </a>
                        </div>
                    @endif
                </div>

            </div>
        </section>

        @include('partials.footer')
    </body>
</html>
