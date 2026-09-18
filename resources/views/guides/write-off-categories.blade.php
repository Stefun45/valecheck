@extends('layouts.legal', [
    'title' => 'Car Write-Off Categories Explained (Cat A, B, S, N)',
    'description' => 'What Category A, B, S and N write-offs actually mean in the UK, how they differ from the older Cat C and Cat D system, and what buying one means for safety, insurance and value.',
])

@section('content')
    <div class="prose-content space-y-6 text-gray-600 leading-relaxed">
        <p>
            Since October 2017, UK insurers have used four write-off categories to describe how badly damaged
            a vehicle was, and what legally has to happen to it afterwards. Two of the four mean the car should
            never be on the road again; the other two mean it's been repaired and can legally be driven, but
            still carries a permanent history marker.
        </p>

        <h2 class="font-display font-bold text-lg text-vale-navy">Category A - scrap only</h2>
        <p>
            The most severe category. The vehicle must be crushed in its entirety, and no parts may be resold
            or reused, even for spares. A car showing a genuine Category A history should never legally reappear
            for sale.
        </p>

        <h2 class="font-display font-bold text-lg text-vale-navy">Category B - break for parts only</h2>
        <p>
            The body shell must be crushed and the vehicle must never return to the road, but individual
            components (engine, gearbox, panels) can be salvaged and resold separately. Like Category A, a
            genuine Cat B car itself should never be back on sale as a driveable vehicle.
        </p>

        <h2 class="font-display font-bold text-lg text-vale-navy">Category S - structural damage, repaired</h2>
        <p>
            "S" stands for structural: damage to the chassis or frame. A Category S vehicle has been assessed,
            professionally repaired and can legally return to the road. This doesn't automatically mean it's
            unsafe, but structural repairs are a bigger job than cosmetic ones, and it's reasonable to ask for
            evidence of the repair and consider an independent inspection before buying.
        </p>

        <h2 class="font-display font-bold text-lg text-vale-navy">Category N - non-structural damage, repaired</h2>
        <p>
            "N" stands for non-structural: damage that didn't affect the chassis or frame, such as cosmetic
            panel damage, electrical faults or interior damage. A Category N vehicle can also legally return to
            the road once repaired, and is generally considered less serious than Category S.
        </p>

        <h2 class="font-display font-bold text-lg text-vale-navy">The older Cat C and Cat D system</h2>
        <p>
            Vehicles written off before October 2017 may still show the categories these replaced: Category C
            (roughly equivalent to today's S) and Category D (roughly equivalent to today's N). You may still
            see these labels on older vehicle history records.
        </p>

        <h2 class="font-display font-bold text-lg text-vale-navy">Does a write-off category mean the car is unsafe?</h2>
        <p>
            Not necessarily - a well-repaired Category S or N car can be perfectly safe to drive. What it
            does mean is that the car's market value is permanently reduced compared to an equivalent car with
            no write-off history, and some insurers charge more (or decline cover) for a written-off vehicle.
            It's worth knowing before you agree a price, not after.
        </p>

        <p>
            Want to check a specific vehicle? Our
            <a href="{{ route('vehicle-checks.start') }}" class="text-vale-red hover:text-red-600 underline">free lookup</a>
            shows basic details at no cost, and a full
            <a href="{{ route('sample-report') }}" class="text-vale-red hover:text-red-600 underline">ValeCheck report</a>
            shows the write-off category, the date it was recorded and much more - see our
            <a href="{{ route('guides.write-off-check') }}" class="text-vale-red hover:text-red-600 underline">guide to checking write-off history</a>
            for the full process.
        </p>
    </div>
@endsection
