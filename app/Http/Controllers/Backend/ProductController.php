<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Size;
use App\Services\ImageService;
use App\Services\ProductModerationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class ProductController extends Controller
{
    public function merchandiseIndex()
    {
        return view('backend.pages.products.index', [
            'catalogType' => 'merchandise',
            'pageTitle' => 'Merchandise products',
            'pageSubtitle' => 'Create and manage merchandise catalog listings',
            'breadcrumbLabel' => 'Merchandise products',
            'showApprovalColumn' => false,
        ]);
    }

    public function hajraIndex()
    {
        return view('backend.pages.products.index', [
            'catalogType' => 'hajra',
            'pageTitle' => 'Hajra products',
            'pageSubtitle' => 'Create and manage Hajra catalog listings',
            'breadcrumbLabel' => 'Hajra products',
            'showApprovalColumn' => false,
        ]);
    }

    public function allIndex()
    {
        return view('backend.pages.products.index', [
            'catalogType' => null,
            'pageTitle' => 'All products',
            'pageSubtitle' => 'View and manage every product across seller, merchandise, and Hajra listings',
            'breadcrumbLabel' => 'All products',
            'showApprovalColumn' => true,
        ]);
    }

    public function create(string $catalogType)
    {
        if (! in_array($catalogType, ['merchandise', 'hajra'], true)) {
            abort(404);
        }

        $sizes = Size::pluck('name', 'id');
        $categories = Category::pluck('name', 'id');
        $materials = Product::$materials;

        $html = view('backend.pages.products.create', compact('sizes', 'categories', 'materials', 'catalogType'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:merchandise,hajra',
            'brand' => 'required|string|max:255',
            'material' => 'required|string|in:'.implode(',', array_keys(Product::$materials)),
            'color' => 'required|string|max:255',
            'size_id' => 'required|exists:sizes,id',
            'category_id' => 'required|exists:categories,id',
            'condition' => 'required|in:new,used',
            'price' => [
                Rule::requiredIf(fn () => ! ($request->input('type') === 'hajra' && $request->has('is_free'))),
                'numeric',
                'min:0',
            ],
            'is_free' => 'nullable|boolean',
            'is_washed' => 'nullable|in:0,1',
            'discount_enabled' => 'nullable|boolean',
            'discount_type' => 'nullable|in:percentage,flat',
            'discount' => 'nullable|numeric|min:0',
            'platform_donation' => 'nullable|boolean',
            'donation_percentage' => 'nullable|integer|min:0|max:100',
            'active_listing' => 'nullable|boolean',
            'stock' => 'nullable|integer|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_featured' => 'nullable|boolean',
        ]);

        $data = $request->except(['images', 'thumbnail']);

        // Auto-assign owner_id from current authenticated user
        $data['owner_id'] = auth()->id();

        $data['type'] = $request->input('type');

        $isHajraFree = $request->input('type') === 'hajra' && $request->has('is_free');

        if ($isHajraFree) {
            $data['is_free'] = 1;
            $data['price'] = 0;
            $data['discount_enabled'] = 0;
            $data['discount_type'] = null;
            $data['discount'] = 0;
            $data['platform_donation'] = 0;
            $data['donation_percentage'] = 0;
        } else {
            $data['is_free'] = 0;
        }

        // Handle boolean fields
        $data['is_washed'] = $request->input('is_washed', 0);
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        if (! $isHajraFree) {
            $data['discount_enabled'] = $request->has('discount_enabled') ? 1 : 0;
            $data['platform_donation'] = $request->has('platform_donation') ? 1 : 0;
        }
        $data['active_listing'] = $request->input('active_listing', 1);
        $data['stock'] = (int) $request->input('stock', 1);

        // Handle discount fields (discount column cannot be null)
        if (! $isHajraFree) {
            if (! $data['discount_enabled']) {
                $data['discount_type'] = null;
                $data['discount'] = 0;
            } else {
                $data['discount'] = (float) $request->input('discount', 0);
            }
            $data['discount'] = (float) ($data['discount'] ?? 0);

            // Handle donation percentage (column cannot be null)
            if (! ($data['platform_donation'] ?? 0)) {
                $data['donation_percentage'] = 0;
            } else {
                $data['donation_percentage'] = (int) $request->input('donation_percentage', 0);
            }
            $data['donation_percentage'] = (int) ($data['donation_percentage'] ?? 0);
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = ImageService::upload($request->file('thumbnail'), 'products');
        }

        // Set upload_date if not provided
        if (! isset($data['upload_date'])) {
            $data['upload_date'] = now()->toDateString();
        }

        $data['approval_status'] = Product::APPROVAL_APPROVED;

        $product = Product::create($data);

        // Handle multiple images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = ImageService::upload($image, 'products');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $imagePath,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
        ]);
    }

    public function list(Request $request)
    {
        if (request()->ajax()) {
            $query = Product::with(['owner', 'size', 'category'])
                ->select('id', 'owner_id', 'title', 'type', 'price', 'thumbnail', 'location', 'is_featured', 'upload_date', 'created_at', 'is_free', 'active_listing', 'approval_status')
                ->latest();

            if ($request->filled('type') && in_array($request->query('type'), ['merchandise', 'hajra', 'seller'], true)) {
                $query->where('type', $request->query('type'));
            }

            return DataTables::of($query)
                ->addColumn('thumbnail', function ($row) {
                    $imagePath = $row->thumbnail_url;

                    return '<img src="'.$imagePath.'" alt="'.$row->title.'" class="product-thumb" width="60" height="60">';
                })
                ->addColumn('title', function ($row) {
                    $title = '<div class="fw-bold">'.$row->title.'</div>';
                    if ($row->is_featured) {
                        $title .= '<span class="badge bg-warning text-dark">Featured</span>';
                    }

                    return $title;
                })
                ->addColumn('type', function ($row) {
                    return $row->type ? '<span class="badge bg-secondary">'.e($row->type).'</span>' : '<span class="text-muted">—</span>';
                })
                ->addColumn('owner', function ($row) {
                    return $row->owner ? $row->owner->name : 'N/A';
                })
                ->addColumn('price', function ($row) {
                    if ($row->is_free) {
                        return '<span class="badge bg-success">Free</span>';
                    }

                    return $row->price ? '£'.number_format($row->price, 2) : 'N/A';
                })
                ->addColumn('location', function ($row) {
                    return $row->location ?: '<span class="text-muted">N/A</span>';
                })
                ->addColumn('upload_date', function ($row) {
                    return $row->upload_date ? Carbon::parse($row->upload_date)->format('d M Y') : 'N/A';
                })
                ->addColumn('approval_status', function ($row) {
                    if (! $row->isSellerListing()) {
                        return '<span class="text-muted">—</span>';
                    }

                    $current = $row->approval_status ?? Product::APPROVAL_PENDING;
                    $options = [
                        Product::APPROVAL_PENDING => 'Pending',
                        Product::APPROVAL_APPROVED => 'Approved',
                        Product::APPROVAL_REJECTED => 'Rejected',
                    ];

                    $html = '<select class="form-select form-select-sm product-approval-status" data-url="'.route('admin.products.approval-status', $row->id).'" style="min-width: 110px;">';
                    foreach ($options as $value => $label) {
                        $selected = $current === $value ? ' selected' : '';
                        $html .= '<option value="'.$value.'"'.$selected.'>'.$label.'</option>';
                    }
                    $html .= '</select>';

                    return $html;
                })
                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="'.route('admin.products.edit', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="'.route('admin.products.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit.' '.$delete;
                })
                ->rawColumns(['thumbnail', 'title', 'type', 'price', 'location', 'approval_status', 'action'])
                ->make(true);
        }

        return abort(404);
    }

    public function edit($id)
    {
        $product = Product::with('images')->find($id);
        if (! $product) {
            return abort(404);
        }

        $sizes = Size::pluck('name', 'id');
        $categories = Category::pluck('name', 'id');
        $materials = Product::$materials;

        $html = view('backend.pages.products.edit', compact('product', 'sizes', 'categories', 'materials'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:merchandise,hajra,seller',
            'brand' => 'required|string|max:255',
            'material' => 'required|string|in:'.implode(',', array_keys(Product::$materials)),
            'color' => 'required|string|max:255',
            'size_id' => 'required|exists:sizes,id',
            'category_id' => 'required|exists:categories,id',
            'condition' => 'required|in:new,used',
            'price' => [
                Rule::requiredIf(fn () => ! ($request->input('type') === 'hajra' && $request->has('is_free'))),
                'numeric',
                'min:0',
            ],
            'is_free' => 'nullable|boolean',
            'is_washed' => 'nullable|in:0,1',
            'discount_enabled' => 'nullable|boolean',
            'discount_type' => 'nullable|in:percentage,flat',
            'discount' => 'nullable|numeric|min:0',
            'platform_donation' => 'nullable|boolean',
            'donation_percentage' => 'nullable|integer|min:0|max:100',
            'active_listing' => 'nullable|boolean',
            'approval_status' => 'nullable|in:pending,approved,rejected',
            'stock' => 'nullable|integer|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_featured' => 'nullable|boolean',
            'remove_thumbnail' => 'nullable',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'nullable|integer|exists:product_images,id',
        ]);

        $product = Product::find($request->id);
        $data = $request->except(['images', 'thumbnail', 'remove_images', 'remove_thumbnail']);

        if ($product && $product->isSellerListing() && $request->filled('approval_status')) {
            $data['approval_status'] = $request->input('approval_status');
        } else {
            unset($data['approval_status']);
        }

        $isHajraFree = ($request->input('type') === 'hajra') && $request->has('is_free');

        if ($request->input('type') !== 'hajra') {
            $data['is_free'] = 0;
        } else {
            $data['is_free'] = $request->has('is_free') ? 1 : 0;
        }

        // Handle boolean fields
        $data['is_washed'] = $request->input('is_washed', 0);
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        if ($isHajraFree) {
            $data['discount_enabled'] = 0;
            $data['platform_donation'] = 0;
            $data['price'] = 0;
            $data['discount_type'] = null;
            $data['discount'] = 0;
            $data['donation_percentage'] = 0;
        } else {
            $data['discount_enabled'] = $request->has('discount_enabled') ? 1 : 0;
            $data['platform_donation'] = $request->has('platform_donation') ? 1 : 0;
        }
        $data['active_listing'] = $request->input('active_listing', 1);
        if ($request->has('stock')) {
            $data['stock'] = (int) $request->input('stock', 1);
        }

        if (! $isHajraFree) {
            // Handle discount fields (discount column cannot be null)
            if (! $data['discount_enabled']) {
                $data['discount_type'] = null;
                $data['discount'] = 0;
            } else {
                $data['discount'] = (float) $request->input('discount', 0);
            }
            $data['discount'] = (float) ($data['discount'] ?? 0);

            // Handle donation percentage (column cannot be null)
            if (! ($data['platform_donation'] ?? 0)) {
                $data['donation_percentage'] = 0;
            } else {
                $data['donation_percentage'] = (int) $request->input('donation_percentage', 0);
            }
            $data['donation_percentage'] = (int) ($data['donation_percentage'] ?? 0);
        }

        // Handle thumbnail removal
        if ($request->has('remove_thumbnail') && $request->remove_thumbnail == '1') {
            if ($product->thumbnail) {
                ImageService::delete($product->thumbnail);
                $data['thumbnail'] = null;
            }
        }
        // Handle new thumbnail upload
        elseif ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = ImageService::upload($request->file('thumbnail'), 'products', $product->thumbnail);
        }

        // Handle removal of existing images
        if ($request->has('remove_images') && is_array($request->remove_images)) {
            foreach ($request->remove_images as $imageId) {
                $image = ProductImage::find($imageId);
                if ($image && $image->product_id == $product->id) {
                    ImageService::delete($image->image);
                    $image->delete();
                }
            }
        }

        // Handle new images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = ImageService::upload($image, 'products');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $imagePath,
                ]);
            }
        }

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
        ]);
    }

    public function delete($id)
    {
        $product = Product::with('images')->find($id);
        if (! $product) {
            return abort(404);
        }

        // Delete thumbnail
        if ($product->thumbnail) {
            ImageService::delete($product->thumbnail);
        }

        // Delete product images
        foreach ($product->images as $image) {
            ImageService::delete($image->image);
            $image->delete();
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    public function approve(int $id)
    {
        return $this->setApprovalStatus($id, Product::APPROVAL_APPROVED, 'approve', 'Product approved successfully');
    }

    public function reject(Request $request, int $id)
    {
        $request->validate([
            'message' => 'nullable|string|max:2000',
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        return $this->setApprovalStatus(
            $id,
            Product::APPROVAL_REJECTED,
            'reject',
            'Product rejected successfully',
            $request->input('message'),
            $request->input('rejection_reason'),
        );
    }

    public function returnForCorrection(Request $request, int $id)
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        return $this->setApprovalStatus(
            $id,
            Product::APPROVAL_RETURNED,
            'return_for_correction',
            'Product returned for correction',
            $request->input('message'),
        );
    }

    public function updateApprovalStatus(Request $request, int $id)
    {
        $request->validate([
            'approval_status' => 'required|in:pending,approved,rejected,returned',
            'message' => 'nullable|string|max:2000',
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        $status = $request->input('approval_status');
        $action = match ($status) {
            Product::APPROVAL_APPROVED => 'approve',
            Product::APPROVAL_REJECTED => 'reject',
            Product::APPROVAL_RETURNED => 'return_for_correction',
            default => 'set_pending',
        };
        $message = match ($status) {
            Product::APPROVAL_APPROVED => 'Product approved successfully',
            Product::APPROVAL_REJECTED => 'Product rejected successfully',
            Product::APPROVAL_RETURNED => 'Product returned for correction',
            default => 'Product set to pending review',
        };

        return $this->setApprovalStatus(
            $id,
            $status,
            $action,
            $message,
            $request->input('message'),
            $request->input('rejection_reason'),
        );
    }

    private function setApprovalStatus(
        int $id,
        string $status,
        string $action,
        string $flashMessage,
        ?string $moderationMessage = null,
        ?string $rejectionReason = null,
    ) {
        $product = Product::find($id);

        if (! $product || ! $product->isSellerListing()) {
            return response()->json([
                'success' => false,
                'message' => 'Seller product not found',
            ], 404);
        }

        app(ProductModerationService::class)->transition(
            $product,
            $status,
            $action,
            Auth::user(),
            $moderationMessage,
            $rejectionReason,
        );

        return response()->json([
            'success' => true,
            'message' => $flashMessage,
            'data' => [
                'id' => $product->id,
                'approval_status' => $product->fresh()->approval_status,
            ],
        ]);
    }
}
