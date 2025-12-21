@extends('backend.layout.app')

@section('content')
    <div class="page-shell">

        <!-- Breadcrumb -->
        <nav class="breadcrumb-modern">
            <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
            <span>/</span>
            <span>Products</span>
        </nav>

        <!-- Header -->
        <div class="page-top">
            <div>
                <h1 class="page-heading">Products</h1>
                <p class="page-subtitle">Create and manage product listings</p>
            </div>

            <button class="btn btn-create open_modal_btn" data-url="{{ route('admin.products.create') }}"
                data-modal-parent="#crudModal">
                + New product
            </button>
        </div>

        <!-- Table surface -->
        <div class="surface">
            <table id="datatable" class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Owner</th>
                        <th>Price</th>
                        <th>Location</th>
                        <th>Upload Date</th>
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
        $(function() {
            initDataTable(
                '#datatable',
                [{
                        data: 'thumbnail',
                        name: 'thumbnail',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'title',
                        name: 'title'
                    },
                    {
                        data: 'owner',
                        name: 'owner'
                    },
                    {
                        data: 'price',
                        name: 'price'
                    },
                    {
                        data: 'location',
                        name: 'location'
                    },
                    {
                        data: 'upload_date',
                        name: 'upload_date'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    }
                ],
                "{{ route('admin.products.list') }}"
            );
        });

        $(document).ready(function() {
            // Handle current thumbnail removal
            $(document).on("click", "#removeCurrentThumbnail", function() {
                $('.current-image-preview').hide();
                $('#remove_thumbnail').val('1');
            });

            // Handle current images removal
            $(document).on("click", ".remove-current-image", function() {
                alert("hello")
                var imageId = $(this).data('image-id');
                var input = $('.remove-image-input[data-image-id="' + imageId + '"]');
                input.val(imageId);
                $(this).closest('.position-relative').hide();
            });
        })
    </script>
@endpush
