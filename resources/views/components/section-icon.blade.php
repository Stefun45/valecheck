@props(['name', 'size' => 16])

{{-- Simple geometric icons only (no complex multi-curve path data) so
     every shape here can be hand-verified rather than risking a subtly
     malformed icon library path. Sized via width/height attributes, not
     Tailwind classes, so this also renders correctly in dompdf. --}}
<svg viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
    @switch($name)
        @case('identity')
            <circle cx="12" cy="12" r="9" />
            <line x1="12" y1="11" x2="12" y2="16" />
            <circle cx="12" cy="8" r="0.6" fill="currentColor" stroke="none" />
            @break
        @case('warning')
            <path d="M12 4L3 20h18L12 4z" />
            <line x1="12" y1="10" x2="12" y2="15" />
            <circle cx="12" cy="17.5" r="0.6" fill="currentColor" stroke="none" />
            @break
        @case('finance')
            <rect x="3" y="6" width="18" height="12" rx="2" />
            <line x1="3" y1="10" x2="21" y2="10" />
            @break
        @case('shield')
            <path d="M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6l7-3z" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <line x1="3" y1="10" x2="21" y2="10" />
            <line x1="8" y1="3" x2="8" y2="7" />
            <line x1="16" y1="3" x2="16" y2="7" />
            @break
        @case('trending-up')
            <polyline points="3,17 9,11 13,15 21,6" />
            <polyline points="15,6 21,6 21,12" />
            @break
        @case('user')
            <circle cx="12" cy="8" r="4" />
            <path d="M4 21c0-4.5 3.5-7 8-7s8 2.5 8 7" />
            @break
        @case('chart-bar')
            <line x1="5" y1="21" x2="5" y2="10" />
            <line x1="12" y1="21" x2="12" y2="4" />
            <line x1="19" y1="21" x2="19" y2="14" />
            @break
        @case('document')
            <path d="M7 3h7l5 5v13a1 1 0 01-1 1H7a1 1 0 01-1-1V4a1 1 0 011-1z" />
            <polyline points="14,3 14,8 19,8" />
            @break
    @endswitch
</svg>
