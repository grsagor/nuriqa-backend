<form class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    @csrf
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h1 class="modal-title fs-5" id="crudModalLabel">Create Language</h1>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Language Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter language name" required>
            </div>
            
            <div class="mb-3">
                <label for="code" class="form-label fw-semibold">Language Code <span class="text-danger">*</span></label>
                <input type="text" name="code" id="code" class="form-control" placeholder="e.g., en, es, fr" required>
                <div class="form-text">ISO 639-1 language code (2 letters)</div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                    <label class="form-check-label fw-semibold" for="is_active">
                        Active
                    </label>
                </div>
                <div class="form-text">Inactive languages won't be available for selection</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary form_submit_btn"
                data-url="{{ route('admin.languages.store') }}">Save Language</button>
        </div>
    </div>
</form>