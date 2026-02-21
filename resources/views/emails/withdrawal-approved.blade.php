<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Approved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #22c55e; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; }
        .field { margin-bottom: 15px; }
        .field-label { font-weight: bold; color: #166534; margin-bottom: 5px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Withdrawal Approved</h1>
    </div>
    <div class="content">
        <p>Hello {{ $withdrawal->user->name ?? 'there' }},</p>
        <p>Your withdrawal request has been <strong>approved</strong>.</p>
        <div class="field">
            <div class="field-label">Amount:</div>
            <div>${{ number_format($withdrawal->amount, 2) }}</div>
        </div>
        <div class="field">
            <div class="field-label">Reference:</div>
            <div>#{{ $withdrawal->id }}</div>
        </div>
        @if($withdrawal->admin_notes)
        <div class="field">
            <div class="field-label">Note:</div>
            <div>{{ $withdrawal->admin_notes }}</div>
        </div>
        @endif
        <p>The amount will be processed according to your selected payment method.</p>
        <div class="footer">
            <p>This is an automated message from {{ config('app.name') }}.</p>
        </div>
    </div>
</body>
</html>
