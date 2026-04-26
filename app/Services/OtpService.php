<?php

namespace App\Services;

use App\Mail\OtpVerificationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class OtpService
{
    public static function generate(string $email): string
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return '';
        }

        return self::generateForUser($user);
    }

    public static function generateForUser(User $user): string
    {
        $otpCode = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp' => $otpCode,
            'otp_expires_at' => Carbon::now()->addMinutes(config('otp.expires_minutes')),
        ]);

        if ((bool) config('otp.send_email', false)) {
            self::sendOtpEmail($user->email, $otpCode);
        }
        self::sendOtpSms($user->phone, $otpCode);

        return $otpCode;
    }

    public static function verify(string $emailOrPhone, string $code): bool
    {
        $user = User::query()
            ->where(function ($query) use ($emailOrPhone) {
                $query->where('email', $emailOrPhone)
                    ->orWhere('phone', $emailOrPhone);
            })
            ->where('otp', $code)
            ->where('otp_expires_at', '>', Carbon::now())
            ->first();

        if ($user) {
            // Clear OTP and set email as verified
            $user->update([
                'otp' => null,
                'otp_expires_at' => null,
                'email_verified_at' => Carbon::now(),
            ]);

            return true;
        }

        return false;
    }

    public static function isVerified(string $email): bool
    {
        return User::where('email', $email)
            ->whereNotNull('email_verified_at')
            ->exists();
    }

    private static function sendOtpEmail(?string $email, string $code): void
    {
        if (empty($email)) {
            return;
        }

        try {
            Mail::to($email)->send(new OtpVerificationMail($code));
        } catch (\Throwable $e) {
            Log::error('Failed to send OTP email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private static function sendOtpSms(?string $phone, string $code): void
    {
        if (empty($phone)) {
            return;
        }

        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');
        $messagingServiceSid = config('services.twilio.messaging_service_sid');

        if (empty($sid) || empty($token) || (empty($from) && empty($messagingServiceSid))) {
            Log::warning('Twilio credentials are not configured. OTP SMS not sent.', [
                'phone' => $phone,
            ]);

            return;
        }

        try {
            $twilio = new Client($sid, $token);
            $messageBody = sprintf('Your %s verification code is %s', config('app.name', 'Nuriqa'), $code);

            $payload = [
                'body' => $messageBody,
            ];

            if (! empty($messagingServiceSid)) {
                $payload['messagingServiceSid'] = $messagingServiceSid;
            } else {
                $payload['from'] = $from;
            }

            $twilio->messages->create($phone, $payload);
        } catch (TwilioException $e) {
            Log::error('Failed to send OTP SMS', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
