<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Mail\ContactMail;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Handle contact form submission
     */
    public function store(ContactRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Save to database
            Contact::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'subject' => $data['subject'],
                'message' => $data['message'] ?? null,
                'is_read' => false,
            ]);

            // Get admin email from config or use a default
            $adminEmail = config('mail.from.address', 'admin@nuriqa.com');

            // Send email using default mailer
            Mail::to($adminEmail)->send(
                new ContactMail(
                    firstName: $data['first_name'],
                    lastName: $data['last_name'],
                    email: $data['email'],
                    phone: $data['phone'],
                    messageSubject: $data['subject'],
                    message: $data['message'] ?? null,
                )
            );

            return response()->json([
                'success' => true,
                'message' => 'Thank you for contacting us. We will get back to you soon.',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Contact Form Error: '.$e->getMessage());
            Log::error('Contact Form Stack Trace: '.$e->getTraceAsString());

            $errorMessage = config('app.debug') ? (string) $e->getMessage() : null;

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message. Please try again later.',
                'error' => $errorMessage,
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}
