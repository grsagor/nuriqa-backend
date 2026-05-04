<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, AdminDashboardStatsService $dashboardStats): View
    {
        $range = (int) $request->query('range', 30);
        if (! in_array($range, [7, 30], true)) {
            $range = 30;
        }

        $stats = $dashboardStats->summary($range);

        return view('backend.pages.dashboard.index', compact('stats', 'range'));
    }
}
