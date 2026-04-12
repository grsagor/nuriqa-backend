@extends('backend.layout.app')

@section('content')
<div class="page-shell">
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Seller reports</span>
    </nav>

    <div class="page-top">
        <div>
            <h1 class="page-heading">Seller reports</h1>
            <p class="page-subtitle">Reports submitted by customers about sellers</p>
        </div>
    </div>

    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>Reporter</th>
                    <th>Reported seller</th>
                    <th>Reason</th>
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
            { data: 'reporter_name', name: 'reporter_name', searchable: false, orderable: false },
            { data: 'reported_name', name: 'reported_name', searchable: false, orderable: false },
            { data: 'reason', name: 'reason' },
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
        "{{ route('admin.seller-reports.list') }}"
    );
});
</script>
@endpush
