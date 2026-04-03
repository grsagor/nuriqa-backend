<?php

namespace App\Services;

use App\Mail\OtpVerificationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public static function generate(string $email): string
    {
        $otpCode = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update([
                'otp' => $otpCode,
                'otp_expires_at' => Carbon::now()->addMinutes(config('otp.expires_minutes')),
            ]);

            self::sendOtp($email, $otpCode);
        }

        return $otpCode;
    }

    public static function verify(string $email, string $code): bool
    {
        $user = User::where('email', $email)
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

    private static function sendOtp(string $email, string $code): void
    {
        try {
            Mail::to($email)->send(new OtpVerificationMail($code));
        } catch (\Throwable $e) {
            Log::error('Failed to send OTP email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
