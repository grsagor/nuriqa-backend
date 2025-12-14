<form class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    @csrf
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h1 class="modal-title fs-5" id="crudModalLabel">Create Brand</h1>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Brand Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter brand name" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary form_submit_btn"
                data-url="{{ route('admin.brands.store') }}">Save Brand</button>
        </div>
    </div>
</form>