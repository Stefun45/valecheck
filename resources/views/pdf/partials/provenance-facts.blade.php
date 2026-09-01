{{-- Shared by pdf/check-report.blade.php and pdf/plus-report.blade.php —
     keep in sync so the two report types never silently drift apart again.
     See livewire/vehicle-check/partials/provenance-facts.blade.php for why
     "unavailable" is a distinct third state from "clean". --}}
<td>
    <div class="section">
        <div class="section-title">Write-Off History</div>
        @if (is_null($history))
            <p>Write-off data unavailable.</p>
        @elseif ($history->isWrittenOff())
            <p class="warn">Category {{ $history->write_off_category }} recorded</p>
            <p>Date: {{ optional($history->write_off_date)->format('d M Y') ?? 'Unknown' }}</p>
            @if ($history->formattedDamageLocations())
                <p>Damage area: {{ implode(', ', $history->formattedDamageLocations()) }}</p>
            @endif
            <x-damage-diagram :locations="$history->damage_locations ?? []" />
        @else
            <p class="ok">No write-off history recorded.</p>
        @endif
    </div>
</td>
<td>
    <div class="section">
        <div class="section-title">Finance</div>
        @if (is_null($history?->finance_marker))
            <p>Finance data unavailable.</p>
        @elseif ($history->finance_marker)
            <p class="warn">Finance marker detected</p>
        @else
            <p class="ok">No finance marker found</p>
        @endif
    </div>
</td>
<td>
    <div class="section">
        <div class="section-title">Stolen / Scrapped</div>
        @if (is_null($history?->stolen_marker))
            <p>Stolen check unavailable.</p>
        @elseif ($history->stolen_marker)
            <p class="warn">Stolen: Marker found</p>
        @else
            <p class="ok">Stolen: No marker found</p>
        @endif
        @if (is_null($history?->scrapped_marker))
            <p>Scrapped check unavailable.</p>
        @elseif ($history->scrapped_marker)
            <p class="warn">Scrapped: Marker found</p>
        @else
            <p class="ok">Scrapped: No marker found</p>
        @endif
    </div>
</td>
@if ($check->user->isDealerSubscriber())
    <td>
        <div class="section">
            <div class="section-title">High Risk</div>
            @if (is_null($history?->high_risk_marker))
                <p>High risk data unavailable.</p>
            @elseif ($history->high_risk_marker)
                <p class="warn">High risk marker found</p>
            @else
                <p class="ok">No high risk marker found</p>
            @endif
        </div>
    </td>
@endif
