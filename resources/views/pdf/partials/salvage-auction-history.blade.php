<div class="section">
    <div class="section-title">Salvage Auction History</div>
    @if (! $salvageAuctionCheck?->record_found)
        <p>No salvage auction record found for this vehicle.</p>
    @else
        <table class="data">
            <tr><th>Auction date</th><th>Mileage</th><th>Primary damage</th><th>Location</th></tr>
            @foreach ($salvageAuctionCheck->records as $record)
                <tr>
                    <td>{{ $record['lotDate'] ? \Illuminate\Support\Carbon::parse($record['lotDate'])->format('d M Y') : '—' }}</td>
                    <td>{{ $record['mileage'] ? number_format($record['mileage']).' mi' : '—' }}</td>
                    <td>{{ $record['primaryDamageDescription'] ?? '—' }}</td>
                    <td>{{ $record['location'] ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
        {{-- A PDF is a static snapshot generated once - it can't hold a
             live link the way the online report can fetch one fresh at
             view time, and One Auto's thirdpartyimages URL expires after
             ~12 hours anyway, so a link embedded here could easily be dead
             by the time anyone reads it. Pointing to the online report
             instead means whoever views it always gets a fresh fetch. --}}
        <p style="color:#999; font-size:9px; margin-top:6px;">Salvage auction photographs are available on the online version of this report at {{ $reportUrl }}</p>
    @endif
</div>
