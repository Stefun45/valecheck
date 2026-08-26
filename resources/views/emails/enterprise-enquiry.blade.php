<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Enterprise enquiry</title>
</head>
<body style="font-family: Helvetica, Arial, sans-serif; color: #10243A; background: #F5F6F8; margin: 0; padding: 24px;">
    <table role="presentation" width="100%" style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden;">
        <tr>
            <td style="padding: 32px;">
                <p style="font-size: 20px; font-weight: bold; margin: 0 0 24px;">
                    <span style="color:#10243A;">VALE</span><span style="color:#E31B23;">CHECK</span>
                </p>

                <h1 style="font-size: 20px; margin: 0 0 12px;">New Enterprise enquiry</h1>

                <table role="presentation" width="100%" style="font-size: 14px; line-height: 1.6; color: #444; margin: 0 0 20px;">
                    <tr><td style="padding: 4px 0; font-weight: bold; width: 90px;">Name</td><td style="padding: 4px 0;">{{ $enquiry['name'] }}</td></tr>
                    <tr><td style="padding: 4px 0; font-weight: bold;">Email</td><td style="padding: 4px 0;">{{ $enquiry['email'] }}</td></tr>
                    @if (! empty($enquiry['company']))
                        <tr><td style="padding: 4px 0; font-weight: bold;">Company</td><td style="padding: 4px 0;">{{ $enquiry['company'] }}</td></tr>
                    @endif
                </table>

                <p style="font-size: 14px; line-height: 1.6; color: #444; white-space: pre-line;">{{ $enquiry['message'] }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
