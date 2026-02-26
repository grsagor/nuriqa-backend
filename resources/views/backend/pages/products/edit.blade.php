<form class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
    @csrf
    @method('PUT')
    <input type="hidden" name="id" value="{{ $product->id }}">
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h1 class="modal-title fs-5" id="crudModalLabel">Edit Product</h1>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <!-- Basic Information -->
            <div class="border-bottom pb-3 mb-4">
                <h6 class="text-muted mb-3">Basic Information</h6>
                <div class="mb-3">
                    <label for="title" class="form-label fw-semibold">Product Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="title" class="form-control" value="{{ $product->title ?? '' }}" placeholder="Enter product title" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Product Description <span class="text-danger">*</span></label>
                    <textarea name="description" id="description" class="form-control" rows="4" placeholder="Enter product description" required>{{ $product->description ?? '' }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="category_id" class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="category_id" id="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        @if(isset($categories))
                            @foreach($categories as $id => $name)
                                <option value="{{ $id }}" {{ (isset($product->category_id) && $product->category_id == $id) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <!-- Details Information -->
            <div class="border-bottom pb-3 mb-4">
                <h6 class="text-muted mb-3">Details Information</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="brand" class="form-label fw-semibold">Brand <span class="text-danger">*</span></label>
                        <input type="text" name="brand" id="brand" class="form-control" value="{{ $product->brand ?? '' }}" placeholder="Enter brand name" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="material" class="form-label fw-semibold">Material <span class="text-danger">*</span></label>
                        <select name="material" id="material" class="form-select" required>
                            <option value="">Select Material</option>
                            @if(isset($materials))
                                @foreach($materials as $key => $value)
                                    <option value="{{ $key }}" {{ (isset($product->material) && $product->material == $key) ? 'selected' : '' }}>{{ $value }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="color" class="form-label fw-semibold">Color <span class="text-danger">*</span></label>
                        <input type="text" name="color" id="color" class="form-control" value="{{ $product->color ?? '' }}" placeholder="Enter color" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="size_id" class="form-label fw-semibold">Size <span class="text-danger">*</span></label>
                        <select name="size_id" id="size_id" class="form-select" required>
                            <option value="">Select Size</option>
                            @if(isset($sizes))
                                @foreach($sizes as $id => $name)
                                    <option value="{{ $id }}" {{ (isset($product->size_id) && $product->size_id == $id) ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="condition" class="form-label fw-semibold">Condition <span class="text-danger">*</span></label>
                        <select name="condition" id="condition" class="form-select" required>
                            <option value="">Select Condition</option>
                            <option value="new" {{ (isset($product->condition) && $product->condition == 'new') ? 'selected' : '' }}>New</option>
                            <option value="used" {{ (isset($product->condition) && $product->condition == 'used') ? 'selected' : '' }}>Used</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Item Condition Information (for used products) -->
            <div class="border-bottom pb-3 mb-4" id="washedSection" style="display: {{ (isset($product->condition) && $product->condition == 'used') ? 'block' : 'none' }};">
                <h6 class="text-muted mb-3">Item Condition Information</h6>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Item Washed</label>
                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_washed" id="washed_yes" value="1" {{ (isset($product->is_washed) && $product->is_washed) ? 'checked' : '' }}>
                            <label class="form-check-label" for="washed_yes">Washed</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_washed" id="washed_no" value="0" {{ (!isset($product->is_washed) || !$product->is_washed) ? 'checked' : '' }}>
                            <label class="form-check-label" for="washed_no">Not Washed</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Information -->
            <div class="border-bottom pb-3 mb-4">
                <h6 class="text-muted mb-3">Pricing Information</h6>
                <div class="mb-3">
                    <label for="price" class="form-label fw-semibold">Price <span class="text-danger">*</span></label>
                    <input type="number" name="price" id="price" class="form-control" value="{{ $product->price ?? '' }}" placeholder="0.00" step="0.01" min="0" required>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <label class="form-label fw-semibold mb-0">Discount</label>
                            <p class="text-muted small mb-0">Enable to add a discount to the current price</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="discount_enabled" id="discount_enabled" value="1" {{ (isset($product->discount_enabled) && $product->discount_enabled) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                <div id="discountFields" style="display: {{ (isset($product->discount_enabled) && $product->discount_enabled) ? 'block' : 'none' }};">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="discount_type" class="form-label fw-semibold">Discount Type</label>
                            <select name="discount_type" id="discount_type" class="form-select">
                                <option value="percentage" {{ (isset($product->discount_type) && $product->discount_type == 'percentage') ? 'selected' : '' }}>Percentage</option>
                                <option value="flat" {{ (isset($product->discount_type) && $product->discount_type == 'flat') ? 'selected' : '' }}>Flat</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="discount" class="form-label fw-semibold">Discount Value</label>
                            <input type="number" name="discount" id="discount" class="form-control" value="{{ $product->discount ?? 0 }}" placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Donation Information -->
            <div class="border-bottom pb-3 mb-4">
                <h6 class="text-muted mb-3">Donation Information</h6>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <label class="form-label fw-semibold mb-0">Platform Donation</label>
                            <p class="text-muted small mb-0">Enable to contribute a % of this item's sale price to keep the platform running</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="platform_donation" id="platform_donation" value="1" {{ (isset($product->platform_donation) && $product->platform_donation) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                <div id="donationFields" style="display: {{ (isset($product->platform_donation) && $product->platform_donation) ? 'block' : 'none' }};">
                    <div class="mb-3">
                        <label for="donation_percentage" class="form-label fw-semibold">Donation Percentage</label>
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary donation-preset {{ (isset($product->donation_percentage) && $product->donation_percentage == 10) ? 'active' : '' }}" data-value="10">10%</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary donation-preset {{ (isset($product->donation_percentage) && $product->donation_percentage == 20) ? 'active' : '' }}" data-value="20">20%</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary donation-preset {{ (isset($product->donation_percentage) && $product->donation_percentage == 50) ? 'active' : '' }}" data-value="50">50%</button>
                        </div>
                        <input type="number" name="donation_percentage" id="donation_percentage" class="form-control" value="{{ $product->donation_percentage ?? 0 }}" placeholder="Enter donation percentage" min="0" max="100">
                    </div>
                </div>
            </div>

            <!-- Listing -->
            <div class="border-bottom pb-3 mb-4">
                <h6 class="text-muted mb-3">Listing</h6>
                <div class="mb-3">
                    <label for="stock" class="form-label fw-semibold">Stock</label>
                    <input type="number" name="stock" id="stock" class="form-control" placeholder="1" min="0" value="{{ $product->stock ?? 1 }}">
                    <div class="form-text">Available quantity. Product is hidden from the frontend when stock is 0.</div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <label class="form-label fw-semibold mb-0">Active Listing</label>
                            <p class="text-muted small mb-0">When enabled, this product appears in searches and browse listings</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="active_listing" id="active_listing" value="1" {{ (!isset($product->active_listing) || $product->active_listing) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div class="mb-4">
                <h6 class="text-muted mb-3">Images</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="thumbnail" class="form-label fw-semibold">Thumbnail Image</label>
                        <input type="file" name="thumbnail" id="thumbnail" class="form-control image-preview-input" 
                               accept="image/*" data-preview-container="#thumbnailPreviewContainer">
                        <div id="thumbnailPreviewContainer" class="mt-2 image-preview-container">
                            @if(isset($product->thumbnail) && !empty($product->thumbnail))
                                <div class="current-image-preview position-relative d-inline-block">
                                    <img src="{{ $product->thumbnail_url }}" alt="Current Thumbnail" class="img-thumbnail" width="150">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" id="removeCurrentThumbnail">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div class="form-text mt-1">Current thumbnail</div>
                                </div>
                                <input type="hidden" name="remove_thumbnail" id="remove_thumbnail" value="0">
                            @endif
                        </div>
                        <div class="form-text">Main product image (Recommended: 400x300px)</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="images" class="form-label fw-semibold">Additional Images</label>
                        <input type="file" name="images[]" id="images" class="form-control" 
                               accept="image/*" multiple>
                        <div id="imagesPreviewContainer" class="mt-2 image-preview-container">
                            @if(isset($product->images) && $product->images->count() > 0)
                                <div class="current-images-container">
                                    <div class="form-text mb-2">Current images (click to remove)</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($product->images as $image)
                                            <div class="position-relative">
                                                <img src="{{ asset($image->image) }}" alt="Product Image" class="img-thumbnail" width="80" height="80">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 remove-current-image" data-image-id="{{ $image->id }}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <input type="hidden" name="remove_images[]" class="remove-image-input" value="" data-image-id="{{ $image->id }}">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="form-text">You can select multiple images</div>
                    </div>
                </div>
            </div>

            <!-- Featured Product -->
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" {{ (isset($product->is_featured) && $product->is_featured) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_featured">
                        Featured Product
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary form_submit_btn"
                data-url="{{ route('admin.products.update', $product->id) }}">Update Product</button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show/hide washed section based on condition
    const conditionSelect = document.getElementById('condition');
    const washedSection = document.getElementById('washedSection');
    
    if (conditionSelect) {
        conditionSelect.addEventListener('change', function() {
            if (this.value === 'used') {
                washedSection.style.display = 'block';
            } else {
                washedSection.style.display = 'none';
                document.getElementById('washed_no').checked = true;
            }
        });
    }

    // Show/hide discount fields
    const discountEnabled = document.getElementById('discount_enabled');
    const discountFields = document.getElementById('discountFields');
    
    if (discountEnabled) {
        discountEnabled.addEventListener('change', function() {
            discountFields.style.display = this.checked ? 'block' : 'none';
        });
    }

    // Show/hide donation fields
    const platformDonation = document.getElementById('platform_donation');
    const donationFields = document.getElementById('donationFields');
    
    if (platformDonation) {
        platformDonation.addEventListener('change', function() {
            donationFields.style.display = this.checked ? 'block' : 'none';
        });
    }

    // Donation percentage presets
    document.querySelectorAll('.donation-preset').forEach(btn => {
        btn.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            document.getElementById('donation_percentage').value = value;
            document.querySelectorAll('.donation-preset').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>
