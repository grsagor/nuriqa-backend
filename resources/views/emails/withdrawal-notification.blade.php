<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Withdrawal Request</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #FFA500;
            color: #333;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 8px 8px;
        }
        .alert {
            background-color: #E3F2FD;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
        }
        .field {
            margin-bottom: 15px;
        }
        .field-label {
            font-weight: bold;
            color: #FFA500;
            margin-bottom: 5px;
        }
        .field-value {
            color: #333;
        }
        .payment-details {
            background-color: #fff;
            padding: 15px;
            border-left: 4px solid #FFA500;
            margin-top: 10px;
            border-radius: 4px;
        }
        .status-badge {
            background-color: #FFA500;
            color: #333;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .action-buttons {
            margin-top: 20px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }
        .btn-primary {
            background-color: #2196F3;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .row {
            display: flex;
            margin-bottom: 15px;
        }
        .col {
            flex: 1;
            padding: 0 10px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>💰 New Withdrawal Request</h1>
        <p>Admin Action Required</p>
    </div>
    <div class="content">
        <div class="alert">
            <strong>⚠️ Action Required:</strong> A seller has requested a withdrawal that needs your review and approval.
        </div>

        <div class="row">
            <div class="col">
                <div class="field">
                    <div class="field-label">👤 Seller Name:</div>
                    <div class="field-value">{{ $user->first_name }} {{ $user->last_name }}</div>
                </div>

                <div class="field">
                    <div class="field-label">📧 Email:</div>
                    <div class="field-value"><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></div>
                </div>

                <div class="field">
                    <div class="field-label">📱 Phone:</div>
                    <div class="field-value">{{ $user->phone ?? 'Not provided' }}</div>
                </div>
            </div>
            <div class="col">
                <div class="field">
                    <div class="field-label">💵 Amount:</div>
                    <div class="field-value"><strong>${{ number_format($withdrawal->amount, 2) }}</strong></div>
                </div>

                <div class="field">
                    <div class="field-label">📅 Request Date:</div>
                    <div class="field-value">{{ $withdrawal->created_at->format('M d, Y H:i A') }}</div>
                </div>

                <div class="field">
                    <div class="field-label">🏷️ Status:</div>
                    <div class="field-value"><span class="status-badge">{{ ucfirst($withdrawal->status) }}</span></div>
                </div>

                <div class="field">
                    <div class="field-label">🔖 Reference ID:</div>
                    <div class="field-value">#{{ $withdrawal->id }}</div>
                </div>
            </div>
        </div>

        <div class="field">
            <div class="field-label">💳 Payment Method:</div>
            @if($paymentMethod)
                <div class="payment-details">
                    <p><strong>Type:</strong> {{ ucfirst($paymentMethod->type) }}</p>
                    @if($paymentMethod->type === 'bank_account')
                        <p><strong>Bank Name:</strong> {{ $paymentMethod->details['bank_name'] ?? 'N/A' }}</p>
                        <p><strong>Account Name:</strong> {{ $paymentMethod->details['account_name'] ?? 'N/A' }}</p>
                        <p><strong>Account Number:</strong> {{ $paymentMethod->details['account_number'] ?? 'N/A' }}</p>
                        <p><strong>Routing Number:</strong> {{ $paymentMethod->details['routing_number'] ?? 'N/A' }}</p>
                    @elseif($paymentMethod->type === 'paypal')
                        <p><strong>PayPal Email:</strong> {{ $paymentMethod->details['paypal_email'] ?? 'N/A' }}</p>
                    @elseif($paymentMethod->type === 'stripe')
                        <p><strong>Stripe Account ID:</strong> {{ $paymentMethod->details['stripe_account_id'] ?? 'N/A' }}</p>
                    @endif
                    <p><strong>Default Method:</strong> {{ $paymentMethod->is_default ? 'Yes' : 'No' }}</p>
                </div>
            @else
                <div class="field-value" style="color: #666;">No payment method found</div>
            @endif
        </div>

        @if($withdrawal->notes)
        <div class="field">
            <div class="field-label">📝 Seller Notes:</div>
            <div class="payment-details">
                {{ $withdrawal->notes }}
            </div>
        </div>
        @endif

        <div class="action-buttons">
            <a href="{{ config('app.url') }}/admin/withdrawals/{{ $withdrawal->id }}" class="btn btn-primary">
                👁️ View Withdrawal Details
            </a>
            <a href="{{ config('app.url') }}/admin/withdrawals" class="btn btn-secondary">
                📋 View All Withdrawals
            </a>
        </div>

        <div class="footer">
            <p>This is an automated notification from the Nuriqa Seller Dashboard.</p>
            <p>Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>