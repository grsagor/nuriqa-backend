<?php

namespace Tests\Feature;

use App\Models\SupportCase;
use App\Models\User;
use App\Services\SupportCaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class SupportCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_damage_dispute_and_query_cases(): void
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        foreach (['damage', 'dispute', 'query'] as $type) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson('/api/v1/support-cases', [
                    'type' => $type,
                    'subject' => "Test {$type}",
                    'description' => 'Details about the issue with evidence notes.',
                    'evidence' => ['https://example.com/photo.jpg'],
                ])
                ->assertCreated()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.type', $type)
                ->assertJsonPath('data.status', SupportCase::STATUS_SUBMITTED);
        }

        $this->assertSame(3, SupportCase::query()->count());
    }

    public function test_support_case_service_acknowledges_and_decides(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $service = app(SupportCaseService::class);

        $case = $service->create($user, [
            'type' => SupportCase::TYPE_QUERY,
            'subject' => 'Help',
            'description' => 'I need help with my order.',
        ]);

        $service->acknowledge($case, $admin);
        $this->assertSame(SupportCase::STATUS_ACKNOWLEDGED, $case->fresh()->status);

        $service->decide($case, [
            'decision' => 'answered',
            'status' => SupportCase::STATUS_CLOSED,
            'message' => 'Resolved via email.',
        ], $admin);

        $this->assertSame(SupportCase::STATUS_CLOSED, $case->fresh()->status);
        $this->assertNotNull($case->fresh()->closed_at);
    }
}
