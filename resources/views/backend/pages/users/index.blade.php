@extends('backend.layout.app')

@section('content')

<div class="page-shell">

    <!-- Breadcrumb -->
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Users</span>
    </nav>

    <!-- Header -->
    <div class="page-top">
        <div>
            <h1 class="page-heading">Users</h1>
            <p class="page-subtitle">Create and manage users</p>
        </div>

        <button
            class="btn-create open_modal_btn"
            data-url="{{ route('admin.users.create') }}"
            data-modal-parent="#crudModal">
            + New user  
        </button>
    </div>

    <!-- Table surface -->
    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>User name</th>
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
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-end'
            }
        ],
        "{{ route('admin.roles.list') }}"
    );
});
</script>
@endpush
