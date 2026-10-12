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
        @php $salvageImages = $salvageAuctionCheck->images; @endphp
        @if ($salvageImages->isNotEmpty())
            {{-- Embedded as base64 data URIs, not plain <img src> URLs —
                 dompdf has isRemoteEnabled disabled (see ReportPdfService),
                 so a normal URL (even our own signed one) can't be fetched
                 while rendering the PDF. --}}
            <div style="margin-top:6px;">
                @foreach ($salvageImages as $image)
                    <img src="{{ $image->dataUri() }}" alt="" style="max-width:150px; max-height:110px; margin:0 6px 6px 0;">
                @endforeach
            </div>
        @else
            <p style="color:#999; font-size:9px; margin-top:6px;">No salvage auction photographs were available for this listing.</p>
        @endif
    @endif
</div>
