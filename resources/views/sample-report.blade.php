<x-app-layout
    title="Sample Vehicle History Report — ValeCheck"
    description="See a real example ValeCheck Plus report — vehicle history, provenance, MOT history and market valuation — before you buy your own."
    :noindex="false"
>
    <div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="bg-vale-navy text-white rounded-xl p-5 mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="font-display font-bold">This is a sample report</p>
                <p class="text-sm text-white/70 mt-0.5">Demonstration data only — not a real vehicle. Here's exactly what you'll get.</p>
            </div>
            <a href="{{ route('vehicle-checks.start') }}" class="inline-flex items-center px-5 py-2.5 bg-vale-red rounded-full font-semibold text-sm text-white hover:bg-red-600 transition whitespace-nowrap">
                Check Your Own Vehicle &rarr;
            </a>
        </div>

        @include('livewire.vehicle-check.partials.plus-report', ['check' => $vehicleCheck])
    </div>
</x-app-layout>
