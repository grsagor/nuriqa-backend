<?php

namespace Tests\Feature;

use App\Models\SupportCase;
use App\Models\User;
use App\Services\SupportCaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSupportCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_support_cases_index(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);

        $this->actingAs($admin)
            ->get(route('admin.support-cases.index'))
            ->assertOk();
    }

    public function test_admin_can_acknowledge_assign_respond_and_decide(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $customer = User::factory()->create(['role_id' => 3]);

        $case = app(SupportCaseService::class)->create($customer, [
            'type' => SupportCase::TYPE_DISPUTE,
            'subject' => 'Item not as described',
            'description' => 'The condition was worse than listed.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.support-cases.show', $case->id))
            ->assertOk()
            ->assertSee($case->case_number)
            ->assertSee('Item not as described');

        $this->actingAs($admin)
            ->post(route('admin.support-cases.acknowledge', $case->id))
            ->assertRedirect();

        $this->assertSame(SupportCase::STATUS_ACKNOWLEDGED, $case->fresh()->status);

        $this->actingAs($admin)
            ->post(route('admin.support-cases.assign', $case->id), [
                'owner_id' => $admin->id,
            ])
            ->assertRedirect();

        $this->assertSame(SupportCase::STATUS_ASSIGNED, $case->fresh()->status);
        $this->assertSame($admin->id, $case->fresh()->owner_id);

        $this->actingAs($admin)
            ->post(route('admin.support-cases.messages', $case->id), [
                'body' => 'We are reviewing your dispute.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_case_messages', [
            'support_case_id' => $case->id,
            'body' => 'We are reviewing your dispute.',
            'is_internal' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.support-cases.decide', $case->id), [
                'decision' => 'Partial refund approved',
                'status' => SupportCase::STATUS_RESOLVED,
                'financial_outcome' => 12.50,
                'admin_notes' => 'Refund via ledger',
                'message' => 'We have approved a partial refund.',
            ])
            ->assertRedirect();

        $case->refresh();
        $this->assertSame(SupportCase::STATUS_RESOLVED, $case->status);
        $this->assertSame('Partial refund approved', $case->decision);
        $this->assertEquals(12.50, (float) $case->financial_outcome);
        $this->assertNotNull($case->closed_at);
    }

    public function test_non_admin_cannot_access_support_cases(): void
    {
        $user = User::factory()->create(['role_id' => 3]);

        $this->actingAs($user)
            ->get(route('admin.support-cases.index'))
            ->assertRedirect();
    }
}
