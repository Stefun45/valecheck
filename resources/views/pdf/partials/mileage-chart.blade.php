{{-- Shared by pdf/check-report.blade.php and pdf/plus-report.blade.php —
     keep in sync so the two report types never silently drift apart again. --}}
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
        $chartWidth = 500;
        $chartHeight = 100;
        $pad = 10;

        $coords = $points->map(function ($point, $i) use ($count, $chartWidth, $chartHeight, $pad, $minMileage, $range) {
            $x = $count > 1 ? $pad + ($i / ($count - 1)) * ($chartWidth - $pad * 2) : $chartWidth / 2;
            $y = $pad + ($chartHeight - $pad * 2) - ((($point['mileage'] - $minMileage) / $range) * ($chartHeight - $pad * 2));

            return [round($x, 1), round($y, 1)];
        });

        $polylinePoints = $coords->map(fn ($c) => "{$c[0]},{$c[1]}")->implode(' ');
    }
@endphp

<div class="section">
    <div class="section-title">Mileage Over Time</div>
    @if ($hasEnoughPoints)
        <svg width="{{ $chartWidth }}" height="{{ $chartHeight }}" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}">
            <polyline points="{{ $polylinePoints }}" fill="none" stroke="#10243A" stroke-width="2" />
            @foreach ($coords as $c)
                <circle cx="{{ $c[0] }}" cy="{{ $c[1] }}" r="3" fill="#E31B23" />
            @endforeach
        </svg>
        <p style="color:#999; font-size:9px; margin-top:4px;">{{ number_format($minMileage) }}–{{ number_format($maxMileage) }} miles across {{ $count }} recorded MOT tests.</p>
    @else
        <p>Not enough MOT history to show a mileage trend.</p>
    @endif
</div>
