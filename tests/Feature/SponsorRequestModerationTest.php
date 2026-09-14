<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use App\Models\SponsorRequest;
use App\Models\User;
use App\Services\SponsorRequestModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SponsorRequestModerationTest extends TestCase
{
    use RefreshDatabase;

    private function makeRequest(User $user): SponsorRequest
    {
        $category = Category::query()->create(['name' => 'Cat']);
        $size = Size::query()->create(['name' => 'M', 'type' => 'general']);
        $product = Product::query()->create([
            'owner_id' => $user->id,
            'title' => 'Item',
            'description' => 'd',
            'size_id' => $size->id,
            'category_id' => $category->id,
            'condition' => 'used',
            'price' => 0,
            'type' => 'seller',
            'approval_status' => Product::APPROVAL_APPROVED,
            'active_listing' => true,
            'stock' => 1,
            'is_free' => true,
        ]);

        return SponsorRequest::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'first_name' => 'A',
            'last_name' => 'B',
            'email' => 'a@example.com',
            'phone' => '123',
            'address' => '1 Street',
            'city' => 'London',
            'postal_code' => 'E1 1AA',
            'request_reason' => 'Need clothing',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_return_and_reject_with_message(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create(['role_id' => 3]);
        $request = $this->makeRequest($user);

        $this->actingAs($admin)
            ->post(route('admin.sponsor-requests.return', $request->id), [
                'message' => 'Please clarify the shipping address.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $request->refresh();
        $this->assertSame('returned', $request->status);
        $this->assertSame('Please clarify the shipping address.', $request->moderation_message);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sponsor_request.return_for_correction',
            'auditable_id' => $request->id,
            'to_status' => 'returned',
        ]);

        $request->update(['status' => 'pending']);

        $this->actingAs($admin)
            ->post(route('admin.sponsor-requests.reject', $request->id), [
                'message' => 'Not eligible for sponsorship.',
            ])
            ->assertOk();

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Not eligible for sponsorship.', $request->fresh()->moderation_message);
    }

    public function test_moderation_service_approve_writes_audit(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create();
        $request = $this->makeRequest($user);

        app(SponsorRequestModerationService::class)->approve($request, $admin, 'Approved');

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sponsor_request.approve',
            'to_status' => 'approved',
        ]);
    }
}
