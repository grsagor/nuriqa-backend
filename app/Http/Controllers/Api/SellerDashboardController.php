<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SellerDashboardStatsService;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class SellerDashboardController extends Controller
{
    public function __construct(
        private SellerDashboardStatsService $sellerDashboardStatsService,
    ) {}

    public function stats(): JsonResponse
    {
        $seller = JWTAuth::parseToken()->authenticate();

        return response()->json([
            'success' => true,
            'data' => $this->sellerDashboardStatsService->summary($seller->id),
        ]);
    }
}
