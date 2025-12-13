@extends('backend.layout.app')

@section('content')
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Users</li>
    </ol>

    <div class="card shadow-lg mb-4 border-0 rounded-3 overflow-hidden">
        <div class="header-bg text-white py-4 border-0">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fas fa-users me-3 fs-4"></i>
                    <h5 class="m-0 fw-bold">Users Management</h5>
                </div>
                <button class="btn-primary-custom btn-sm open_modal_btn" data-url="{{ route('admin.users.create') }}"
                    data-modal-parent="#crudModal">
                    <i class="fas fa-plus me-1"></i> Add New User
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="datatable" class="table table-hover mb-0 align-middle">
                    <thead class="table-header-bg">
                        <tr>
                            <th class="border-0 fw-semibold text-uppercase small text-muted ps-4">Image</th>
                            <th class="border-0 fw-semibold text-uppercase small text-muted">Name</th>
                            <th class="border-0 fw-semibold text-uppercase small text-muted">Email</th>
                            <th class="border-0 fw-semibold text-uppercase small text-muted">Phone</th>
                            <th class="border-0 fw-semibold text-uppercase small text-muted">Role</th>
                            <th class="border-0 fw-semibold text-uppercase small text-muted">Joined</th>
                            <th class="border-0 fw-semibold text-uppercase small text-muted text-center pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .header-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }
        
        .table-header-bg {
            background-color: #f8f9fa;
        }
        
        .btn-primary-custom {
            background-color: #ffffff;
            color: #667eea;
            font-weight: 500;
            border: none;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 0.25rem;
            cursor: pointer;
            display: inline-block;
            text-align: center;
            vertical-align: middle;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.2s ease;
        }
        
        .btn-primary-custom:hover {
            background-color: #f8f9fa;
            color: #764ba2;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .btn-primary-custom:active {
            transform: translateY(0);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: fadeIn 0.5s ease-out;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .table-hover tbody tr {
            transition: all 0.2s ease;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }
        
        .table thead th {
            border-bottom: 2px solid #e9ecef;
            position: relative;
        }
        
        .table thead th::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transition: width 0.3s ease;
        }
        
        .table thead th:hover::after {
            width: 100%;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: "›";
            color: #667eea;
            font-weight: bold;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e9ecef;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            var table = initDataTable('#datatable', [{
                data: 'image',
                name: 'image',
                orderable: false,
                searchable: false,
                className: 'text-center ps-4'
            }, {
                data: 'name',
                name: 'name',
                render: function(data, type, row) {
                    return '<div class="d-flex align-items-center"><i class="fas fa-user me-2 text-primary"></i>' + data + '</div>';
                }
            }, {
                data: 'email',
                name: 'email'
            }, {
                data: 'phone',
                name: 'phone',
                render: function(data, type, row) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            }, {
                data: 'role',
                name: 'role',
                render: function(data, type, row) {
                    return '<span class="badge bg-primary">' + data + '</span>';
                }
            }, {
                data: 'created_at',
                name: 'created_at'
            }, {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                className: 'text-center pe-4',
                render: function(data, type, row) {
                    return data;
                }
            }], "{{ route('admin.users.list') }}");
        });
    </script>
@endpush
