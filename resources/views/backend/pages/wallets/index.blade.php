@extends('backend.layout.app')

@section('content')
<div class="page-shell">

    <!-- Breadcrumb -->
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Wallets</span>
    </nav>

    <!-- Header -->
    <div class="page-top">
        <div>
            <h1 class="page-heading">Wallets</h1>
            <p class="page-subtitle">Manage user wallets and balances</p>
        </div>
    </div>

    <!-- Table surface -->
    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Available Balance</th>
                    <th>Pending Balance</th>
                    <th>Total Balance</th>
                    <th>Status</th>
                    <th>Created</th>
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
            { data: 'user', name: 'user' },
            { data: 'email', name: 'email' },
            { data: 'available_balance', name: 'available_balance' },
            { data: 'pending_balance', name: 'pending_balance' },
            { data: 'total_balance', name: 'total_balance' },
            { data: 'status', name: 'status' },
            { data: 'created', name: 'created' },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-end'
            }
        ],
        "{{ route('admin.wallets.list') }}"
    );
});
</script>
@endpush
