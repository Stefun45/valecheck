{{-- Subtle, time-boxed seasonal touch - see App\Support\SeasonalTheme.
     Purely decorative, so it's aria-hidden and never intercepts clicks.
     The parent element must have `relative` for this to anchor correctly. --}}
@if (\App\Support\SeasonalTheme::halloweenActive())
    <div class="absolute top-0 left-0 w-32 h-32 sm:w-40 sm:h-40 overflow-hidden pointer-events-none select-none z-0" aria-hidden="true">
        <svg viewBox="0 0 120 120" fill="none" class="w-full h-full text-vale-navy/15">
            <path d="M0 0L120 0M0 0L0 120M0 0L50 10M0 0L10 50M0 0L80 18M0 0L18 80M0 0L120 55M0 0L55 120" stroke="currentColor" stroke-width="1"/>
            <path d="M3 3Q20 20 3 40M3 3Q20 20 40 3" stroke="currentColor" stroke-width="1" fill="none"/>
            <path d="M8 8Q32 32 8 64M8 8Q32 32 64 8" stroke="currentColor" stroke-width="1" fill="none"/>
            <path d="M14 14Q46 46 14 90M14 14Q46 46 90 14" stroke="currentColor" stroke-width="1" fill="none"/>
        </svg>
    </div>

    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"
         class="hidden sm:block absolute top-6 right-8 w-6 h-6 text-vale-navy/25 vale-halloween-drift pointer-events-none select-none z-0">
        <path d="M12 7c-.8-2-2.8-3.4-5-3.4-2.6 0-4.7 2-5 4.6 1.7-.9 3.6-.6 4.8.6-2 .1-3.8 1.3-4.6 3.2 1.8-.5 3.5.1 4.5 1.6-1.6.5-2.8 1.8-3.2 3.4 1.7-1.1 3.8-1.2 5.5-.1A3.6 3.6 0 0 1 12 14.2a3.6 3.6 0 0 1 3 2.7c1.7-1.1 3.8-1 5.5.1-.4-1.6-1.6-2.9-3.2-3.4 1-1.5 2.7-2.1 4.5-1.6-.8-1.9-2.6-3.1-4.6-3.2 1.2-1.2 3.1-1.5 4.8-.6-.3-2.6-2.4-4.6-5-4.6-2.2 0-4.2 1.4-5 3.4Z"/>
    </svg>
@endif
