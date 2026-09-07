@extends('backend.layout.app')

@section('title', 'Support case ' . $case->case_number)

@section('content')
<div class="page-shell">
    <nav class="breadcrumb-modern">
        <a href="{{ route('admin.dashboard.index') }}">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.support-cases.index') }}">Support cases</a>
        <span>/</span>
        <span>{{ $case->case_number }}</span>
    </nav>

    <div class="page-top">
        <div>
            <h1 class="page-heading">{{ $case->case_number }}</h1>
            <p class="page-subtitle">{{ $case->subject }}</p>
        </div>
        <div>
            <span class="badge bg-secondary text-uppercase">{{ $case->type }}</span>
            <span class="badge bg-info">{{ str_replace('_', ' ', $case->status) }}</span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-7">
            <div class="surface mb-4">
                <h5 class="mb-3">Case details</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Customer</strong>
                        <p class="mb-0">{{ $case->user?->name ?? '—' }}</p>
                        <p class="small text-muted">{{ $case->user?->email }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Owner</strong>
                        <p>{{ $case->owner?->name ?? 'Unassigned' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Product</strong>
                        <p>{{ $case->product?->title ?? '—' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Transaction</strong>
                        <p>{{ $case->transaction_id ? '#'.$case->transaction_id : '—' }}</p>
                    </div>
                    <div class="col-12 mb-3">
                        <strong>Description</strong>
                        <p style="white-space: pre-wrap;">{{ $case->description }}</p>
                    </div>
                    @if(!empty($case->evidence))
                        <div class="col-12 mb-3">
                            <strong>Evidence</strong>
                            <ul class="mb-0">
                                @foreach($case->evidence as $item)
                                    <li><a href="{{ $item }}" target="_blank" rel="noopener">{{ $item }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if($case->decision)
                        <div class="col-12 mb-3">
                            <strong>Decision</strong>
                            <p style="white-space: pre-wrap;">{{ $case->decision }}</p>
                        </div>
                    @endif
                    @if($case->admin_notes)
                        <div class="col-12 mb-3">
                            <strong>Admin notes</strong>
                            <p style="white-space: pre-wrap;">{{ $case->admin_notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="surface mb-4">
                <h5 class="mb-3">Messages</h5>
                @forelse($case->messages as $message)
                    <div class="border rounded p-3 mb-3 {{ $message->is_internal ? 'bg-light' : '' }}">
                        <div class="d-flex justify-content-between mb-2">
                            <strong>{{ $message->user?->name ?? 'System' }}</strong>
                            <span class="small text-muted">
                                {{ $message->created_at?->format('d M Y H:i') }}
                                @if($message->is_internal)
                                    · <span class="badge bg-secondary">Internal</span>
                                @endif
                            </span>
                        </div>
                        <p class="mb-0" style="white-space: pre-wrap;">{{ $message->body }}</p>
                    </div>
                @empty
                    <p class="text-muted mb-0">No messages yet.</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-5">
            @if($case->status === \App\Models\SupportCase::STATUS_SUBMITTED)
                <div class="surface mb-4">
                    <h5 class="mb-3">Acknowledge</h5>
                    <form method="POST" action="{{ route('admin.support-cases.acknowledge', $case->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Acknowledge case</button>
                    </form>
                </div>
            @endif

            <div class="surface mb-4">
                <h5 class="mb-3">Assign owner</h5>
                <form method="POST" action="{{ route('admin.support-cases.assign', $case->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="owner_id">Admin</label>
                        <select name="owner_id" id="owner_id" class="form-select" required>
                            @foreach($admins as $admin)
                                <option value="{{ $admin->id }}" @selected(($case->owner_id ?? auth()->id()) === $admin->id)>
                                    {{ $admin->name }} ({{ $admin->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-primary">Assign</button>
                </form>
            </div>

            <div class="surface mb-4">
                <h5 class="mb-3">Respond</h5>
                <form method="POST" action="{{ route('admin.support-cases.messages', $case->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="body">Message</label>
                        <textarea name="body" id="body" class="form-control" rows="4" required>{{ old('body') }}</textarea>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" value="1" name="is_internal" id="is_internal">
                        <label class="form-check-label" for="is_internal">Internal note (not visible to customer)</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" value="1" name="awaiting_customer" id="awaiting_customer">
                        <label class="form-check-label" for="awaiting_customer">Mark awaiting customer</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Send</button>
                </form>
            </div>

            <div class="surface mb-4">
                <h5 class="mb-3">Decide / close</h5>
                <form method="POST" action="{{ route('admin.support-cases.decide', $case->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="decision">Decision</label>
                        <textarea name="decision" id="decision" class="form-control" rows="3" required>{{ old('decision', $case->decision) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="resolved" @selected(old('status', $case->status) === 'resolved')>Resolved</option>
                            <option value="closed" @selected(old('status') === 'closed')>Closed</option>
                            <option value="awaiting_customer" @selected(old('status') === 'awaiting_customer')>Awaiting customer</option>
                            <option value="in_progress" @selected(old('status') === 'in_progress')>In progress</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="financial_outcome">Financial outcome (£)</label>
                        <input type="number" step="0.01" name="financial_outcome" id="financial_outcome" class="form-control" value="{{ old('financial_outcome', $case->financial_outcome) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="admin_notes">Admin notes</label>
                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3">{{ old('admin_notes', $case->admin_notes) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="message">Customer-facing message (optional)</label>
                        <textarea name="message" id="message" class="form-control" rows="2">{{ old('message') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-success">Save decision</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
