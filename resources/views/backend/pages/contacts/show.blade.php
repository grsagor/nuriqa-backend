<div class="modal-header">
    <h5 class="modal-title">Contact Message Details</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <div class="row">
        <div class="col-md-6 mb-3">
            <strong>First Name:</strong>
            <p>{{ $contact->first_name }}</p>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Last Name:</strong>
            <p>{{ $contact->last_name }}</p>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Email:</strong>
            <p><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></p>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Phone:</strong>
            <p><a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a></p>
        </div>
        <div class="col-md-12 mb-3">
            <strong>Subject:</strong>
            <p>{{ $contact->subject }}</p>
        </div>
        @if($contact->message)
        <div class="col-md-12 mb-3">
            <strong>Message:</strong>
            <p>{{ $contact->message }}</p>
        </div>
        @endif
        <div class="col-md-6 mb-3">
            <strong>Status:</strong>
            <p>
                <span class="badge bg-{{ $contact->is_read ? 'success' : 'warning' }}">
                    {{ $contact->is_read ? 'Read' : 'Unread' }}
                </span>
            </p>
        </div>
        <div class="col-md-6 mb-3">
            <strong>Created At:</strong>
            <p>{{ $contact->created_at->format('d M Y H:i') }}</p>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
