{{-- Shared by check-report.blade.php and plus-report.blade.php — keep in
     sync so the two report types never silently drift apart again. --}}
@php
    $points = collect($history?->mot_history ?? [])
        ->filter(fn ($test) => isset($test['mileage'], $test['test_date']))
        ->sortBy('test_date')
        ->values();
    $hasEnoughPoints = $points->count() >= 2;

    if ($hasEnoughPoints) {
        $mileages = $points->pluck('mileage');
        $minMileage = $mileages->min();
        $maxMileage = max($mileages->max(), $minMileage + 1);
        $range = $maxMileage - $minMileage;
        $count = $points->count();
        $chartWidth = 600;
        $chartHeight = 160;
        $pad = 12;

        $coords = $points->map(function ($point, $i) use ($count, $chartWidth, $chartHeight, $pad, $minMileage, $range) {
            $x = $count > 1 ? $pad + ($i / ($count - 1)) * ($chartWidth - $pad * 2) : $chartWidth / 2;
            $y = $pad + ($chartHeight - $pad * 2) - ((($point['mileage'] - $minMileage) / $range) * ($chartHeight - $pad * 2));

            return [
                'x' => round($x, 1),
                'y' => round($y, 1),
                'mileage' => $point['mileage'],
                'label' => \Illuminate\Support\Carbon::parse($point['test_date'])->format('d M Y').' — '.number_format($point['mileage']).' mi',
            ];
        });

        $polylinePoints = $coords->map(fn ($c) => "{$c['x']},{$c['y']}")->implode(' ');
        $labels = \Illuminate\Support\Js::from($coords->pluck('label'));
    }
@endphp

<div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm sm:col-span-2" @if ($hasEnoughPoints) x-data="{ hovered: null, labels: {{ $labels }} }" @endif>
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">Mileage Over Time</h3>
        @if ($hasEnoughPoints)
            <p class="text-xs font-semibold text-vale-navy" x-show="hovered !== null">
                <span x-text="hovered !== null ? labels[hovered] : ''"></span>
            </p>
        @endif
    </div>
    @if ($hasEnoughPoints)
        <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="none" class="w-full h-40">
            <polyline points="{{ $polylinePoints }}" fill="none" stroke="#10243A" stroke-width="2" vector-effect="non-scaling-stroke" />
            @foreach ($coords as $i => $c)
                {{-- Larger invisible circle gives an easy-to-hit hover target; the small visible dot just reacts to it. --}}
                <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="14" fill="transparent" style="cursor: pointer;" x-on:mouseenter="hovered = {{ $i }}" x-on:mouseleave="hovered = null" />
                <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}" r="4" :fill="hovered === {{ $i }} ? '#10243A' : '#E31B23'" style="pointer-events: none;" />
            @endforeach
        </svg>
        <div class="flex justify-between text-[10px] text-gray-400 mt-1">
            <span>{{ \Illuminate\Support\Carbon::parse($points->first()['test_date'])->format('M Y') }}</span>
            <span>{{ \Illuminate\Support\Carbon::parse($points->last()['test_date'])->format('M Y') }}</span>
        </div>
        <p class="text-xs text-gray-400 mt-2">{{ number_format($minMileage) }}–{{ number_format($maxMileage) }} miles across {{ $count }} recorded MOT tests.</p>
    @else
        <p class="text-vale-navy text-sm">Not enough MOT history to show a mileage trend.</p>
    @endif
</div>
