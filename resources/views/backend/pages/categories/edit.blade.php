<form class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    @csrf
    @method('PUT')
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h1 class="modal-title fs-5" id="crudModalLabel">Edit Category</h1>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" value="{{ $category->id }}">

            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $category->name ?? '' }}" placeholder="Enter category name" required>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label fw-semibold">Category Image</label>
                <input type="file" name="image" id="image" class="form-control image-preview-input"
                       accept="image/*" data-preview-container="#imagePreviewContainer">
                <div id="imagePreviewContainer" class="mt-2 image-preview-container">
                    @if(isset($category->image) && !empty($category->image))
                        <div class="current-image-preview position-relative d-inline-block">
                            <img src="{{ $category->image_url }}" alt="Current Image" class="img-thumbnail">
                            <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" id="removeCurrentImage">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="form-text mt-1">Current image</div>
                        </div>
                        <input type="hidden" name="remove_image" id="remove_image" value="0">
                    @endif
                </div>
                <div class="form-text">Allowed formats: jpeg, png, jpg, gif (Max size: 2MB)</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary form_submit_btn"
                data-url="{{ route('admin.categories.update', $category->id) }}">Update Category</button>
        </div>
    </div>
</form>
