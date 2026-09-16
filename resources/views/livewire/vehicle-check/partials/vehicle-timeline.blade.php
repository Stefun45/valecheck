@php
    $timelineEvents = \App\Services\Reports\VehicleTimeline::build($history);
    $dotClass = fn (string $type) => match ($type) {
        \App\Services\Reports\VehicleTimeline::TYPE_MOT_FAIL, \App\Services\Reports\VehicleTimeline::TYPE_WRITE_OFF => 'bg-vale-red',
        \App\Services\Reports\VehicleTimeline::TYPE_MOT_PASS => 'bg-green-500',
        default => 'bg-vale-navy',
    };
@endphp

@if (! empty($timelineEvents))
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <h3 class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest text-gray-400 mb-4"><x-section-icon name="calendar" />Vehicle Timeline</h3>
        <ol class="relative border-l-2 border-gray-100 ml-1.5 space-y-4">
            @foreach ($timelineEvents as $event)
                <li class="pl-5 relative">
                    <span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full border-2 border-white {{ $dotClass($event['type']) }}"></span>
                    <p class="text-xs text-gray-400">{{ $event['date']->format('d M Y') }}</p>
                    <p class="text-sm text-vale-navy font-medium">
                        {{ $event['label'] }}
                        @if ($event['detail'])
                            <span class="text-gray-500 font-normal">&middot; {{ $event['detail'] }}</span>
                        @endif
                    </p>
                </li>
            @endforeach
        </ol>
    </div>
@endif
