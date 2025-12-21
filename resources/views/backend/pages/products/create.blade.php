<form class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
    @csrf
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h1 class="modal-title fs-5" id="crudModalLabel">Create Product</h1>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <!-- Basic Information -->
            <div class="border-bottom pb-3 mb-4">
                <h6 class="text-muted mb-3">Basic Information</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="Enter product title" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="owner_id" class="form-label fw-semibold">Owner <span class="text-danger">*</span></label>
                        <select name="owner_id" id="owner_id" class="form-select" required>
                            <option value="">Select Owner</option>
                            @if(isset($users))
                                @foreach($users as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter product description"></textarea>
                </div>
            </div>

            <!-- Product Details -->
            <div class="border-bottom pb-3 mb-4">
                <h6 class="text-muted mb-3">Product Details</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="brand" class="form-label fw-semibold">Brand</label>
                        <input type="text" name="brand" id="brand" class="form-control" placeholder="Enter brand name">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="material" class="form-label fw-semibold">Material</label>
                        <select name="material" id="material" class="form-select">
                            <option value="">Select Material</option>
                            @if(isset($materials))
                                @foreach($materials as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="color" class="form-label fw-semibold">Color</label>
                        <input type="text" name="color" id="color" class="form-control" placeholder="Enter color">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="size_id" class="form-label fw-semibold">Size</label>
                        <select name="size_id" id="size_id" class="form-select">
                            <option value="">Select Size</option>
                            @if(isset($sizes))
                                @foreach($sizes as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="category_id" class="form-label fw-semibold">Category</label>
                        <select name="category_id" id="category_id" class="form-select">
                            <option value="">Select Category</option>
                            @if(isset($categories))
                                @foreach($categories as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="condition_id" class="form-label fw-semibold">Condition</label>
                        <select name="condition_id" id="condition_id" class="form-select">
                            <option value="">Select Condition</option>
                            @if(isset($conditions))
                                @foreach($conditions as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="price" class="form-label fw-semibold">Price</label>
                        <input type="number" name="price" id="price" class="form-control" placeholder="0.00" step="0.01" min="0">
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="border-bottom pb-3 mb-4">
                <h6 class="text-muted mb-3">Additional Information</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="location" class="form-label fw-semibold">Location</label>
                        <input type="text" name="location" id="location" class="form-control" placeholder="Enter location">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="upload_date" class="form-label fw-semibold">Upload Date</label>
                        <input type="date" name="upload_date" id="upload_date" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Options</label>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="is_washed" id="is_washed">
                            <label class="form-check-label" for="is_washed">
                                Is Washed
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                            <label class="form-check-label" for="is_featured">
                                Featured Product
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div>
                <h6 class="text-muted mb-3">Images</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="thumbnail" class="form-label fw-semibold">Thumbnail Image</label>
                        <input type="file" name="thumbnail" id="thumbnail" class="form-control image-preview-input" 
                               accept="image/*" data-preview-container="#thumbnailPreviewContainer">
                        <div id="thumbnailPreviewContainer" class="mt-2 image-preview-container"></div>
                        <div class="form-text">Main product image (Recommended: 400x300px)</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="images" class="form-label fw-semibold">Additional Images</label>
                        <input type="file" name="images[]" id="images" class="form-control" 
                               accept="image/*" multiple>
                        <div id="imagesPreviewContainer" class="mt-2 image-preview-container"></div>
                        <div class="form-text">You can select multiple images</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary form_submit_btn"
                data-url="{{ route('admin.products.store') }}">Save Product</button>
        </div>
    </div>
</form>