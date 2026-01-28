@extends('backend.layout.master')

@section('title', 'Wallet Transactions - ' . $wallet->user->name ?? 'Unknown')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Wallet Transactions</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.wallets.index') }}">Wallets</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.wallets.show', $wallet->id) }}">{{ $wallet->user->name ?? 'Unknown' }}</a></li>
        <li class="breadcrumb-item active">Transactions</li>
    </ol>

    <!-- Wallet Summary -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-wallet me-1"></i>
                    Wallet Summary - {{ $wallet->user->name ?? 'Unknown' }}
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h5 class="text-success">Available Balance</h5>
                                <h3>${{ number_format($wallet->available_balance, 2) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h5 class="text-warning">Pending Balance</h5>
                                <h3>${{ number_format($wallet->pending_balance, 2) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h5 class="text-primary">Total Earnings</h5>
                                <h3>${{ number_format($wallet->total_earnings, 2) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h5 class="text-info">Total Withdrawals</h5>
                                <h3>${{ number_format($wallet->withdrawals()->where('status', 'completed')->sum('amount'), 2) }}</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filter Transactions
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.wallets.transactions', $wallet->id) }}">
                <div class="row">
                    <div class="col-md-3">
                        <label for="transaction_type" class="form-label">Transaction Type</label>
                        <select id="transaction_type" name="transaction_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="withdrawal">Withdrawals</option>
                            <option value="earnings">Earnings</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.wallets.transactions', $wallet->id) }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Withdrawals Table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-exchange-alt me-1"></i>
            Transaction History
            <a href="{{ route('admin.wallets.show', $wallet->id) }}" class="btn btn-sm btn-secondary float-end">
                <i class="fas fa-arrow-left me-1"></i> Back to Wallet
            </a>
        </div>
        <div class="card-body">
            @if($wallet->withdrawals && $wallet->withdrawals->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered" id="transactionsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Processed</th>
                                <th>Processed By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($wallet->withdrawals as $withdrawal)
                                <tr>
                                    <td>#{{ $withdrawal->id }}</td>
                                    <td>
                                        <span class="badge bg-warning">Withdrawal</span>
                                    </td>
                                    <td class="fw-bold text-danger">-${{ number_format($withdrawal->amount, 2) }}</td>
                                    <td>
                                        @if($withdrawal->paymentMethod)
                                            <div>
                                                <strong>{{ ucfirst($withdrawal->paymentMethod->type) }}</strong><br>
                                                <small class="text-muted">
                                                    {{ $withdrawal->paymentMethod->account_number ?? 'N/A' }}
                                                </small>
                                            </div>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @switch($withdrawal->status)
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @break
                                            @case('approved')
                                                <span class="badge bg-info">Approved</span>
                                                @break
                                            @case('completed')
                                                <span class="badge bg-success">Completed</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-secondary">Cancelled</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>{{ $withdrawal->created_at->format('M j, Y g:i A') }}</td>
                                    <td>{{ $withdrawal->processed_at?->format('M j, Y g:i A') ?? '-' }}</td>
                                    <td>
                                        @if($withdrawal->processed_by && $withdrawal->processor)
                                            {{ $withdrawal->processor->name }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.withdrawals.show', $withdrawal->id) }}" class="btn btn-sm btn-info" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($withdrawal->status === 'pending')
                                                <form method="POST" action="{{ route('admin.withdrawals.approve', $withdrawal->id) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Approve" onclick="return confirm('Are you sure you want to approve this withdrawal?')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-danger" title="Reject" onclick="showRejectForm({{ $withdrawal->id }})">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No transactions found</h4>
                    <p class="text-muted">This user hasn't made any transactions yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Reject Withdrawal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="rejectForm">
                @csrf
                <div class="modal-body">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#transactionsTable').DataTable({
        responsive: true,
        order: [[5, 'desc']],
        pageLength: 25
    });

    window.showRejectForm = function(withdrawalId) {
        const form = document.getElementById('rejectForm');
        form.action = `/admin/withdrawals/${withdrawalId}/reject`;
        const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
        modal.show();
    };
});
</script>
@endsection