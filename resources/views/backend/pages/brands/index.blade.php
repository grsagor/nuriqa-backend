@extends('backend.layout.app')

@section('content')

<div class="page-shell">

    <!-- Breadcrumb -->
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Brands</span>
    </nav>

    <!-- Header -->
    <div class="page-top">
        <div>
            <h1 class="page-heading">Brands</h1>
            <p class="page-subtitle">Create and manage product brands</p>
        </div>

        <button
            class="btn btn-create open_modal_btn"
            data-url="{{ route('admin.brands.create') }}"
            data-modal-parent="#crudModal">
            + New brand
        </button>
    </div>

    <!-- Table surface -->
    <div class="surface">
        <table id="datatable" class="data-table">
            <thead>
                <tr>
                    <th>Brand name</th>
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
        "{{ route('admin.brands.list') }}"
    );
});
</script>
@endpush