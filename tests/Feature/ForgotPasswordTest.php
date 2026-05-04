<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_flow_with_email_sets_new_password(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-me@example.com',
            'phone' => '+447700900111',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/forgot-password', [
            'identifier' => 'reset-me@example.com',
        ])->assertOk()->assertJson(['success' => true]);

        $user->refresh();
        $user->forceFill([
            'otp' => '654321',
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ])->save();

        $verify = $this->postJson('/api/v1/forgot-password/verify-otp', [
            'identifier' => 'reset-me@example.com',
            'otp' => '654321',
        ])->assertOk();

        $token = $verify->json('data.reset_token');
        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));

        $this->postJson('/api/v1/forgot-password/reset', [
            'reset_token' => $token,
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-secret-1', $user->fresh()->password));
    }

    public function test_forgot_password_accepts_phone_country_and_number_pair(): void
    {
        $user = User::factory()->create([
            'email' => 'u2@example.com',
            'phone' => '+447700900222',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/forgot-password', [
            'phone_country_code' => '+44',
            'phone_number' => '7700900222',
        ])->assertOk();

        $user->refresh();
        $user->forceFill([
            'otp' => '111222',
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ])->save();

        $verify = $this->postJson('/api/v1/forgot-password/verify-otp', [
            'phone_country_code' => '+44',
            'phone_number' => '7700900222',
            'otp' => '111222',
        ])->assertOk();

        $token = $verify->json('data.reset_token');
        $this->postJson('/api/v1/forgot-password/reset', [
            'reset_token' => $token,
            'password' => 'another-pass',
            'password_confirmation' => 'another-pass',
        ])->assertOk();

        $this->assertTrue(Hash::check('another-pass', $user->fresh()->password));
    }

    public function test_forgot_password_verify_rejects_bad_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'bad@example.com',
            'phone' => '+447700900333',
            'email_verified_at' => now(),
        ]);

        $this->postJson('/api/v1/forgot-password', [
            'identifier' => 'bad@example.com',
        ])->assertOk();

        $user->forceFill([
            'otp' => '000000',
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ])->save();

        $this->postJson('/api/v1/forgot-password/verify-otp', [
            'identifier' => 'bad@example.com',
            'otp' => '999999',
        ])->assertStatus(400);
    }
}
