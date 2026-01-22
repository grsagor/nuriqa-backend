@extends('backend.layout.app')

@section('content')
<div class="page-shell">
    <!-- Breadcrumb -->
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Transactions</span>
    </nav>

    <!-- Header -->
    <div class="page-top">
        <div>
            <h1 class="page-heading">Transactions</h1>
            <p class="page-subtitle">Manage all orders and transactions</p>
        </div>
    </div>

    <!-- Table surface -->
    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>User</th>
                    <th>Total</th>
                    <th>Items</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    initDataTable(
        '#datatable',
        [
            { data: 'invoice_no', name: 'invoice_no' },
            { data: 'user', name: 'user' },
            { data: 'total', name: 'total' },
            { data: 'items_count', name: 'items_count' },
            { data: 'payment_method', name: 'payment_method' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-end'
            }
        ],
        "{{ route('admin.transactions.list') }}"
    );
});
</script>
@endpush
