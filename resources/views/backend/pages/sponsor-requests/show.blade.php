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
                        <span class="badge bg-{{ $sponsorRequest->status === 'approved' ? 'success' : ($sponsorRequest->status === 'rejected' ? 'danger' : ($sponsorRequest->status === 'returned' ? 'info' : 'warning')) }}">
                            {{ ucfirst($sponsorRequest->status) }}
                        </span>
                    </p>
                </div>
                @if($sponsorRequest->moderation_message)
                    <div class="col-md-12 mb-3">
                        <strong>Moderation message:</strong>
                        <p style="white-space: pre-wrap;">{{ $sponsorRequest->moderation_message }}</p>
                    </div>
                @endif
                <div class="col-md-12 mb-3">
                    <strong>Created At:</strong>
                    <p>{{ $sponsorRequest->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>

            @if($sponsorRequest->status === 'pending')
                <hr>
                <div class="row g-3">
                    <div class="col-md-4">
                        <form method="POST" action="{{ route('admin.sponsor-requests.approve', $sponsorRequest->id) }}" class="sponsor-moderation-form">
                            @csrf
                            <label class="form-label">Approve message (optional)</label>
                            <textarea name="message" class="form-control mb-2" rows="2"></textarea>
                            <button type="submit" class="btn btn-success w-100">Approve</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="POST" action="{{ route('admin.sponsor-requests.return', $sponsorRequest->id) }}" class="sponsor-moderation-form">
                            @csrf
                            <label class="form-label">Return for correction *</label>
                            <textarea name="message" class="form-control mb-2" rows="2" required></textarea>
                            <button type="submit" class="btn btn-info w-100 text-white">Return</button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="POST" action="{{ route('admin.sponsor-requests.reject', $sponsorRequest->id) }}" class="sponsor-moderation-form">
                            @csrf
                            <label class="form-label">Reject message *</label>
                            <textarea name="message" class="form-control mb-2" rows="2" required></textarea>
                            <button type="submit" class="btn btn-danger w-100">Reject</button>
                        </form>
                    </div>
                </div>
                <script>
                    (function () {
                        document.querySelectorAll('.sponsor-moderation-form').forEach(function (form) {
                            form.addEventListener('submit', function (e) {
                                e.preventDefault();
                                var fd = new FormData(form);
                                fetch(form.action, {
                                    method: 'POST',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json'
                                    },
                                    body: fd
                                }).then(function (r) { return r.json(); }).then(function (data) {
                                    if (data.success) {
                                        if (typeof toastr !== 'undefined') toastr.success(data.message);
                                        else alert(data.message);
                                        if (typeof $('#datatable').DataTable === 'function') {
                                            $('#datatable').DataTable().ajax.reload(null, false);
                                        }
                                        var modal = bootstrap.Modal.getInstance(document.querySelector('#crudModal'));
                                        if (modal) modal.hide();
                                    } else {
                                        alert(data.message || 'Action failed');
                                    }
                                }).catch(function () {
                                    alert('Action failed');
                                });
                            });
                        });
                    })();
                </script>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
