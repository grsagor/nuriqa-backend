<?php

namespace Modules\Gazian\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Gazian\Http\Requests\TradeEnquiryRequest;
use Modules\Gazian\Mail\TradeEnquiryAdminMail;
use Modules\Gazian\Mail\TradeEnquiryCustomerMail;
use Modules\Gazian\Models\TradeEnquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TradeEnquiryController extends Controller
{
    public function store(TradeEnquiryRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $enquiry = TradeEnquiry::query()->create([
                'name' => $data['name'],
                'email' => strtolower(trim((string) $data['email'])),
                'interest' => $data['interest'],
                'business_type' => $data['business_type'],
                'explore' => $data['explore'],
                'country' => $data['country'],
                'city' => $data['city'],
                'is_read' => false,
            ]);

            $adminEmail = (string) config('gazian.admin_email');

            try {
                Mail::to($enquiry->email)->send(new TradeEnquiryCustomerMail($enquiry));
            } catch (\Throwable $mailException) {
                Log::error('Gazian trade enquiry customer email failed', [
                    'email' => $enquiry->email,
                    'error' => $mailException->getMessage(),
                ]);
            }

            try {
                if ($adminEmail !== '') {
                    Mail::to($adminEmail)->send(new TradeEnquiryAdminMail($enquiry));
                }
            } catch (\Throwable $mailException) {
                Log::error('Gazian trade enquiry admin email failed', [
                    'email' => $enquiry->email,
                    'error' => $mailException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your enquiry. We will be in touch shortly.',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Gazian trade enquiry error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
                'error' => config('app.debug') ? (string) $e->getMessage() : null,
            ], 500);
        }
    }
}
