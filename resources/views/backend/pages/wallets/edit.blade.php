@extends('backend.layout.app')

@section('title', 'Edit Wallet - ' . $wallet->user->name ?? 'Unknown')

@section('content')
<div class="page-shell">
    <!-- Breadcrumb -->
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.wallets.index') }}">Wallets</a>
        <span>/</span>
        <a href="{{ route('admin.wallets.show', $wallet->id) }}">{{ $wallet->user->name ?? 'Unknown' }}</a>
        <span>/</span>
        <span>Edit</span>
    </nav>

    <!-- Header -->
    <div class="page-top">
        <div>
            <h1 class="page-heading">Edit Wallet</h1>
            <p class="page-subtitle">Adjust wallet balance</p>
        </div>
    </div>

    <!-- Content -->
    <div class="surface">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Edit Wallet Balance</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.wallets.update', $wallet->id) }}" method="POST">
                            @csrf
                            
                            <!-- User Info -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">User</label>
                                    <input type="text" class="form-control" value="{{ $wallet->user->name ?? 'N/A' }}" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control" value="{{ $wallet->user->email ?? 'N/A' }}" readonly>
                                </div>
                            </div>

                            <!-- Current Balances -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Current Available Balance</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" value="{{ number_format($wallet->available_balance, 2) }}" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Current Pending Balance</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" class="form-control" value="{{ number_format($wallet->pending_balance, 2) }}" readonly>
                                    </div>
                                </div>
                            </div>

                            <!-- New Balances -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="available_balance" class="form-label">New Available Balance *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" 
                                               id="available_balance" 
                                               name="available_balance" 
                                               class="form-control @error('available_balance') is-invalid @enderror" 
                                               value="{{ old('available_balance', $wallet->available_balance) }}" 
                                               step="0.01" 
                                               min="0" 
                                               required>
                                    </div>
                                    @error('available_balance')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="pending_balance" class="form-label">New Pending Balance *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" 
                                               id="pending_balance" 
                                               name="pending_balance" 
                                               class="form-control @error('pending_balance') is-invalid @enderror" 
                                               value="{{ old('pending_balance', $wallet->pending_balance) }}" 
                                               step="0.01" 
                                               min="0" 
                                               required>
                                    </div>
                                    @error('pending_balance')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Balance Difference -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="form-label">Available Balance Change</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" id="available_change" class="form-control" readonly>
                                        <span class="input-group-text" id="available_change_sign"></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pending Balance Change</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="text" id="pending_change" class="form-control" readonly>
                                        <span class="input-group-text" id="pending_change_sign"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div class="mb-4">
                                <label for="notes" class="form-label">Admin Notes</label>
                                <textarea id="notes" 
                                          name="notes" 
                                          class="form-control @error('notes') is-invalid @enderror" 
                                          rows="4" 
                                          placeholder="Enter reason for balance adjustment...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Warning Message -->
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> Modifying wallet balances will create an adjustment log. This action should only be performed for legitimate reasons and will be recorded for audit purposes.
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('admin.wallets.show', $wallet->id) }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Wallet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Quick Info Sidebar -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Info</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Wallet ID:</strong> #{{ $wallet->id }}</p>
                        <p><strong>User ID:</strong> #{{ $wallet->user_id }}</p>
                        <p><strong>Created:</strong> {{ $wallet->created_at->format('M j, Y g:i A') }}</p>
                        <p><strong>Last Updated:</strong> {{ $wallet->updated_at->format('M j, Y g:i A') }}</p>
                    </div>
                </div>

                @if($wallet->adjustments && $wallet->adjustments->count() > 0)
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Adjustments</h5>
                        </div>
                        <div class="card-body">
                            @foreach($wallet->adjustments->take(5) as $adjustment)
                                <div class="border-bottom pb-2 mb-2">
                                    <small class="text-muted">{{ $adjustment->created_at->format('M j, Y g:i A') }}</small><br>
                                    <strong>{{ $adjustment->description }}</strong><br>
                                    <span class="badge bg-info">Amount: ${{ number_format($adjustment->amount, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const availableInput = document.getElementById('available_balance');
    const pendingInput = document.getElementById('pending_balance');
    const availableChange = document.getElementById('available_change');
    const pendingChange = document.getElementById('pending_change');
    const availableSign = document.getElementById('available_change_sign');
    const pendingSign = document.getElementById('pending_change_sign');
    
    const originalAvailable = parseFloat({{ $wallet->available_balance }});
    const originalPending = parseFloat({{ $wallet->pending_balance }});

    function calculateChanges() {
        const newAvailable = parseFloat(availableInput.value) || 0;
        const newPending = parseFloat(pendingInput.value) || 0;
        
        const availableDiff = newAvailable - originalAvailable;
        const pendingDiff = newPending - originalPending;
        
        // Update available balance change
        availableChange.value = Math.abs(availableDiff).toFixed(2);
        if (availableDiff > 0) {
            availableSign.textContent = '+';
            availableSign.className = 'input-group-text text-success';
        } else if (availableDiff < 0) {
            availableSign.textContent = '-';
            availableSign.className = 'input-group-text text-danger';
        } else {
            availableSign.textContent = '';
            availableSign.className = 'input-group-text';
        }
        
        // Update pending balance change
        pendingChange.value = Math.abs(pendingDiff).toFixed(2);
        if (pendingDiff > 0) {
            pendingSign.textContent = '+';
            pendingSign.className = 'input-group-text text-success';
        } else if (pendingDiff < 0) {
            pendingSign.textContent = '-';
            pendingSign.className = 'input-group-text text-danger';
        } else {
            pendingSign.textContent = '';
            pendingSign.className = 'input-group-text';
        }
    }

    availableInput.addEventListener('input', calculateChanges);
    pendingInput.addEventListener('input', calculateChanges);
    
    // Initial calculation
    calculateChanges();
});
</script>
@endsection
