<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Newsletter') }}</title>
</head>
<body style="font-family: system-ui, sans-serif; line-height: 1.6; color: #333; max-width: 520px; margin: 0 auto; padding: 24px;">
    <p>{{ __('Hello,') }}</p>
    <p>{{ __('Thank you for joining the :name mailing list.', ['name' => config('app.name')]) }}</p>
    <p>{{ __('You will hear from us about launches, stories, and updates from our community.') }}</p>
    <p style="font-size: 14px; color: #666;">{{ __('If you did not sign up, you can ignore this email.') }}</p>
</body>
</html>
