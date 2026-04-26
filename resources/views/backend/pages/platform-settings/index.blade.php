@extends('backend.layout.app')

@section('content')

<div class="page-shell">

    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <span>Buyer protection fee</span>
    </nav>

    <div class="page-top">
        <div>
            <h1 class="page-heading">Buyer protection fee</h1>
            <p class="page-subtitle">Set the percentage added to seller-listed prices. Buyers see this on product cards, cart, and checkout.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif

    <div class="surface p-4" style="max-width: 480px;">
        <form method="post" action="{{ route('admin.platform-settings.update') }}">
            @csrf
            <div class="mb-3">
                <label for="fee_percentage" class="form-label">Buyer protection fee (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="form-control @error('fee_percentage') is-invalid @enderror"
                    id="fee_percentage"
                    name="fee_percentage"
                    value="{{ old('fee_percentage', $settings->fee_percentage) }}"
                    required
                />
                @error('fee_percentage')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>

@endsection
