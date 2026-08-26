@props(['textClass' => 'text-2xl', 'tagline' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 select-none']) }}>
    <svg viewBox="0 0 40 40" fill="none" class="h-[1.4em] w-[1.4em] shrink-0" aria-hidden="true">
        <circle cx="20" cy="20" r="17.5" fill="white" stroke="#10243A" stroke-width="2.5"/>
        <path d="M11 20.5L16.5 27L29 12" stroke="#E31B23" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    <span class="flex flex-col leading-none">
        <span class="font-display font-extrabold tracking-tight {{ $textClass }}">
            <span class="text-vale-navy">VALE</span><span class="text-vale-red">CHECK</span>
        </span>
        @if ($tagline)
            <span class="flex items-center gap-1.5 mt-1">
                <span class="h-[2px] w-3 bg-vale-red"></span>
                <span class="text-[0.55em] font-semibold tracking-[0.2em] text-vale-navy/70">KNOW BEFORE YOU BUY</span>
                <span class="h-[2px] w-3 bg-vale-navy"></span>
            </span>
        @endif
    </span>
</span>
