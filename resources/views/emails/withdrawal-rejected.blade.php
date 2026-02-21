<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Withdrawal Request Not Approved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #dc2626; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; }
        .field { margin-bottom: 15px; }
        .field-label { font-weight: bold; color: #991b1b; margin-bottom: 5px; }
        .reason { background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Withdrawal Request Not Approved</h1>
    </div>
    <div class="content">
        <p>Hello {{ $withdrawal->user->name ?? 'there' }},</p>
        <p>Your withdrawal request has <strong>not been approved</strong>. The amount has been returned to your available balance.</p>
        <div class="field">
            <div class="field-label">Amount:</div>
            <div>${{ number_format($withdrawal->amount, 2) }}</div>
        </div>
        <div class="field">
            <div class="field-label">Reference:</div>
            <div>#{{ $withdrawal->id }}</div>
        </div>
        @if($withdrawal->rejection_reason)
        <div class="field">
            <div class="field-label">Reason:</div>
            <div class="reason">{{ $withdrawal->rejection_reason }}</div>
        </div>
        @endif
        <p>If you have questions, please contact support.</p>
        <div class="footer">
            <p>This is an automated message from {{ config('app.name') }}.</p>
        </div>
    </div>
</body>
</html>
