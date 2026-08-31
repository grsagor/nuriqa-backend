@extends('backend.layout.app')

@section('content')
    <div class="page-shell">

        <!-- Breadcrumb -->
        <nav class="breadcrumb-modern">
            <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
            <span>/</span>
            <span>{{ $breadcrumbLabel ?? 'Products' }}</span>
        </nav>

        <!-- Header -->
        <div class="page-top">
            <div>
                <h1 class="page-heading">{{ $pageTitle ?? 'Products' }}</h1>
                <p class="page-subtitle">{{ $pageSubtitle ?? 'Create and manage product listings' }}</p>
            </div>

            @if(!empty($catalogType))
                <button type="button" class="btn btn-create open_modal_btn"
                    data-url="{{ route('admin.products.create', ['catalogType' => $catalogType]) }}"
                    data-modal-parent="#crudModal">
                    + Add {{ $catalogType === 'hajra' ? 'Hajra' : 'Merchandise' }}
                </button>
            @endif
        </div>

        <!-- Table surface -->
        <div class="surface">
            <table id="datatable" class="data-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        @if(empty($catalogType))
                            <th>Type</th>
                        @endif
                        <th>Owner</th>
                        <th>Price</th>
                        @if($showApprovalColumn ?? empty($catalogType))
                            <th>Approval</th>
                        @endif
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
            @php
                $listColumns = [
                    ['data' => 'thumbnail', 'name' => 'thumbnail', 'orderable' => false, 'searchable' => false],
                    ['data' => 'title', 'name' => 'title'],
                ];
                if (empty($catalogType)) {
                    $listColumns[] = ['data' => 'type', 'name' => 'type', 'orderable' => false];
                }
                $listColumns[] = ['data' => 'owner', 'name' => 'owner'];
                $listColumns[] = ['data' => 'price', 'name' => 'price'];
                if ($showApprovalColumn ?? empty($catalogType)) {
                    $listColumns[] = ['data' => 'approval_status', 'name' => 'approval_status', 'orderable' => false];
                }
                $listColumns = array_merge($listColumns, [
                    ['data' => 'location', 'name' => 'location'],
                    ['data' => 'upload_date', 'name' => 'upload_date'],
                    ['data' => 'action', 'name' => 'action', 'orderable' => false, 'searchable' => false, 'className' => 'text-end'],
                ]);
            @endphp
            initDataTable(
                '#datatable',
                @json($listColumns),
                @json(route('admin.products.list', array_filter(['type' => $catalogType ?? null])))
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
