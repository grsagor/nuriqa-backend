<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSellerReportRequest;
use App\Models\SellerReport;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class SellerReportController extends Controller
{
    public function store(StoreSellerReportRequest $request)
    {
        $reporter = Auth::user();
        $reportedId = (int) $request->validated('reported_user_id');

        if (! $reporter || (int) $reporter->id === $reportedId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot report yourself.',
            ], 422);
        }

        $reported = User::find($reportedId);
        if (! $reported) {
            return response()->json([
                'success' => false,
                'message' => 'Seller not found.',
            ], 404);
        }

        SellerReport::create([
            'reporter_id' => $reporter->id,
            'reported_user_id' => $reportedId,
            'reason' => $request->validated('reason'),
            'details' => $request->validated('details'),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you. Your report has been submitted.',
        ], 201);
    }
}
