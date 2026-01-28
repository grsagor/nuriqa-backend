@extends('backend.layout.master')

@section('title', 'Wallet Details - ' . $wallet->user->name ?? 'Unknown')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Wallet Details</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard.index') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.wallets.index') }}">Wallets</a></li>
        <li class="breadcrumb-item active">{{ $wallet->user->name ?? 'Unknown' }}</li>
    </ol>

    <!-- User Info Card -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i>
                    User Information
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> {{ $wallet->user->name ?? 'N/A' }}</p>
                    <p><strong>Email:</strong> {{ $wallet->user->email ?? 'N/A' }}</p>
                    <p><strong>Member Since:</strong> {{ $wallet->user->created_at->format('M j, Y') }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-wallet me-1"></i>
                    Wallet Balance
                </div>
                <div class="card-body">
                    <p><strong>Available Balance:</strong> <span class="text-success fs-5">${{ number_format($wallet->available_balance, 2) }}</span></p>
                    <p><strong>Pending Balance:</strong> <span class="text-warning fs-5">${{ number_format($wallet->pending_balance, 2) }}</span></p>
                    <p><strong>Total Earnings:</strong> <span class="text-primary fs-5">${{ number_format($wallet->total_earnings, 2) }}</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tools me-1"></i>
                    Quick Actions
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.wallets.edit', $wallet->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-1"></i> Edit Balance
                    </a>
                    <a href="{{ route('admin.wallets.transactions', $wallet->id) }}" class="btn btn-info">
                        <i class="fas fa-exchange-alt me-1"></i> View Transactions
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Withdrawals -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-money-bill-wave me-1"></i>
            Recent Withdrawals
            <a href="{{ route('admin.withdrawals.index', ['user_id' => $wallet->user_id]) }}" class="btn btn-sm btn-primary float-end">View All</a>
        </div>
        <div class="card-body">
            @if($withdrawals->count() > 0)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Requested</th>
                                <th>Processed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($withdrawals as $withdrawal)
                                <tr>
                                    <td class="fw-bold">${{ number_format($withdrawal->amount, 2) }}</td>
                                    <td>
                                        @switch($withdrawal->status)
                                            @case('pending')
                                                <span class="badge bg-warning">Pending</span>
                                                @break
                                            @case('approved')
                                                <span class="badge bg-info">Approved</span>
                                                @break
                                            @case('completed')
                                                <span class="badge bg-success">Completed</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-secondary">Cancelled</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>{{ $withdrawal->paymentMethod->type ?? 'N/A' }}</td>
                                    <td>{{ $withdrawal->created_at->format('M j, Y g:i A') }}</td>
                                    <td>{{ $withdrawal->processed_at?->format('M j, Y g:i A') ?? '-' }}</td>
                                    <td>
                                        <a href="{{ route('admin.withdrawals.show', $withdrawal->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between">
                    <div>
                        Showing {{ $withdrawals->firstItem() }} to {{ $withdrawals->lastItem() }} of {{ $withdrawals->total() }} withdrawals
                    </div>
                    <div>
                        {{ $withdrawals->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No withdrawals found for this user</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection