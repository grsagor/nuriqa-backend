<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Verification code') }}</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #333; max-width: 480px; margin: 0 auto; padding: 24px;">
    <p>{{ __('Hello,') }}</p>
    <p>{{ __('Use this code to verify your email on :name:', ['name' => config('app.name')]) }}</p>
    <p style="font-size: 28px; font-weight: 700; letter-spacing: 0.2em; padding: 16px 24px; background: #f5f5f5; border-radius: 8px; text-align: center;">
        {{ $otpCode }}
    </p>
    <p style="font-size: 14px; color: #666;">{{ __('This code expires in 15 minutes. If you did not register, you can ignore this email.') }}</p>
</body>
</html>
