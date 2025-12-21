<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class OtpService
{
    public static function generate(string $email): string
    {
        // For development, always use 123456
        $otpCode = '123456';
        
        // In production, you would generate a random 6-digit code
        // $otpCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Update user with new OTP
        $user = User::where('email', $email)->first();
        
        if ($user) {
            $user->update([
                'otp' => $otpCode,
                'otp_expires_at' => Carbon::now()->addMinutes(15), // OTP expires in 15 minutes
            ]);
        }
        
        // In development, we don't send the OTP
        // In production, you would send the OTP via email/SMS here
        // self::sendOtp($email, $otpCode);
        
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
        // Implementation for sending OTP via email/SMS
        // This is where you would integrate with your email service
        // or SMS service provider
    }
}