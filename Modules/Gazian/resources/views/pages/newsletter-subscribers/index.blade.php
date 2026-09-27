@extends('gazian::layout.app')

@section('title', 'Newsletter — Gazian Water Admin')

@section('content')
<div class="page-shell">
    <nav class="breadcrumb-modern">
        <a href="{{ route('gazian.admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Newsletter</span>
    </nav>

    <div class="page-top d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="page-heading">Newsletter subscribers</h1>
            <p class="page-subtitle">Emails from the Gazian Water join form</p>
        </div>
        <a href="{{ route('gazian.admin.newsletter-subscribers.export-csv') }}" class="btn btn-sm btn-outline-secondary text-nowrap">
            <i class="fas fa-file-csv me-1"></i> Download CSV
        </a>
    </div>

    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Subscribed at</th>
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
            { data: 'email', name: 'email' },
            { data: 'created_at', name: 'created_at' },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-end'
            }
        ],
        "{{ route('gazian.admin.newsletter-subscribers.list') }}"
    );
});
</script>
@endpush
