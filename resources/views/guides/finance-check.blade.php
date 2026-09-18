@extends('layouts.legal', [
    'title' => 'How to Check a Car for Outstanding Finance (UK)',
    'description' => 'How to find out if a used car still has finance owing on it before you buy, what the law actually says about buying a car on finance, and why dealers in particular can\'t rely on being an "innocent purchaser".',
])

@section('content')
    <div class="prose-content space-y-6 text-gray-600 leading-relaxed">
        <p>
            Many used cars are bought on hire purchase (HP) or PCP finance, and the finance company keeps a
            legal interest in the vehicle until the agreement is fully settled. If the seller hasn't finished
            paying it off, that outstanding finance doesn't just disappear when they sell the car to you.
        </p>

        <h2 class="font-display font-bold text-lg text-vale-navy">What the law actually says</h2>
        <p>
            Under Section 27 of the Hire Purchase Act 1964, a genuine private individual who buys a car in good
            faith, with no notice of the outstanding finance, is generally protected: they can keep the car, and
            the finance company's claim stays against the person who sold it to them, not against the innocent
            buyer. This protection matters and is real, but it comes with real limits worth knowing before you
            rely on it.
        </p>

        <h2 class="font-display font-bold text-lg text-vale-navy">Why you still shouldn't skip a finance check</h2>
        <ul class="list-disc list-inside space-y-2">
            <li><strong>It only protects genuine private buyers.</strong> A motor trader or dealer buying the
                same car, even in a private capacity, does not qualify as a protected "private purchaser" under
                the Act - there's no fallback protection if you're in the trade.</li>
            <li><strong>"Good faith and no notice" can still be disputed.</strong> If a finance company later
                challenges your purchase, proving you genuinely had no notice of the agreement is a real
                dispute to have, even if you're ultimately in the right.</li>
            <li><strong>It doesn't undo the hassle.</strong> Even a buyer who's fully protected in law can still
                face a finance company or a debt recovery agent contacting them about a car, which is worth
                avoiding entirely rather than resolving after the fact.</li>
        </ul>

        <h2 class="font-display font-bold text-lg text-vale-navy">How to check before you buy</h2>
        <p>
            The V5C logbook doesn't show finance status - it only records the registered keeper, who
            isn't necessarily the legal owner while finance is outstanding. A proper vehicle history check
            queries finance industry data directly and will flag whether an agreement is currently recorded
            against that registration.
        </p>

        <p>
            Both <a href="{{ route('vehicle-checks.start') }}" class="text-vale-red hover:text-red-600 underline">ValeCheck and ValeCheck Plus</a>
            include an outstanding finance check, sourced from Experian, alongside write-off history, stolen
            markers and keeper history. See a
            <a href="{{ route('sample-report') }}" class="text-vale-red hover:text-red-600 underline">full sample report</a>
            to see exactly what's covered.
        </p>

        <p class="text-sm text-gray-400">
            This is general information, not legal advice - if you're unsure about a specific situation,
            speak to a qualified solicitor.
        </p>
    </div>
@endsection
