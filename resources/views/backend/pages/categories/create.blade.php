<form class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    @csrf
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h1 class="modal-title fs-5" id="crudModalLabel">Create Category</h1>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" placeholder="Enter category name" required>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label fw-semibold">Category Image</label>
                <input type="file" name="image" id="image" class="form-control image-preview-input"
                       accept="image/*" data-preview-container="#imagePreviewContainer">
                <div id="imagePreviewContainer" class="mt-2 image-preview-container"></div>
                <div class="form-text">Allowed formats: jpeg, png, jpg, gif (Max size: 2MB)</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary form_submit_btn"
                data-url="{{ route('admin.categories.store') }}">Save Category</button>
        </div>
    </div>
</form>
