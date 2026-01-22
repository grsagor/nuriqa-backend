<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Size;
use App\Services\ImageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ProductController extends Controller
{
    public function index()
    {
        return view('backend.pages.products.index');
    }

    public function create()
    {
        $sizes = Size::pluck('name', 'id');
        $categories = Category::pluck('name', 'id');
        $materials = Product::$materials;

        $html = view('backend.pages.products.create', compact('sizes', 'categories', 'materials'))->render();

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
            'brand' => 'required|string|max:255',
            'material' => 'required|string|in:'.implode(',', array_keys(Product::$materials)),
            'color' => 'required|string|max:255',
            'size_id' => 'required|exists:sizes,id',
            'category_id' => 'required|exists:categories,id',
            'condition' => 'required|in:new,used',
            'price' => 'required|numeric|min:0',
            'is_washed' => 'nullable|in:0,1',
            'discount_enabled' => 'nullable|boolean',
            'discount_type' => 'nullable|in:percentage,flat',
            'discount' => 'nullable|numeric|min:0',
            'platform_donation' => 'nullable|boolean',
            'donation_percentage' => 'nullable|integer|min:0|max:100',
            'active_listing' => 'nullable|boolean',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_featured' => 'nullable|boolean',
        ]);

        $data = $request->except(['images', 'thumbnail']);

        // Auto-assign owner_id from current authenticated user
        $data['owner_id'] = auth()->id();

        // Set type to 'seller' for admin-created products
        $data['type'] = 'seller';

        // Admin can only create merchandise products (not free)
        $data['is_free'] = 0;

        // Handle boolean fields
        $data['is_washed'] = $request->input('is_washed', 0);
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $data['discount_enabled'] = $request->has('discount_enabled') ? 1 : 0;
        $data['platform_donation'] = $request->has('platform_donation') ? 1 : 0;
        $data['active_listing'] = $request->input('active_listing', 1);

        // Handle discount fields
        if (! $data['discount_enabled']) {
            $data['discount_type'] = null;
            $data['discount'] = 0;
        } else {
            $data['discount'] = $request->input('discount', 0);
        }

        // Handle donation percentage
        if (! $data['platform_donation']) {
            $data['donation_percentage'] = 0;
        } else {
            $data['donation_percentage'] = $request->input('donation_percentage', 0);
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = ImageService::upload($request->file('thumbnail'), 'products');
        }

        // Set upload_date if not provided
        if (! isset($data['upload_date'])) {
            $data['upload_date'] = now()->toDateString();
        }

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

    public function list()
    {
        if (request()->ajax()) {
            $data = Product::with(['owner', 'size', 'category'])
                ->select('id', 'owner_id', 'title', 'price', 'thumbnail', 'location', 'is_featured', 'upload_date', 'created_at')
                ->latest();

            return DataTables::of($data)
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
                ->addColumn('owner', function ($row) {
                    return $row->owner ? $row->owner->name : 'N/A';
                })
                ->addColumn('price', function ($row) {
                    return $row->price ? '$'.number_format($row->price, 2) : 'N/A';
                })
                ->addColumn('location', function ($row) {
                    return $row->location ?: '<span class="text-muted">N/A</span>';
                })
                ->addColumn('upload_date', function ($row) {
                    return $row->upload_date ? Carbon::parse($row->upload_date)->format('d M Y') : 'N/A';
                })
                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="'.route('admin.products.edit', $row->id).'" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="'.route('admin.products.delete', $row->id).'" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit.' '.$delete;
                })
                ->rawColumns(['thumbnail', 'title', 'location', 'action'])
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
            'brand' => 'required|string|max:255',
            'material' => 'required|string|in:'.implode(',', array_keys(Product::$materials)),
            'color' => 'required|string|max:255',
            'size_id' => 'required|exists:sizes,id',
            'category_id' => 'required|exists:categories,id',
            'condition' => 'required|in:new,used',
            'price' => 'required|numeric|min:0',
            'is_washed' => 'nullable|in:0,1',
            'discount_enabled' => 'nullable|boolean',
            'discount_type' => 'nullable|in:percentage,flat',
            'discount' => 'nullable|numeric|min:0',
            'platform_donation' => 'nullable|boolean',
            'donation_percentage' => 'nullable|integer|min:0|max:100',
            'active_listing' => 'nullable|boolean',
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

        // Handle boolean fields
        $data['is_washed'] = $request->input('is_washed', 0);
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        $data['discount_enabled'] = $request->has('discount_enabled') ? 1 : 0;
        $data['platform_donation'] = $request->has('platform_donation') ? 1 : 0;
        $data['active_listing'] = $request->input('active_listing', 1);

        // Handle discount fields
        if (! $data['discount_enabled']) {
            $data['discount_type'] = null;
            $data['discount'] = 0;
        } else {
            $data['discount'] = $request->input('discount', 0);
        }

        // Handle donation percentage
        if (! $data['platform_donation']) {
            $data['donation_percentage'] = 0;
        } else {
            $data['donation_percentage'] = $request->input('donation_percentage', 0);
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
}
