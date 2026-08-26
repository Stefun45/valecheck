@php
    $vehicle = $check->vehicle;
    $label = config("valecheck.pricing.{$check->type}.label", 'ValeCheck');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your ValeCheck report is ready</title>
</head>
<body style="font-family: Helvetica, Arial, sans-serif; color: #10243A; background: #F5F6F8; margin: 0; padding: 24px;">
    <table role="presentation" width="100%" style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden;">
        <tr>
            <td style="padding: 32px;">
                <p style="font-size: 20px; font-weight: bold; margin: 0 0 24px;">
                    <span style="color:#10243A;">VALE</span><span style="color:#E31B23;">CHECK</span>
                </p>

                <h1 style="font-size: 20px; margin: 0 0 4px;">Your {{ $label }} report is ready</h1>
                <p style="font-family: monospace; color: #888; margin: 0 0 16px;">{{ $check->registration }}{{ $vehicle?->description() ? ' · '.$vehicle->description() : '' }}</p>

                @if ($check->report?->headline_summary)
                    <p style="font-size: 14px; line-height: 1.6; color: #444;">{{ $check->report->headline_summary }}</p>
                @endif

                <p style="font-size: 13px; color: #666;">A PDF copy is attached to this email.</p>

                <p style="margin: 28px 0;">
                    <a href="{{ route('vehicle-checks.show', $check) }}" style="background: #E31B23; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 999px; font-weight: bold; font-size: 14px;">
                        View report online
                    </a>
                </p>

                <p style="font-size: 12px; color: #999;">
                    This report and its PDF will remain available in your ValeCheck account for {{ config('valecheck.reports.retention_days') }} days.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
