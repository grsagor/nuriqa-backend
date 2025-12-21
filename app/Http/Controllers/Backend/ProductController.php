<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Size;
use App\Models\Category;
use App\Models\Condition;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;

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
        $conditions = Condition::pluck('name', 'id');
        $materials = Product::$materials;
        $users = User::pluck('name', 'id');
        
        $html = view('backend.pages.products.create', compact('sizes', 'categories', 'conditions', 'materials', 'users'))->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'owner_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_washed' => 'nullable',
            'location' => 'nullable|string|max:255',
            'upload_date' => 'nullable|date',
            'brand' => 'nullable|string|max:255',
            'size_id' => 'nullable|exists:sizes,id',
            'category_id' => 'nullable|exists:categories,id',
            'condition_id' => 'nullable|exists:conditions,id',
            'material' => 'nullable|string|in:' . implode(',', array_keys(Product::$materials)),
            'color' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_featured' => 'nullable'
        ]);

        $data = $request->except(['images', 'thumbnail']);
        
        // Handle boolean fields
        $data['is_washed'] = $request->has('is_washed') ? 1 : 0;
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        
        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = ImageService::upload($request->file('thumbnail'), 'products');
        }
        
        // Set upload_date if not provided
        if (!isset($data['upload_date'])) {
            $data['upload_date'] = now()->toDateString();
        }

        $product = Product::create($data);
        
        // Handle multiple images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePath = ImageService::upload($image, 'products');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $imagePath
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully'
        ]);
    }
    
    public function list()
    {
        if (request()->ajax()) {
            $data = Product::with(['owner', 'size', 'category', 'condition'])
                ->select('id', 'owner_id', 'title', 'price', 'thumbnail', 'location', 'is_featured', 'upload_date', 'created_at')
                ->latest();

            return DataTables::of($data)
                ->addColumn('thumbnail', function ($row) {
                    $imagePath = $row->thumbnail_url;
                    return '<img src="' . $imagePath . '" alt="' . $row->title . '" class="product-thumb" width="60" height="60">';
                })
                ->addColumn('title', function ($row) {
                    $title = '<div class="fw-bold">' . $row->title . '</div>';
                    if ($row->is_featured) {
                        $title .= '<span class="badge bg-warning text-dark">Featured</span>';
                    }
                    return $title;
                })
                ->addColumn('owner', function ($row) {
                    return $row->owner ? $row->owner->name : 'N/A';
                })
                ->addColumn('price', function ($row) {
                    return $row->price ? '$' . number_format($row->price, 2) : 'N/A';
                })
                ->addColumn('location', function ($row) {
                    return $row->location ?: '<span class="text-muted">N/A</span>';
                })
                ->addColumn('upload_date', function ($row) {
                    return $row->upload_date ? Carbon::parse($row->upload_date)->format('d M Y') : 'N/A';
                })
                ->addColumn('action', function ($row) {
                    $edit = '<button data-url="' . route('admin.products.edit', $row->id) . '" data-modal-parent="#crudModal" class="btn btn-sm btn-primary open_modal_btn"><i class="fas fa-edit"></i></button>';
                    $delete = '<button data-url="' . route('admin.products.delete', $row->id) . '" class="btn btn-sm btn-danger crud_delete_btn"><i class="fas fa-trash"></i></button>';

                    return $edit . ' ' . $delete;
                })
                ->rawColumns(['thumbnail', 'title', 'location', 'action'])
                ->make(true);
        }

        return abort(404);
    }
    
    public function edit($id)
    {
        $product = Product::with('images')->find($id);
        if (!$product) {
            return abort(404);
        }
        
        $sizes = Size::pluck('name', 'id');
        $categories = Category::pluck('name', 'id');
        $conditions = Condition::pluck('name', 'id');
        $materials = Product::$materials;
        $users = User::pluck('name', 'id');
        
        $html = view('backend.pages.products.edit', compact('product', 'sizes', 'categories', 'conditions', 'materials', 'users'))->render();
        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    
    public function update(Request $request)
    {
        $request->validate([
            'owner_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_washed' => 'nullable',
            'location' => 'nullable|string|max:255',
            'upload_date' => 'nullable|date',
            'brand' => 'nullable|string|max:255',
            'size_id' => 'nullable|exists:sizes,id',
            'category_id' => 'nullable|exists:categories,id',
            'condition_id' => 'nullable|exists:conditions,id',
            'material' => 'nullable|string|in:' . implode(',', array_keys(Product::$materials)),
            'color' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_featured' => 'nullable',
            'remove_thumbnail' => 'nullable',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'nullable|integer|exists:product_images,id'
        ]);

        $product = Product::find($request->id);
        $data = $request->except(['images', 'thumbnail', 'remove_images', 'remove_thumbnail']);
        
        // Handle boolean fields
        $data['is_washed'] = $request->has('is_washed') ? 1 : 0;
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;
        
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
                    'image' => $imagePath
                ]);
            }
        }

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully'
        ]);
    }
    
    public function delete($id)
    {
        $product = Product::with('images')->find($id);
        if (!$product) {
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
            'message' => 'Product deleted successfully'
        ]);
    }
}