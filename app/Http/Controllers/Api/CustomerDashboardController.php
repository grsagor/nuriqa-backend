<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerDashboardStatsService;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CustomerDashboardController extends Controller
{
    public function __construct(
        private CustomerDashboardStatsService $customerDashboardStatsService,
    ) {}

    public function stats(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        return response()->json([
            'success' => true,
            'data' => $this->customerDashboardStatsService->summary($user->id),
        ]);
    }
}
