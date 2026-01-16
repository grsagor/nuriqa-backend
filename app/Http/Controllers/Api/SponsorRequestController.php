<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SponsorRequest;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class SponsorRequestController extends Controller
{
    public function index(Request $request)
    {
        // Get all pending sponsor requests (public)
        $status = $request->input('status', 'pending');

        $query = SponsorRequest::with(['product.size', 'product.category', 'product.images', 'product.owner', 'user']);

        if ($status === 'pending') {
            $query->where('status', 'pending');
        }

        // Filter by product category
        if ($request->filled('categories')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->whereIn('category_id', $request->categories);
            });
        }

        // Filter by product condition
        if ($request->filled('conditions')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->whereIn('condition', $request->conditions);
            });
        }

        // Filter by product size
        if ($request->filled('sizes')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->whereIn('size_id', $request->sizes);
            });
        }

        // Filter by product others (is_washed, etc.)
        if ($request->filled('others')) {
            $query->whereHas('product', function ($q) use ($request) {
                if (in_array('is_washed', $request->others)) {
                    $q->where('is_washed', 1);
                }
            });
        }

        // Search by product title
        if ($request->filled('search')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%');
            });
        }

        // Filter by price range
        if ($request->filled('prices')) {
            $query->whereHas('product', function ($q) use ($request) {
                $prices = $request->prices;
                $q->where(function ($subQuery) use ($prices) {
                    foreach ($prices as $price) {
                        // Free products
                        if ($price === 'free') {
                            $subQuery->orWhere(function ($freeQuery) {
                                $freeQuery->where('is_free', 1)
                                    ->orWhere('price', 0);
                            });
                        }

                        // Range: 100-150
                        if (preg_match('/^(\d+)-(\d+)$/', $price, $matches)) {
                            $min = (int) $matches[1];
                            $max = (int) $matches[2];
                            $subQuery->orWhereBetween('price', [$min, $max]);
                        }

                        // Open-ended: 150+
                        if (preg_match('/^(\d+)\+$/', $price, $matches)) {
                            $min = (int) $matches[1];
                            $subQuery->orWhere('price', '>=', $min);
                        }
                    }
                });
            });
        }

        // Sorting
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderByRaw('(SELECT price FROM products WHERE products.id = sponsor_requests.product_id) ASC');
                    break;

                case 'price_desc':
                    $query->orderByRaw('(SELECT price FROM products WHERE products.id = sponsor_requests.product_id) DESC');
                    break;

                default:
                    $query->latest();
                    break;
            }
        } else {
            $query->latest();
        }

        // Limit results
        if ($request->filled('limit')) {
            $query->limit($request->limit);
        }

        $requests = $query->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function myRequests(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $requests = SponsorRequest::where('user_id', $user->id)
            ->with(['product.size', 'product.category', 'product.images', 'product.owner'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'request_reason' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'apartment' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:255',
            'additional_info' => 'nullable|string',
            'keep_updated' => 'nullable|boolean',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        // Check if product exists
        $product = Product::find($request->product_id);
        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $sponsorRequest = SponsorRequest::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'request_reason' => $request->request_reason,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'apartment' => $request->apartment,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'additional_info' => $request->additional_info,
            'keep_updated' => filter_var($request->input('keep_updated', false), FILTER_VALIDATE_BOOLEAN),
            'status' => 'pending',
        ]);

        $sponsorRequest->load(['product.size', 'product.category', 'product.images', 'product.owner']);

        return response()->json([
            'success' => true,
            'message' => 'Sponsor request submitted successfully',
            'data' => $sponsorRequest,
        ], 201);
    }

    public function show(string $id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $sponsorRequest = SponsorRequest::where('user_id', $user->id)
            ->with(['product.size', 'product.category', 'product.images', 'product.owner'])
            ->find($id);

        if (! $sponsorRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Sponsor request not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sponsorRequest,
        ]);
    }

    /**
     * Public endpoint to view a sponsor request by ID
     * Only shows pending sponsor requests
     */
    public function publicShow(string $id)
    {
        $sponsorRequest = SponsorRequest::where('status', 'pending')
            ->with(['product.size', 'product.category', 'product.condition', 'product.images', 'product.owner', 'user'])
            ->find($id);

        if (! $sponsorRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Sponsor request not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sponsorRequest,
        ]);
    }
}
