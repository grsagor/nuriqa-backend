@extends('backend.layout.app')

@section('content')

<div class="page-shell">

    <!-- Breadcrumb -->
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Languages</span>
    </nav>

    <!-- Header -->
    <div class="page-top">
        <div>
            <h1 class="page-heading">Languages</h1>
            <p class="page-subtitle">Create and manage system languages</p>
        </div>

        <button
            class="btn btn-create open_modal_btn"
            data-url="{{ route('admin.languages.create') }}"
            data-modal-parent="#crudModal">
            + New language
        </button>
    </div>

    <!-- Table surface -->
    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
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
            { data: 'name', name: 'name' },
            { data: 'code', name: 'code' },
            { data: 'is_active', name: 'is_active' },
            { data: 'created_at', name: 'created_at' },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-end'
            }
        ],
        "{{ route('admin.languages.list') }}"
    );
});
</script>
@endpush