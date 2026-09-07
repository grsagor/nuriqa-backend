@extends('backend.layout.app')

@section('content')
<div class="page-shell">
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Support cases</span>
    </nav>

    <div class="page-top">
        <div>
            <h1 class="page-heading">Support cases</h1>
            <p class="page-subtitle">Damage, dispute, and query cases from customers</p>
        </div>
    </div>

    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>Case</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Customer</th>
                    <th>Owner</th>
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
            { data: 'case_number', name: 'case_number' },
            { data: 'type', name: 'type' },
            { data: 'subject', name: 'subject' },
            { data: 'customer', name: 'customer', searchable: false, orderable: false },
            { data: 'owner_name', name: 'owner_name', searchable: false, orderable: false },
            { data: 'status', name: 'status', searchable: false },
            { data: 'created_at', name: 'created_at' },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-end'
            }
        ],
        "{{ route('admin.support-cases.list') }}"
    );
});
</script>
@endpush
