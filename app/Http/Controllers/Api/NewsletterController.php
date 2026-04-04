<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewsletterSubscribeRequest;
use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function store(NewsletterSubscribeRequest $request): JsonResponse
    {
        try {
            $email = strtolower(trim((string) $request->validated('email')));
            $locale = $request->validated('locale');

            $existing = NewsletterSubscriber::query()->where('email', $email)->first();

            if ($existing !== null) {
                if ($locale !== null && $existing->locale !== $locale) {
                    $existing->update(['locale' => $locale]);
                }

                return response()->json([
                    'success' => true,
                    'already_subscribed' => true,
                    'message' => 'You are already subscribed to our newsletter.',
                ]);
            }

            NewsletterSubscriber::query()->create([
                'email' => $email,
                'locale' => $locale,
            ]);

            try {
                Mail::to($email)->send(new NewsletterWelcomeMail(localeHint: $locale));
            } catch (\Throwable $mailException) {
                Log::error('Newsletter welcome email failed', [
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
            Log::error('Newsletter subscribe error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $errorMessage = config('app.debug') ? (string) $e->getMessage() : null;

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
                'error' => $errorMessage,
            ], 500);
        }
    }
}
