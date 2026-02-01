@extends('backend.layout.app')

@section('title', 'Withdrawal Details #' . $withdrawal->id)

@section('content')
<div class="page-shell">
    <!-- Breadcrumb -->
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.withdrawals.index') }}">Withdrawals</a>
        <span>/</span>
        <span>#{{ $withdrawal->id }}</span>
    </nav>

    <!-- Header -->
    <div class="page-top">
        <div>
            <h1 class="page-heading">Withdrawal Details #{{ $withdrawal->id }}</h1>
            <p class="page-subtitle">View and manage withdrawal request</p>
        </div>
    </div>

    <!-- Content -->
    <div class="surface">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Withdrawal Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Withdrawal ID:</strong>
                                <p>#{{ $withdrawal->id }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Status:</strong>
                                <p>
                                    <span class="badge bg-{{ $withdrawal->status === 'completed' ? 'success' : ($withdrawal->status === 'rejected' ? 'danger' : ($withdrawal->status === 'approved' ? 'info' : 'warning')) }}">
                                        {{ ucfirst($withdrawal->status) }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>User:</strong>
                                <p>{{ $withdrawal->user->name ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Email:</strong>
                                <p>{{ $withdrawal->user->email ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Amount:</strong>
                                <p class="fw-bold text-success fs-5">${{ number_format($withdrawal->amount, 2) }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Requested:</strong>
                                <p>{{ $withdrawal->created_at->format('M j, Y g:i A') }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Processed:</strong>
                                <p>{{ $withdrawal->processed_at ? $withdrawal->processed_at->format('M j, Y g:i A') : 'Not processed yet' }}</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Processed By:</strong>
                                <p>{{ $withdrawal->processed_by && $withdrawal->processor ? $withdrawal->processor->name : 'Not processed yet' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if($withdrawal->paymentMethod)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <strong>Type:</strong>
                                    <p>{{ ucfirst($withdrawal->paymentMethod->type) }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <strong>Account Name:</strong>
                                    <p>{{ $withdrawal->paymentMethod->account_name ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($withdrawal->admin_notes || $withdrawal->rejection_reason)
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Notes & Reason</h5>
                        </div>
                        <div class="card-body">
                            @if($withdrawal->admin_notes)
                                <div class="mb-3">
                                    <strong>Admin Notes:</strong>
                                    <p>{{ $withdrawal->admin_notes }}</p>
                                </div>
                            @endif
                            @if($withdrawal->rejection_reason)
                                <div class="mb-3">
                                    <strong>Rejection Reason:</strong>
                                    <p class="text-danger">{{ $withdrawal->rejection_reason }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Wallet Information</h5>
                    </div>
                    <div class="card-body">
                        @if($withdrawal->wallet)
                            <p><strong>Available Balance:</strong> <span class="text-success">${{ number_format($withdrawal->wallet->available_balance, 2) }}</span></p>
                            <p><strong>Pending Balance:</strong> <span class="text-warning">${{ number_format($withdrawal->wallet->pending_balance, 2) }}</span></p>
                            <p><strong>Total Earnings:</strong> <span class="text-primary">${{ number_format($withdrawal->wallet->total_earnings, 2) }}</span></p>
                            <a href="{{ route('admin.wallets.show', $withdrawal->wallet->id) }}" class="btn btn-sm btn-primary w-100 mt-3">
                                <i class="fas fa-eye me-1"></i> View Wallet
                            </a>
                        @else
                            <p class="text-muted">Wallet information not available</p>
                        @endif
                    </div>
                </div>

                @if($withdrawal->status === 'pending')
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal->id) }}" class="mb-3">
                                @csrf
                                <div class="mb-2">
                                    <label for="admin_notes" class="form-label">Admin Notes (Optional)</label>
                                    <textarea id="admin_notes" name="admin_notes" class="form-control form-control-sm" rows="2" placeholder="Add notes..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to approve this withdrawal?')">
                                    <i class="fas fa-check me-1"></i> Approve Withdrawal
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger w-100" onclick="showRejectForm()">
                                <i class="fas fa-times me-1"></i> Reject Withdrawal
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-4">
            <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Withdrawals
            </a>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Withdrawal #{{ $withdrawal->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.withdrawals.reject', $withdrawal->id) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Rejecting this withdrawal will return ${{ number_format($withdrawal->amount, 2) }} to the user's available balance.
                    </div>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason *</label>
                        <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Withdrawal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showRejectForm() {
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>
@endsection
