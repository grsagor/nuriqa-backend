<div class="modal-header">
    <h5 class="modal-title">Transaction Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 mb-3">
            <strong>Invoice No:</strong>
            <p>{{ $transaction->invoice_no }}</p>
        </div>
        <div class="col-md-6 mb-3">
            <strong>User:</strong>
            <p>{{ $transaction->user->name ?? 'N/A' }}</p>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Status:</strong>
            <p>
                <span class="badge bg-{{ $transaction->status === 'completed' ? 'success' : ($transaction->status === 'cancelled' ? 'danger' : 'warning') }}">
                    {{ ucfirst($transaction->status) }}
                </span>
            </p>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Payment Method:</strong>
            <p>{{ $transaction->payment_method ? ucfirst($transaction->payment_method) : 'N/A' }}</p>
        </div>
        <div class="col-md-12 mb-3">
            <strong>Billing Information:</strong>
            <p>
                {{ $transaction->billing_first_name }} {{ $transaction->billing_last_name }}<br>
                {{ $transaction->billing_email }}<br>
                {{ $transaction->billing_phone }}
            </p>
        </div>
        <div class="col-md-12 mb-3">
            <strong>Order Summary:</strong>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction->sellLines as $line)
                    <tr>
                        <td>{{ $line->product->title ?? 'N/A' }}</td>
                        <td>{{ $line->quantity }}</td>
                        <td>£{{ number_format($line->unit_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Subtotal:</strong>
            <p>£{{ number_format($transaction->subtotal, 2) }}</p>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Tax:</strong>
            <p>£{{ number_format($transaction->tax, 2) }}</p>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Delivery Fee:</strong>
            <p>£{{ number_format($transaction->delivery_fee, 2) }}</p>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Coupon Discount:</strong>
            <p>£{{ number_format($transaction->coupon_discount, 2) }}</p>
        </div>
        <div class="col-md-12 mb-3">
            <strong>Total:</strong>
            <p class="fw-bold">£{{ number_format($transaction->total, 2) }}</p>
        </div>
        <div class="col-md-12 mb-3">
            <strong>Created At:</strong>
            <p>{{ $transaction->created_at->format('d M Y H:i') }}</p>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
