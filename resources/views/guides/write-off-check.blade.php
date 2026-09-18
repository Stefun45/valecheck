@extends('layouts.legal', [
    'title' => 'How to Check If a Car Is Written Off (UK)',
    'description' => 'Step-by-step guide to checking whether a used car has a write-off history before you buy, including what a write-off marker actually means and why the V5C alone isn\'t enough.',
])

@section('content')
    <div class="prose-content space-y-6 text-gray-600 leading-relaxed">
        <p>
            A "written-off" car is one an insurer has previously declared uneconomical (or unsafe) to repair
            after an accident, flood or theft recovery, then formally recorded against that vehicle's identity.
            The car can still be legally driven again in most cases - but you need to know about it before
            you buy, both for your own safety and because it materially affects what the car is worth.
        </p>

        <h2 class="font-display font-bold text-lg text-vale-navy">Why the V5C logbook isn't enough on its own</h2>
        <p>
            A genuine V5C registration certificate doesn't reliably show write-off history. Some write-off
            categories require the DVLA to mark the vehicle record, but not all do, and a car can change hands
            several times after being repaired - the paperwork a private seller hands you often won't
            mention it at all, whether deliberately or simply because they don't know themselves.
        </p>

        <h2 class="font-display font-bold text-lg text-vale-navy">How to actually check</h2>
        <ol class="list-decimal list-inside space-y-2">
            <li>Enter the registration into a proper vehicle history check - this cross-references
                insurance industry write-off databases, not just DVLA records.</li>
            <li>Look for the specific write-off category (A, B, S or N - see our
                <a href="{{ route('guides.write-off-categories') }}" class="text-vale-red hover:text-red-600 underline">full guide to what each category means</a>),
                not just a yes/no flag.</li>
            <li>Check the date it was recorded, and compare that against the seller's account of the car's
                history - a mismatch or gap is worth asking about directly.</li>
            <li>If it's a structural (Category S) repair, ask for evidence of the repair work and consider an
                independent engineer's inspection before committing to buy.</li>
        </ol>

        <h2 class="font-display font-bold text-lg text-vale-navy">What ValeCheck shows you</h2>
        <p>
            A <a href="{{ route('vehicle-checks.start') }}" class="text-vale-red hover:text-red-600 underline">free ValeCheck lookup</a>
            gives you the vehicle's basic details and MOT history at no cost. A full
            <a href="{{ route('sample-report') }}" class="text-vale-red hover:text-red-600 underline">ValeCheck report</a>
            adds the write-off category (if any), the date it was recorded, outstanding finance, stolen markers
            and keeper history, all sourced from Experian, the DVLA and the DVSA - and it says clearly
            when a provider hasn't returned a particular check, rather than presenting the absence of data as a
            clean result.
        </p>

        <p class="text-sm text-gray-400">
            This is general information, not legal or professional advice. See our
            <a href="{{ route('faq') }}" class="text-vale-red hover:text-red-600 underline">FAQs</a> for more on
            what's included in a report.
        </p>
    </div>
@endsection
