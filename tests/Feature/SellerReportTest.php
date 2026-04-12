<?php

namespace Tests\Feature;

use App\Models\SellerReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class SellerReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_submit_seller_report(): void
    {
        $seller = User::factory()->create();

        $response = $this->postJson('/api/v1/seller-reports', [
            'reported_user_id' => $seller->id,
            'reason' => 'Other',
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_submit_seller_report(): void
    {
        $reporter = User::factory()->create();
        $seller = User::factory()->create();
        $token = JWTAuth::fromUser($reporter);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/seller-reports', [
                'reported_user_id' => $seller->id,
                'reason' => 'Fraud or scam',
                'details' => 'Details here',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('seller_reports', [
            'reporter_id' => $reporter->id,
            'reported_user_id' => $seller->id,
            'reason' => 'Fraud or scam',
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_report_themselves(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/seller-reports', [
                'reported_user_id' => $user->id,
                'reason' => 'Other',
            ]);

        $response->assertStatus(422);
        $this->assertSame(0, SellerReport::count());
    }

    public function test_public_seller_profile_returns_404_for_missing_user(): void
    {
        $response = $this->getJson('/api/v1/sellers/999999/profile');

        $response->assertStatus(404);
    }
}
