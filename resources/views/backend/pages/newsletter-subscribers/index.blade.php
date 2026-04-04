@extends('backend.layout.app')

@section('content')
<div class="page-shell">
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Newsletter subscribers</span>
    </nav>

    <div class="page-top d-flex flex-wrap justify-content-between align-items-start gap-3">
        <div>
            <h1 class="page-heading">Newsletter subscribers</h1>
            <p class="page-subtitle">Emails collected from the website footer signup form</p>
        </div>
        <a href="{{ route('admin.newsletter-subscribers.export-csv') }}" class="btn btn-sm btn-outline-secondary text-nowrap">
            <i class="fas fa-file-csv me-1"></i> Download CSV
        </a>
    </div>

    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Locale</th>
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
            { data: 'locale', name: 'locale' },
            { data: 'created_at', name: 'created_at' },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-end'
            }
        ],
        "{{ route('admin.newsletter-subscribers.list') }}"
    );
});
</script>
@endpush
