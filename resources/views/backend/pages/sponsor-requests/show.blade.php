<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h5 class="modal-title">Sponsor Request Details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>User:</strong>
                    <p>{{ $sponsorRequest->user->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Product:</strong>
                    <p>{{ $sponsorRequest->product->title ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>First Name:</strong>
                    <p>{{ $sponsorRequest->first_name }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Last Name:</strong>
                    <p>{{ $sponsorRequest->last_name }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Email:</strong>
                    <p>{{ $sponsorRequest->email }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Phone:</strong>
                    <p>{{ $sponsorRequest->phone }}</p>
                </div>
                <div class="col-md-12 mb-3">
                    <strong>Address:</strong>
                    <p>{{ $sponsorRequest->address }}{{ $sponsorRequest->apartment ? ', ' . $sponsorRequest->apartment : '' }},
                        {{ $sponsorRequest->city }}, {{ $sponsorRequest->postal_code }}
                    </p>
                </div>
                <div class="col-md-12 mb-3">
                    <strong>Request Reason:</strong>
                    <p>{{ $sponsorRequest->request_reason }}</p>
                </div>
                @if($sponsorRequest->additional_info)
                    <div class="col-md-12 mb-3">
                        <strong>Additional Info:</strong>
                        <p>{{ $sponsorRequest->additional_info }}</p>
                    </div>
                @endif
                <div class="col-md-6 mb-3">
                    <strong>Keep Updated:</strong>
                    <p>{{ $sponsorRequest->keep_updated ? 'Yes' : 'No' }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Status:</strong>
                    <p>
                        <span
                            class="badge bg-{{ $sponsorRequest->status === 'approved' ? 'success' : ($sponsorRequest->status === 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($sponsorRequest->status) }}
                        </span>
                    </p>
                </div>
                <div class="col-md-12 mb-3">
                    <strong>Created At:</strong>
                    <p>{{ $sponsorRequest->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>