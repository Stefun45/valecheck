@php
    $timelineEvents = \App\Services\Reports\VehicleTimeline::build($history);
@endphp

@if (! empty($timelineEvents))
    <div class="section">
        <div class="section-title">Vehicle Timeline</div>
        <table class="data">
            <tr><th>Date</th><th>Event</th></tr>
            @foreach ($timelineEvents as $event)
                <tr>
                    <td style="white-space:nowrap;">{{ $event['date']->format('d M Y') }}</td>
                    <td>
                        {{ $event['label'] }}
                        @if ($event['detail'])
                            <span style="color:#999;"> &middot; {{ $event['detail'] }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endif
