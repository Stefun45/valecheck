@extends('layouts.legal', [
    'title' => 'Frequently Asked Questions',
    'description' => 'Answers to common questions about ValeCheck vehicle history reports — what\'s checked, where the data comes from, pricing, refunds and report access.',
])

@php
    $faqs = [
        [
            'q' => 'What is ValeCheck?',
            'a' => 'ValeCheck is an instant online vehicle history check for UK cars. Enter a registration and we check for write-offs, outstanding finance, stolen or scrapped markers, and mileage anomalies, using data provided by Experian, the DVLA and the DVSA, then show you a clear report in seconds.',
        ],
        [
            'q' => 'What\'s the difference between ValeCheck and ValeCheck Plus?',
            'a' => 'ValeCheck covers vehicle history and provenance: write-off category, finance, stolen/scrapped status, keeper and plate change history, and full MOT and mileage history. ValeCheck Plus includes everything in ValeCheck plus a market valuation (trade, private and part-exchange estimates), a salvage auction history check, and the vehicle\'s tax cost — useful if you\'re also trying to work out whether the asking price is fair.',
        ],
        [
            'q' => 'Where does your data come from?',
            'a' => 'Vehicle summary, write-off history, finance, stolen/scrapped and keeper registration history are data provided by Experian. MOT and mileage history come directly from the DVSA, and tax status from the DVLA. We don\'t independently verify this data ourselves — if a provider hasn\'t returned a particular check, the report says so clearly rather than presenting the absence of data as a clean result.',
        ],
        [
            'q' => 'How quickly will I get my report?',
            'a' => 'Reports are generated automatically as soon as payment is confirmed, and are normally ready within a few seconds. You can view the report on the site immediately, and download a PDF copy from your account.',
        ],
        [
            'q' => 'Is my payment secure?',
            'a' => 'Yes. Payments are processed securely by Stripe — we never see or store your card details ourselves.',
        ],
        [
            'q' => 'Can I get a refund?',
            'a' => 'If a report fails to generate due to a fault on our part, we\'ll provide a full refund or restore any credit used. Since a report is delivered instantly, refunds aren\'t available simply because its findings weren\'t what you hoped for — see our Terms & Conditions for the full policy.',
        ],
        [
            'q' => 'How long can I access my report?',
            'a' => 'Completed reports, including the downloadable PDF, remain available in your account for a limited retention period shown on the report itself. We\'d recommend downloading a copy if you want to keep it for longer.',
        ],
        [
            'q' => 'What if my registration isn\'t found?',
            'a' => 'Most UK-registered vehicles are covered, but if a plate genuinely can\'t be found, get in touch and we\'ll look into it — you won\'t be charged for a report that couldn\'t be generated.',
        ],
        [
            'q' => 'Can I check a car for free before buying a report?',
            'a' => 'Yes — enter a registration on the homepage for a free quick preview showing the vehicle\'s make, model, MOT and tax status, and mileage history, before deciding whether to buy the full report.',
        ],
        [
            'q' => 'Do you check for outstanding finance and stolen or write-off status?',
            'a' => 'Yes, both ValeCheck and ValeCheck Plus include a finance check, a stolen/scrapped marker check, and full write-off category history, all sourced from Experian.',
        ],
    ];
@endphp

@section('content')
    <div x-data="{ open: null }" class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden">
        @foreach ($faqs as $index => $faq)
            <div>
                <button
                    type="button"
                    class="w-full flex items-center justify-between gap-4 text-left px-5 py-4 hover:bg-gray-50"
                    x-on:click="open = (open === {{ $index }} ? null : {{ $index }})"
                    :aria-expanded="open === {{ $index }}"
                >
                    <span class="font-display font-semibold text-vale-navy">{{ $faq['q'] }}</span>
                    <svg class="h-5 w-5 shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': open === {{ $index }} }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
                <div x-show="open === {{ $index }}" x-collapse x-cloak class="px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                    {{ $faq['a'] }}
                </div>
            </div>
        @endforeach
    </div>

    <p class="text-sm text-gray-500 mt-8">
        Still have a question? <a href="{{ route('contact.enterprise') }}" class="text-vale-red hover:text-red-600 underline">Get in touch</a>.
    </p>

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn ($faq) => [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['a'],
                ],
            ])->all(),
        ], JSON_UNESCAPED_SLASHES) !!}
    </script>
@endsection
