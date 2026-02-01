@extends('backend.layout.app')

@section('title', 'Wallet Details - ' . $wallet->user->name ?? 'Unknown')

@section('content')
<div class="page-shell">
    <!-- Breadcrumb -->
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.wallets.index') }}">Wallets</a>
        <span>/</span>
        <span>{{ $wallet->user->name ?? 'Unknown' }}</span>
    </nav>

    <!-- Header -->
    <div class="page-top">
        <div>
            <h1 class="page-heading">Wallet Details</h1>
            <p class="page-subtitle">Manage wallet balance and transactions</p>
        </div>
    </div>

    <!-- Content -->
    <div class="surface">
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">User Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> {{ $wallet->user->name ?? 'N/A' }}</p>
                        <p><strong>Email:</strong> {{ $wallet->user->email ?? 'N/A' }}</p>
                        <p><strong>Member Since:</strong> {{ $wallet->user->created_at->format('M j, Y') }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Wallet Balance</h5>
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
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.wallets.edit', $wallet->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Edit Balance
                </a>
                <a href="{{ route('admin.wallets.transactions', $wallet->id) }}" class="btn btn-info">
                    <i class="fas fa-exchange-alt me-1"></i> View Transactions
                </a>
            </div>
        </div>

        <!-- Recent Withdrawals -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Withdrawals</h5>
                <a href="{{ route('admin.withdrawals.index', ['user_id' => $wallet->user_id]) }}" class="btn btn-sm btn-primary">View All</a>
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
                                            <span class="badge bg-{{ $withdrawal->status === 'completed' ? 'success' : ($withdrawal->status === 'rejected' ? 'danger' : ($withdrawal->status === 'approved' ? 'info' : 'warning')) }}">
                                                {{ ucfirst($withdrawal->status) }}
                                            </span>
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
                    
                    <div class="d-flex justify-content-between mt-3">
                        <div>
                            Showing {{ $withdrawals->firstItem() }} to {{ $withdrawals->lastItem() }} of {{ $withdrawals->total() }} withdrawals
                        </div>
                        <div>
                            {{ $withdrawals->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-4">
                        <p class="text-muted">No withdrawals found for this user</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
