{{-- A struck-through price alone is easy to miss - this is the loud
     version, shown above the header on any page that includes it.
     Renders nothing at all unless at least one plan actually has a live
     promotion right now. --}}
@php
    $liveSitePromotions = collect(['check' => 'ValeCheck', 'plus' => 'ValeCheck Plus', 'rebuild' => 'ValeCheck Rebuild'])
        ->map(fn ($label, $type) => ['label' => $label, 'promotion' => \App\Models\SitePromotion::current($type)])
        ->filter(fn ($row) => $row['promotion']->isLive());
@endphp
@if ($liveSitePromotions->isNotEmpty())
    <div class="bg-vale-red text-white text-center text-sm font-semibold py-2 px-4">
        Launch Offer:
        {{ $liveSitePromotions->map(fn ($row) => "{$row['label']} now £".number_format((float) $row['promotion']->discounted_gross, 2))->implode(', ') }}
        - for a limited time.
    </div>
@endif
