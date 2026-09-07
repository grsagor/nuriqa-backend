<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cause;
use Illuminate\Http\JsonResponse;

class CauseController extends Controller
{
    public function index(): JsonResponse
    {
        $causes = Cause::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'description']);

        return response()->json([
            'success' => true,
            'data' => $causes,
        ]);
    }
}
