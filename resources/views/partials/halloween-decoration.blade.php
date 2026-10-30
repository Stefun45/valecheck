{{-- Subtle, time-boxed seasonal touch - see App\Support\SeasonalTheme.
     Purely decorative, so it's aria-hidden and never intercepts clicks.
     The parent element must have `relative` for this to anchor correctly,
     and must not also stack something busy (like a full-bleed banner)
     directly at its top-right corner. --}}
@if (\App\Support\SeasonalTheme::halloweenActive())
    <div class="hidden sm:block absolute top-0 right-0 w-28 h-28 md:w-36 md:h-36 overflow-hidden pointer-events-none select-none z-0" aria-hidden="true">
        <svg viewBox="0 0 120 120" fill="none" class="w-full h-full text-amber-800/30">
            <path d="M120 0L0 0M120 0L120 120M120 0L70 10M120 0L110 50M120 0L40 18M120 0L102 80M120 0L0 55M120 0L65 120" stroke="currentColor" stroke-width="1.25"/>
            <path d="M117 3Q100 20 117 40M117 3Q100 20 80 3" stroke="currentColor" stroke-width="1.25" fill="none"/>
            <path d="M112 8Q88 32 112 64M112 8Q88 32 56 8" stroke="currentColor" stroke-width="1.25" fill="none"/>
            <path d="M106 14Q74 46 106 90M106 14Q74 46 30 14" stroke="currentColor" stroke-width="1.25" fill="none"/>
        </svg>
        <span class="absolute top-9 right-16 text-sm vale-halloween-drift" style="animation-delay: -3s">🕷️</span>
    </div>
@endif
