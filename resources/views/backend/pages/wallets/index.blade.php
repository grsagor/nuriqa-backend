@extends('backend.layout.master')

@section('title', 'Wallets Management')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Wallets Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Wallets</li>
    </ol>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    Total Available Balance
                    <div class="h3 mb-0">${{ number_format($totalBalance, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    Total Pending Balance
                    <div class="h3 mb-0">${{ number_format($totalPending, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    Total Wallets
                    <div class="h3 mb-0">{{ $totalWallets }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    Active Users
                    <div class="h3 mb-0">{{ $activeUsers }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallets Table -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-wallet me-1"></i>
            All Wallets
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="walletsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Available Balance</th>
                            <th>Pending Balance</th>
                            <th>Total Balance</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wallets as $wallet)
                            <tr>
                                <td>{{ $wallet->user->name ?? 'N/A' }}</td>
                                <td>{{ $wallet->user->email ?? 'N/A' }}</td>
                                <td class="text-success fw-bold">${{ number_format($wallet->available_balance, 2) }}</td>
                                <td class="text-warning fw-bold">${{ number_format($wallet->pending_balance, 2) }}</td>
                                <td class="text-primary fw-bold">${{ number_format($wallet->available_balance + $wallet->pending_balance, 2) }}</td>
                                <td>
                                    @if($wallet->available_balance > 0)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">No Balance</span>
                                    @endif
                                </td>
                                <td>{{ $wallet->created_at->format('M j, Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.wallets.show', $wallet->id) }}" class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.wallets.edit', $wallet->id) }}" class="btn btn-sm btn-warning" title="Edit Wallet">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('admin.wallets.transactions', $wallet->id) }}" class="btn btn-sm btn-primary" title="View Transactions">
                                            <i class="fas fa-exchange-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No wallets found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-between">
                <div>
                    Showing {{ $wallets->firstItem() }} to {{ $wallets->lastItem() }} of {{ $wallets->total() }} entries
                </div>
                <div>
                    {{ $wallets->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#walletsTable').DataTable({
        responsive: true,
        order: [[4, 'desc']],
        pageLength: 25
    });
});
</script>
@endsection