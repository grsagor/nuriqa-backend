<?php

namespace Modules\Gazian\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Gazian\Http\Requests\NewsletterSubscribeRequest;
use Modules\Gazian\Mail\NewsletterAdminMail;
use Modules\Gazian\Mail\NewsletterWelcomeMail;
use Modules\Gazian\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function store(NewsletterSubscribeRequest $request): JsonResponse
    {
        try {
            $email = strtolower(trim((string) $request->validated('email')));

            $existing = NewsletterSubscriber::query()->where('email', $email)->first();

            if ($existing !== null) {
                return response()->json([
                    'success' => true,
                    'already_subscribed' => true,
                    'message' => 'You are already subscribed to our newsletter.',
                ]);
            }

            NewsletterSubscriber::query()->create([
                'email' => $email,
            ]);

            $adminEmail = (string) config('gazian.admin_email');

            try {
                Mail::to($email)->send(new NewsletterWelcomeMail($email));
            } catch (\Throwable $mailException) {
                Log::error('Gazian newsletter welcome email failed', [
                    'email' => $email,
                    'error' => $mailException->getMessage(),
                ]);
            }

            try {
                if ($adminEmail !== '') {
                    Mail::to($adminEmail)->send(new NewsletterAdminMail($email));
                }
            } catch (\Throwable $mailException) {
                Log::error('Gazian newsletter admin email failed', [
                    'email' => $email,
                    'error' => $mailException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'already_subscribed' => false,
                'message' => 'Thank you for subscribing. We will keep you updated.',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Gazian newsletter subscribe error: '.$e->getMessage(), [
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
