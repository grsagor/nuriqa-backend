@extends('backend.layout.master')

@section('title', 'Withdrawals Management')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Withdrawals Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Withdrawals</li>
    </ol>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    Total Requested
                    <div class="h3 mb-0">${{ number_format($totalRequested, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    Pending Amount
                    <div class="h3 mb-0">${{ number_format($pendingAmount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    Completed Amount
                    <div class="h3 mb-0">${{ number_format($completedAmount, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    Rejected Amount
                    <div class="h3 mb-0">${{ number_format($rejectedAmount, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filter Withdrawals
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.withdrawals.index') }}">
                <div class="row">
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="amount_from" class="form-label">Min Amount</label>
                        <input type="number" id="amount_from" name="amount_from" class="form-control" step="0.01" value="{{ request('amount_from') }}" placeholder="0.00">
                    </div>
                    <div class="col-md-2">
                        <label for="amount_to" class="form-label">Max Amount</label>
                        <input type="number" id="amount_to" name="amount_to" class="form-control" step="0.01" value="{{ request('amount_to') }}" placeholder="0.00">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label><br>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i> Apply
                        </button>
                        <a href="{{ route('admin.withdrawals.index') }}" class="btn btn-secondary">
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
            <i class="fas fa-money-bill-wave me-1"></i>
            All Withdrawals
            <a href="{{ route('admin.withdrawals.statistics') }}" class="btn btn-sm btn-info float-end">
                <i class="fas fa-chart-bar me-1"></i> Statistics
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="withdrawalsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Requested</th>
                            <th>Processed</th>
                            <th>Processed By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($withdrawals as $withdrawal)
                            <tr>
                                <td>#{{ $withdrawal->id }}</td>
                                <td>
                                    <div>
                                        <strong>{{ $withdrawal->user->name ?? 'N/A' }}</strong><br>
                                        <small class="text-muted">{{ $withdrawal->user->email ?? 'N/A' }}</small>
                                    </div>
                                </td>
                                <td class="fw-bold">${{ number_format($withdrawal->amount, 2) }}</td>
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
                                <td>
                                    @if($withdrawal->paymentMethod)
                                        <div>
                                            <strong>{{ ucfirst($withdrawal->paymentMethod->type) }}</strong><br>
                                            <small class="text-muted">
                                                {{ $withdrawal->paymentMethod->account_number ?? 'N/A' }}
                                                @if($withdrawal->paymentMethod->is_default)
                                                    <span class="badge bg-primary">Default</span>
                                                @endif
                                            </small>
                                        </div>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
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
                                        
                                        <a href="{{ route('admin.wallets.show', $withdrawal->wallet_id) }}" class="btn btn-sm btn-primary" title="View Wallet">
                                            <i class="fas fa-wallet"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No withdrawals found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between">
                <div>
                    Showing {{ $withdrawals->firstItem() }} to {{ $withdrawals->lastItem() }} of {{ $withdrawals->total() }} entries
                </div>
                <div>
                    {{ $withdrawals->links() }}
                </div>
            </div>
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
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Rejecting this withdrawal will return the amount to the user's available balance.
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#withdrawalsTable').DataTable({
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