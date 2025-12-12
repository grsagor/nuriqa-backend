@extends('backend.layout.app')

@section('content')
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.html">Dashboard</a></li>
        <li class="breadcrumb-item active">Roles</li>
    </ol>

    <div class="card mb-4 p-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="m-0">Roles</h5>
            <button class="btn btn-primary open_modal_btn" data-url="{{ route('admin.roles.create') }}"
                data-modal-parent="#crudModal"><i class="fas fa-plus"></i> Add</button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <table id="datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            initDataTable('#datatable', [{
                data: 'name',
                name: 'name',
            }, {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
            }], "{{ route('admin.roles.list') }}");
        });
    </script>
@endpush
