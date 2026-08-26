<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to ValeCheck</title>
</head>
<body style="font-family: Helvetica, Arial, sans-serif; color: #10243A; background: #F5F6F8; margin: 0; padding: 24px;">
    <table role="presentation" width="100%" style="max-width: 480px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden;">
        <tr>
            <td style="padding: 32px;">
                <p style="font-size: 20px; font-weight: bold; margin: 0 0 24px;">
                    <span style="color:#10243A;">VALE</span><span style="color:#E31B23;">CHECK</span>
                </p>

                <h1 style="font-size: 20px; margin: 0 0 12px;">Welcome, {{ $user->name }}.</h1>

                <p style="font-size: 14px; line-height: 1.6; color: #444;">
                    Your ValeCheck account is ready. Whenever you're looking at a car — private sale, dealer, or
                    a damaged/salvage listing — enter the registration and we'll check the history, provenance
                    and value before you commit to buying.
                </p>

                <p style="margin: 28px 0;">
                    <a href="{{ route('vehicle-checks.start') }}" style="background: #E31B23; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 999px; font-weight: bold; font-size: 14px;">
                        Check a vehicle
                    </a>
                </p>

                <p style="font-size: 12px; color: #999;">
                    If you didn't create this account, you can safely ignore this email.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
