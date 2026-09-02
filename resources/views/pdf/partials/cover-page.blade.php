{{-- Shared by pdf/check-report.blade.php, pdf/plus-report.blade.php and
     pdf/rebuild-report.blade.php — keep in sync so the three report types
     never silently drift apart again. page-break-after pushes the actual
     report content onto its own page, so this reads as a proper cover
     rather than just more content stacked under the thin logo header. --}}
@php
    $verdict = \App\Services\Reports\ReportStatusSummary::verdict($history);
    $verdictColor = match ($verdict['tone']) {
        'good' => '#16A34A',
        'warning' => '#CA8A04',
        default => '#9CA3AF',
    };
@endphp
<div style="text-align:center; padding:40px 0 20px;">
    @if ($check->hasVehicleImage())
        <div style="margin-bottom:20px;">
            <img src="{{ $check->vehicleImageDataUri() }}" alt="" style="max-width:320px; max-height:180px;">
        </div>
    @endif

    <div style="display:inline-block; background:#FFD43B; border:2px solid #10243A; border-radius:6px; padding:10px 24px;">
        <span style="font-family:'Courier New', monospace; font-size:28px; font-weight:bold; letter-spacing:2px; color:#10243A;">{{ $check->registration }}</span>
    </div>

    <h1 style="font-size:22px; margin:24px 0 4px;">{{ $vehicle->description() ?: $check->registration }}</h1>
    <p style="color:#666; font-size:11px; margin:0 0 28px;">{{ $tag ?? '' }}</p>

    <x-report-verdict-badge :tone="$verdict['tone']" :label="$verdict['label']" :size="80" />

    <p style="color:#999; font-size:9px; margin-top:36px;">
        Data provided by Experian &middot; ICO Registered &middot; DVLA &amp; DVSA Verified
    </p>
    <p style="color:#999; font-size:9px; margin-top:4px;">
        Report generated {{ now()->format('d M Y, H:i') }}
    </p>
</div>
<div style="page-break-after: always;"></div>
