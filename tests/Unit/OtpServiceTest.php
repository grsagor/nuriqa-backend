<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_generate_stores_six_digit_otp_and_expiry_on_user(): void
    {
        config(['otp.expires_minutes' => 15]);

        $user = User::factory()->unverified()->create(['email' => 'otp-test@example.com']);

        $returned = OtpService::generate($user->email);

        $user->refresh();

        $this->assertSame($returned, $user->otp);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $user->otp);
        $this->assertNotNull($user->otp_expires_at);
        $this->assertTrue($user->otp_expires_at->isFuture());
        $this->assertTrue($user->otp_expires_at->lessThanOrEqualTo(Carbon::now()->addMinutes(15)));
    }

    public function test_verify_succeeds_and_clears_otp_when_code_matches_and_not_expired(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'verify@example.com',
            'otp' => '424242',
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $this->assertTrue(OtpService::verify($user->email, '424242'));

        $user->refresh();
        $this->assertNull($user->otp);
        $this->assertNull($user->otp_expires_at);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_verify_fails_when_code_is_wrong(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'wrong@example.com',
            'otp' => '111111',
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        $this->assertFalse(OtpService::verify($user->email, '999999'));

        $user->refresh();
        $this->assertSame('111111', $user->otp);
        $this->assertNull($user->email_verified_at);
    }

    public function test_verify_fails_when_otp_has_expired(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'expired@example.com',
            'otp' => '333333',
            'otp_expires_at' => Carbon::now()->subMinute(),
        ]);

        $this->assertFalse(OtpService::verify($user->email, '333333'));

        $user->refresh();
        $this->assertSame('333333', $user->otp);
        $this->assertNull($user->email_verified_at);
    }
}
