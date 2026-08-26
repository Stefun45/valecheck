@php
    $stages = match (true) {
        $check->needsDamageAnalysis() => ['retrieving_history', 'retrieving_valuation', 'analysing_images', 'calculating_repair', 'calculating_maximum_bid', 'calculating_deal_score', 'generating_report'],
        $check->needsValuation() => ['retrieving_history', 'retrieving_valuation', 'generating_report'],
        default => ['retrieving_history', 'generating_report'],
    };
    $currentIndex = array_search($check->stage, $stages, true);
@endphp

<div class="text-center">
    <div class="inline-flex items-center justify-center h-16 w-16 rounded-full border-4 border-gray-200 border-t-vale-red animate-spin mb-6"></div>

    <h1 class="font-display font-bold text-2xl text-vale-navy">Building your report...</h1>
    <p class="text-gray-500 mt-1">Registration: <span class="font-mono text-vale-navy">{{ $check->registration }}</span></p>

    <ul class="mt-8 max-w-sm mx-auto space-y-3 text-left">
        @foreach ($stages as $index => $stage)
            <li class="flex items-center gap-3">
                @if ($currentIndex !== false && $index < $currentIndex)
                    <span class="text-vale-red">✓</span>
                @elseif ($stage === $check->stage)
                    <span class="h-2 w-2 rounded-full bg-vale-red animate-pulse"></span>
                @else
                    <span class="h-2 w-2 rounded-full bg-gray-300"></span>
                @endif
                <span class="{{ $stage === $check->stage ? 'text-vale-navy font-medium' : 'text-gray-400' }}">
                    {{ \App\Models\VehicleCheck::STAGE_LABELS[$stage] ?? ucfirst(str_replace('_', ' ', $stage)) }}
                </span>
            </li>
        @endforeach
    </ul>
</div>
