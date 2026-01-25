<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Size;
use App\Models\Wishlist;
use App\Services\ImageService;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['size', 'category']);

        if ($request->filled('myproduct')) {
            $user = JWTAuth::parseToken()->authenticate();
            $query->where('owner_id', $user->id);
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->filled('conditions')) {
            $query->whereIn('condition', $request->conditions);
        }
        if ($request->filled('sizes')) {
            $query->whereIn('size_id', $request->sizes);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('categories')) {
            $query->whereIn('category_id', $request->categories);
        }
        if ($request->filled('featured')) {
            $query->where('is_featured', 1);
        }
        if ($request->filled('others')) {
            if (in_array('is_washed', $request->others)) {
                $query->where('is_washed', 1);
            }
        }

        // Filter by status (active / inactive)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('active_listing', 1);
            }

            if ($request->status === 'inactive') {
                $query->where('active_listing', 0);
            }
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        // Sorting
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;

                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;

                default:
                    $query->latest(); // created_at desc
                    break;
            }
        } else {
            $query->latest(); // default sort
        }

        if ($request->filled('prices')) {
            $prices = $request->prices;

            $query->where(function ($q) use ($prices) {
                foreach ($prices as $price) {

                    // Free products
                    if ($price === 'free') {
                        $q->orWhere(function ($sub) {
                            $sub->where('is_free', 1)
                                ->orWhere('price', 0);
                        });
                    }

                    // Range: 100-150
                    if (preg_match('/^(\d+)-(\d+)$/', $price, $matches)) {
                        $min = (int) $matches[1];
                        $max = (int) $matches[2];

                        $q->orWhereBetween('price', [$min, $max]);
                    }

                    // Open-ended: 150+
                    if (preg_match('/^(\d+)\+$/', $price, $matches)) {
                        $min = (int) $matches[1];

                        $q->orWhere('price', '>=', $min);
                    }
                }
            });
        }

        if ($request->filled('limit')) {
            $query->limit($request->limit);
        }
        if ($request->filled('offset')) {
            $query->offset($request->offset);
        }

        $products = $query->get();

        // Check if user is authenticated and get their wishlist product IDs
        $wishlistProductIds = [];
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if ($user) {
                $wishlistProductIds = Wishlist::where('user_id', $user->id)
                    ->pluck('product_id')
                    ->toArray();
            }
        } catch (\Exception $e) {
            // User not authenticated, wishlistProductIds remains empty
        }

        // Add wishlist status to each product
        $products->each(function ($product) use ($wishlistProductIds) {
            $product->is_in_wishlist = in_array($product->id, $wishlistProductIds);
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',

            'is_washed' => 'nullable|boolean',

            'brand' => 'nullable|string|max:255',
            'material' => 'nullable|string',
            'color' => 'nullable|string|max:255',

            'price' => 'nullable|numeric|min:0',

            'is_free' => 'nullable|boolean',

            'discount_enabled' => 'nullable|boolean',
            'discount_type' => 'nullable|in:percentage,flat',
            'discount' => 'nullable|numeric|min:0',

            'platform_donation' => 'nullable|boolean',
            'donation_percentage' => 'nullable|numeric|min:0|max:100',

            'active_listing' => 'nullable|boolean',

            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:10000',
        ]);

        $data = $request->except(['images']);

        // Owner comes from auth, never frontend
        $user = JWTAuth::parseToken()->authenticate();
        $data['owner_id'] = $user->id;

        // Normalize booleans
        $data['is_washed'] = filter_var($request->input('is_washed'), FILTER_VALIDATE_BOOLEAN);
        $data['is_free'] = filter_var($request->input('is_free'), FILTER_VALIDATE_BOOLEAN);
        $data['discount_enabled'] = filter_var($request->input('discount_enabled'), FILTER_VALIDATE_BOOLEAN);
        $data['platform_donation'] = filter_var($request->input('platform_donation'), FILTER_VALIDATE_BOOLEAN);
        $data['active_listing'] = filter_var($request->input('active_listing', true), FILTER_VALIDATE_BOOLEAN);

        // Enforce FREE product rules
        if ($data['is_free']) {
            $data['price'] = 0;
            $data['discount'] = 0;
            $data['discount_enabled'] = false;
            $data['discount_type'] = null;
            $data['platform_donation'] = false;
            $data['donation_percentage'] = 0;
        }

        // Enforce discount rules
        if (! $data['discount_enabled']) {
            $data['discount'] = 0;
            $data['discount_type'] = null;
        }

        // Upload date fallback
        $data['upload_date'] = now()->toDateString();
        $data['type'] = $request->type;

        $product = Product::create($data);

        // Handle images + auto thumbnail
        if ($request->hasFile('images')) {

            $images = $request->file('images');
            $thumbnailPath = null;

            foreach ($images as $index => $image) {
                $path = ImageService::upload($image, 'products');

                // First image becomes thumbnail
                if ($index === 0) {
                    $thumbnailPath = $path;
                }

                ProductImage::create([
                    'product_id' => $product->id,
                    'image' => $path,
                ]);
            }

            // Save thumbnail if not already set
            if ($thumbnailPath && empty($product->thumbnail)) {
                $product->update([
                    'thumbnail' => $thumbnailPath,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'product_id' => $product->id,
        ]);
    }

    public function categories(Request $request)
    {
        $categories = Category::all();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function sizes(Request $request)
    {
        $sizes = Size::all();

        return response()->json([
            'success' => true,
            'data' => $sizes,
        ]);
    }

    public function show(string $id)
    {
        $product = Product::with([
            'owner',
            'size',
            'category',
            'images',
        ])->find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        // Check if user is authenticated and if product is in their wishlist
        $product->is_in_wishlist = false;
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if ($user) {
                $product->is_in_wishlist = Wishlist::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->exists();
            }
        } catch (\Exception $e) {
            // User not authenticated, is_in_wishlist remains false
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }
}
