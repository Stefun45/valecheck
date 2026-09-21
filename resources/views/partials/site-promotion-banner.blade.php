{{-- A struck-through price alone is easy to miss - this is the loud
     version, shown above the header on any page that includes it.
     Renders nothing at all unless a promotion is actually live. --}}
@php $sitePromotion = \App\Models\SitePromotion::current(); @endphp
@if ($sitePromotion->isLive())
    <div class="bg-vale-red text-white text-center text-sm font-semibold py-2 px-4">
        {{ $sitePromotion->label }}: {{ rtrim(rtrim(number_format((float) $sitePromotion->percentage, 2), '0'), '.') }}% off every report - for a limited time.
    </div>
@endif
