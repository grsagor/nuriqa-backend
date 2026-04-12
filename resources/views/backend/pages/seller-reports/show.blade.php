<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h5 class="modal-title">Seller report #{{ $report->id }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>Reporter</strong>
                    <p>{{ $report->reporter?->name ?? '—' }}</p>
                    <p class="small text-muted">{{ $report->reporter?->email }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Reported seller</strong>
                    <p>{{ $report->reportedUser?->name ?? '—' }}</p>
                    <p class="small text-muted">{{ $report->reportedUser?->email }}</p>
                </div>
                <div class="col-md-12 mb-3">
                    <strong>Reason</strong>
                    <p>{{ $report->reason }}</p>
                </div>
                @if($report->details)
                    <div class="col-md-12 mb-3">
                        <strong>Details</strong>
                        <p class="mb-0" style="white-space: pre-wrap;">{{ $report->details }}</p>
                    </div>
                @endif
                <div class="col-md-6 mb-3">
                    <strong>Submitted</strong>
                    <p>{{ $report->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>

            <hr>

            <form id="sellerReportUpdateForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="report_status">Status</label>
                    <select name="status" id="report_status" class="form-select" required>
                        <option value="pending" @selected($report->status === 'pending')>Pending</option>
                        <option value="reviewed" @selected($report->status === 'reviewed')>Reviewed</option>
                        <option value="resolved" @selected($report->status === 'resolved')>Resolved</option>
                        <option value="dismissed" @selected($report->status === 'dismissed')>Dismissed</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="admin_notes">Admin notes</label>
                    <textarea name="admin_notes" id="admin_notes" class="form-control" rows="4" placeholder="Internal notes (optional)">{{ old('admin_notes', $report->admin_notes) }}</textarea>
                </div>
                <button type="button" class="btn btn-primary form_submit_btn" data-url="{{ route('admin.seller-reports.update-status', $report->id) }}">
                    Save changes
                </button>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
