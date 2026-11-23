@extends('backend.layout.app')

@section('content')
<div class="page-shell">
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Trader verifications</span>
    </nav>

    <div class="page-top">
        <div>
            <h1 class="page-heading">Trader verifications</h1>
            <p class="page-subtitle">Traders cannot buy or sell until their application is approved</p>
        </div>
        <div>
            <select id="status_filter" class="form-select" aria-label="Filter by status">
                <option value="all">Submitted (all)</option>
                <option value="pending" selected>Waiting for review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="draft">Not submitted yet</option>
            </select>
        </div>
    </div>

    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>Trader</th>
                    <th>Business</th>
                    <th>Status</th>
                    <th>Submitted</th>
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
    const table = initDataTable(
        '#datatable',
        [
            { data: 'trader', name: 'trader', searchable: false, orderable: false },
            { data: 'business', name: 'business', searchable: false, orderable: false },
            { data: 'status', name: 'status', searchable: false },
            { data: 'submitted_at', name: 'submitted_at', searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-end' }
        ],
        "{{ route('admin.trader-verifications.list') }}",
        { ajax: { data: function (d) { d.status = $('#status_filter').val(); } } }
    );

    $('#status_filter').on('change', function () { table.ajax.reload(); });
});
</script>
@endpush
