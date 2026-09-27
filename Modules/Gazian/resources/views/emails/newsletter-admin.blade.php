<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New newsletter subscriber</title>
</head>
<body style="margin:0;padding:0;background:#eef6fb;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#eef6fb;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background:#0c4a6e;padding:24px 32px;">
                            <p style="margin:0;font-size:13px;letter-spacing:0.12em;text-transform:uppercase;color:#9fd4ea;">{{ $brand }} Admin</p>
                            <h1 style="margin:8px 0 0;font-size:22px;color:#ffffff;font-weight:600;">New newsletter subscriber</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px 32px;">
                            <p style="margin:0 0 8px;font-size:13px;font-weight:600;color:#0c4a6e;text-transform:uppercase;letter-spacing:0.06em;">Email</p>
                            <p style="margin:0;font-size:16px;color:#0b2430;">
                                <a href="mailto:{{ $subscriberEmail }}" style="color:#2e96bc;text-decoration:none;">{{ $subscriberEmail }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
