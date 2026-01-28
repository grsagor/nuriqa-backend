@extends('backend.layout.master')

@section('title', 'Withdrawal Details #' . $withdrawal->id)

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Withdrawal Details</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.withdrawals.index') }}">Withdrawals</a></li>
        <li class="breadcrumb-item active">#{{ $withdrawal->id }}</li>
    </ol>

    <!-- Withdrawal Status Card -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-money-bill-wave me-1"></i>
                    Withdrawal Information
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Withdrawal ID:</strong> #{{ $withdrawal->id }}</p>
                            <p><strong>User:</strong> {{ $withdrawal->user->name ?? 'N/A' }}</p>
                            <p><strong>Email:</strong> {{ $withdrawal->user->email ?? 'N/A' }}</p>
                            <p><strong>Amount:</strong> <span class="fs-5 fw-bold text-success">${{ number_format($withdrawal->amount, 2) }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong>
                                @switch($withdrawal->status)
                                    @case('pending')
                                        <span class="badge bg-warning fs-6">Pending</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-info fs-6">Approved</span>
                                        @break
                                    @case('completed')
                                        <span class="badge bg-success fs-6">Completed</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger fs-6">Rejected</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-secondary fs-6">Cancelled</span>
                                        @break
                                @endswitch
                            </p>
                            <p><strong>Requested:</strong> {{ $withdrawal->created_at->format('M j, Y g:i A') }}</p>
                            <p><strong>Processed:</strong> {{ $withdrawal->processed_at?->format('M j, Y g:i A') ?? 'Not processed yet' }}</p>
                            <p><strong>Processed By:</strong> 
                                @if($withdrawal->processed_by && $withdrawal->processor)
                                    {{ $withdrawal->processor->name }}
                                @else
                                    Not processed yet
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-wallet me-1"></i>
                    Wallet Information
                </div>
                <div class="card-body">
                    <p><strong>Available Balance:</strong> <span class="text-success">${{ number_format($withdrawal->wallet->available_balance, 2) }}</span></p>
                    <p><strong>Pending Balance:</strong> <span class="text-warning">${{ number_format($withdrawal->wallet->pending_balance, 2) }}</span></p>
                    <p><strong>Total Earnings:</strong> <span class="text-primary">${{ number_format($withdrawal->wallet->total_earnings, 2) }}</span></p>
                    <a href="{{ route('admin.wallets.show', $withdrawal->wallet_id) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye me-1"></i> View Wallet
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Method Details -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-credit-card me-1"></i>
            Payment Method
        </div>
        <div class="card-body">
            @if($withdrawal->paymentMethod)
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Type:</strong> {{ ucfirst($withdrawal->paymentMethod->type) }}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Account Number:</strong> {{ $withdrawal->paymentMethod->account_number ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Account Name:</strong> {{ $withdrawal->paymentMethod->account_name ?? 'N/A' }}</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Default:</strong>
                            @if($withdrawal->paymentMethod->is_default)
                                <span class="badge bg-primary">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </p>
                    </div>
                </div>
                @if($withdrawal->paymentMethod->additional_details)
                    <div class="row mt-3">
                        <div class="col-12">
                            <p><strong>Additional Details:</strong></p>
                            <pre class="bg-light p-2 rounded">{{ $withdrawal->paymentMethod->additional_details }}</pre>
                        </div>
                    </div>
                @endif
            @else
                <p class="text-muted">No payment method information available</p>
            @endif
        </div>
    </div>

    <!-- Admin Notes & Actions -->
    @if($withdrawal->status === 'pending')
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-tools me-1"></i>
                Actions
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal->id) }}" class="d-inline">
                    @csrf
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes (Optional)</label>
                        <textarea id="admin_notes" name="admin_notes" class="form-control" rows="3" placeholder="Add notes for this approval..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this withdrawal?')">
                        <i class="fas fa-check me-1"></i> Approve Withdrawal
                    </button>
                </form>
                
                <button type="button" class="btn btn-danger" onclick="showRejectForm()">
                    <i class="fas fa-times me-1"></i> Reject Withdrawal
                </button>
            </div>
        </div>
    @endif

    <!-- Notes and Reason -->
    @if($withdrawal->admin_notes || $withdrawal->rejection_reason)
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-sticky-note me-1"></i>
                Notes & Reason
            </div>
            <div class="card-body">
                @if($withdrawal->admin_notes)
                    <div class="mb-3">
                        <h6>Admin Notes:</h6>
                        <p class="bg-light p-2 rounded">{{ $withdrawal->admin_notes }}</p>
                    </div>
                @endif
                
                @if($withdrawal->rejection_reason)
                    <div class="mb-3">
                        <h6>Rejection Reason:</h6>
                        <p class="bg-danger text-white p-2 rounded">{{ $withdrawal->rejection_reason }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Timeline -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-clock me-1"></i>
            Timeline
        </div>
        <div class="card-body">
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker bg-primary"></div>
                    <div class="timeline-content">
                        <h6>Withdrawal Requested</h6>
                        <p class="text-muted">{{ $withdrawal->created_at->format('M j, Y g:i A') }}</p>
                        <p>Withdrawal of ${{ number_format($withdrawal->amount, 2) }} was requested by {{ $withdrawal->user->name ?? 'User' }}</p>
                    </div>
                </div>
                
                @if($withdrawal->processed_at)
                    <div class="timeline-item">
                        <div class="timeline-marker bg-{{ $withdrawal->status === 'completed' ? 'success' : ($withdrawal->status === 'rejected' ? 'danger' : 'info') }}"></div>
                        <div class="timeline-content">
                            <h6>{{ ucfirst($withdrawal->status) }}</h6>
                            <p class="text-muted">{{ $withdrawal->processed_at->format('M j, Y g:i A') }}</p>
                            <p>
                                @if($withdrawal->processed_by && $withdrawal->processor)
                                    Processed by {{ $withdrawal->processor->name }}
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-4">
        <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Withdrawals
        </a>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Reject Withdrawal #{{ $withdrawal->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                        <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason for rejecting this withdrawal..."></textarea>
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

<style>
.timeline {
    position: relative;
    padding-left: 40px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-content h6 {
    margin-bottom: 5px;
    color: #495057;
}

.timeline-content p {
    margin-bottom: 0;
}
</style>

<script>
function showRejectForm() {
    const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
    modal.show();
}
</script>
@endsection