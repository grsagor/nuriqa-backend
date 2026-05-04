<?php

namespace Tests\Feature;

use App\Mail\OtpVerificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationOtpDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_sends_no_otp_email_only_sms_channel(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/signup', [
            'name' => 'Register User',
            'email' => 'register-channel@example.com',
            'password' => 'secret12',
            'password_confirmation' => 'secret12',
            'phone_country_code' => '+1',
            'phone_number' => '5551234567',
        ])
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        Mail::assertNotSent(OtpVerificationMail::class);
    }

    public function test_resend_otp_does_not_send_email(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/signup', [
            'name' => 'U2',
            'email' => 'resend-otp@example.com',
            'password' => 'secret12',
            'password_confirmation' => 'secret12',
            'phone_country_code' => '+1',
            'phone_number' => '5559876543',
        ])->assertStatus(201);

        Mail::assertNotSent(OtpVerificationMail::class);

        $this->postJson('/api/v1/resend-otp', [
            'identifier' => 'resend-otp@example.com',
        ])->assertOk();

        Mail::assertNotSent(OtpVerificationMail::class);
    }
}
