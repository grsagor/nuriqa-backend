@extends('gazian::layout.app')

@section('title', 'Trade Enquiries — Gazian Water Admin')

@section('content')
<div class="page-shell">
    <nav class="breadcrumb-modern">
        <a href="{{ route('gazian.admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Trade Enquiries</span>
    </nav>

    <div class="page-top">
        <div>
            <h1 class="page-heading">Trade enquiries</h1>
            <p class="page-subtitle">Submissions from the Gazian Water trade enquiry form</p>
        </div>
    </div>

    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Interest</th>
                    <th>Business</th>
                    <th>Location</th>
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
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'interest', name: 'interest' },
            { data: 'business_type', name: 'business_type' },
            {
                data: null,
                name: 'location',
                orderable: false,
                render: function (row) {
                    return (row.city || '') + ', ' + (row.country || '');
                }
            },
            { data: 'is_read', name: 'is_read' },
            { data: 'created_at', name: 'created_at' },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-end'
            }
        ],
        "{{ route('gazian.admin.trade-enquiries.list') }}"
    );
});
</script>
@endpush
