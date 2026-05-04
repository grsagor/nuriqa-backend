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
    /**
     * @param  array{send_email?: bool, send_sms?: bool}  $delivery
     */
    public static function generate(string $email, array $delivery = []): string
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return '';
        }

        if ($delivery === []) {
            $delivery = self::defaultRegistrationDelivery();
        }

        return self::generateForUser($user, $delivery);
    }

    /**
     * @param  array{send_email?: bool, send_sms?: bool}  $delivery
     */
    public static function generateForUser(User $user, array $delivery = []): string
    {
        $otpCode = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'otp' => $otpCode,
            'otp_expires_at' => Carbon::now()->addMinutes(config('otp.expires_minutes')),
        ]);

        if ($delivery === []) {
            $delivery = [
                'send_email' => (bool) config('otp.send_email', false),
                'send_sms' => true,
            ];
        }

        $sendEmail = (bool) ($delivery['send_email'] ?? false);
        $sendSms = array_key_exists('send_sms', $delivery)
            ? (bool) $delivery['send_sms']
            : true;

        if ($sendEmail) {
            self::sendOtpEmail($user->email, $otpCode);
        }
        if ($sendSms) {
            self::sendOtpSms($user->phone, $otpCode);
        }

        return $otpCode;
    }

    /**
     * Registration / verify-phone flows: SMS only.
     *
     * @return array{send_email: bool, send_sms: bool}
     */
    public static function defaultRegistrationDelivery(): array
    {
        return [
            'send_email' => false,
            'send_sms' => true,
        ];
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

    /**
     * Whether the user currently has a non-expired OTP matching the given code (no DB mutation).
     */
    public static function matchesActiveOtp(User $user, string $code): bool
    {
        if ($user->otp === null || $user->otp_expires_at === null) {
            return false;
        }

        if (! hash_equals((string) $user->otp, $code)) {
            return false;
        }

        return Carbon::now()->lt(Carbon::parse($user->otp_expires_at));
    }

    public static function clearOtpFields(User $user): void
    {
        $user->forceFill([
            'otp' => null,
            'otp_expires_at' => null,
        ])->save();
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
