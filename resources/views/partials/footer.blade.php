<footer class="border-t border-gray-100 py-10 text-center text-xs text-gray-400">
    <x-application-logo text-class="text-sm" class="justify-center" />
    <p class="mt-3 max-w-2xl mx-auto leading-relaxed px-4">
        Vehicle valuations, repair estimates, resale estimates and recommended purchase or bid amounts are
        estimates based on available vehicle, market and image data at the time of analysis, provided for
        guidance only.
        @if (config('valecheck.rebuild_enabled'))
            AI damage analysis is based on the images supplied and may fail to identify concealed
            structural, mechanical or electrical damage — it is not a substitute for a physical inspection.
        @endif
    </p>
    <p class="mt-4 max-w-2xl mx-auto leading-relaxed px-4">
        Silverback Customs UK Ltd, trading as ValeCheck.<br>
        Unit 2A, 35 Eastgate North, Driffield, YO25 6DG.
    </p>
    <p class="mt-3 flex items-center justify-center gap-4">
        <a href="{{ route('legal.terms') }}" wire:navigate class="hover:text-vale-navy">Terms &amp; Conditions</a>
        <a href="{{ route('legal.privacy') }}" wire:navigate class="hover:text-vale-navy">Privacy Policy</a>
    </p>
</footer>
