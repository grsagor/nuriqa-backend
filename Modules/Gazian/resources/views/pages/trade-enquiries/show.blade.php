<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
        <div class="modal-header text-white" style="background-color:#0c4a6e;">
            <h5 class="modal-title">Trade enquiry details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>Name:</strong>
                    <p>{{ $enquiry->name }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Email:</strong>
                    <p><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Interest:</strong>
                    <p>{{ $enquiry->interest }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Business type:</strong>
                    <p>{{ $enquiry->business_type }}</p>
                </div>
                <div class="col-md-12 mb-3">
                    <strong>Explore:</strong>
                    <p>{{ is_array($enquiry->explore) ? implode(', ', $enquiry->explore) : $enquiry->explore }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Country:</strong>
                    <p>{{ $enquiry->country }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>City:</strong>
                    <p>{{ $enquiry->city }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Status:</strong>
                    <p>
                        <span class="badge bg-{{ $enquiry->is_read ? 'success' : 'warning' }}">
                            {{ $enquiry->is_read ? 'Read' : 'Unread' }}
                        </span>
                    </p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Submitted:</strong>
                    <p>{{ $enquiry->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
