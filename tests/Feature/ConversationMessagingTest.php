<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ConversationMessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_conversation_and_send_message(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $token = JWTAuth::fromUser($a);

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/conversations', [
                'user_id' => $b->id,
            ]);

        $create->assertStatus(201)->assertJsonPath('success', true);
        $conversationId = $create->json('data.id');
        $this->assertNotNull($conversationId);

        $msg = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/v1/conversations/{$conversationId}/messages", [
                'type' => 'text',
                'body' => 'Hello there',
            ]);

        $msg->assertStatus(201)->assertJsonPath('success', true)
            ->assertJsonPath('data.body', 'Hello there');

        $list = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson("/api/v1/conversations/{$conversationId}/messages");

        $list->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($list->json('data')));
    }

    public function test_user_cannot_message_self(): void
    {
        $a = User::factory()->create();
        $token = JWTAuth::fromUser($a);

        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/conversations', [
                'user_id' => $a->id,
            ]);

        $create->assertStatus(422);
    }
}
