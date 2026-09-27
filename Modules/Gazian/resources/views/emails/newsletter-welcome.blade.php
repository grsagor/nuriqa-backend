<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $brand }}</title>
</head>
<body style="margin:0;padding:0;background:#eef6fb;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef6fb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 24px rgba(11,36,48,0.08);">
                    <tr>
                        <td style="background:#0c4a6e;padding:28px 32px;">
                            <p style="margin:0;font-size:13px;letter-spacing:0.12em;text-transform:uppercase;color:#9fd4ea;">{{ $brand }}</p>
                            <h1 style="margin:8px 0 0;font-size:26px;line-height:1.25;color:#ffffff;font-weight:600;">You're on the list</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#0b2430;">Hello,</p>
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#5c7380;">
                                Thank you for joining {{ $brand }}. We'll keep you updated on new stockists, product news, and the considered work behind every bottle.
                            </p>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#5c7380;">
                                Occasional emails only — you can unsubscribe at any time.
                            </p>
                            <div style="height:1px;background:#c9d8e1;margin:0 0 24px;"></div>
                            <p style="margin:0;font-size:13px;line-height:1.5;color:#5c7380;">
                                Subscribed as <a href="mailto:{{ $email }}" style="color:#2e96bc;text-decoration:none;">{{ $email }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fbff;padding:20px 32px;border-top:1px solid #e3eef5;">
                            <p style="margin:0;font-size:12px;color:#5c7380;">© {{ date('Y') }} {{ $brand }}. Pure Water. Pure Dignity.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
