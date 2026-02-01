@extends('backend.layout.app')

@section('content')
<div class="page-shell">
    <!-- Breadcrumb -->
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Withdrawals</span>
    </nav>

    <!-- Header -->
    <div class="page-top">
        <div>
            <h1 class="page-heading">Withdrawals</h1>
            <p class="page-subtitle">Manage withdrawal requests and payments</p>
        </div>
        <a href="{{ route('admin.withdrawals.statistics') }}" class="btn btn-create">
            Statistics
        </a>
    </div>

    <!-- Table surface -->
    <div class="surface">
        <table id="datatable" class="data-table">
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
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
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

@push('scripts')
<script>
$(function () {
    initDataTable(
        '#datatable',
        [
            { data: 'id', name: 'id' },
            { data: 'user', name: 'user' },
            { data: 'amount', name: 'amount' },
            { data: 'status', name: 'status' },
            { data: 'payment_method', name: 'payment_method' },
            { data: 'requested', name: 'requested' },
            { data: 'processed', name: 'processed' },
            { data: 'processed_by', name: 'processed_by' },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-end'
            }
        ],
        "{{ route('admin.withdrawals.list') }}"
    );

    window.showRejectForm = function(withdrawalId) {
        const form = document.getElementById('rejectForm');
        form.action = `/admin/withdrawals/${withdrawalId}/reject`;
        const modal = new bootstrap.Modal(document.getElementById('rejectModal'));
        modal.show();
    };
});
</script>
@endpush
@endsection