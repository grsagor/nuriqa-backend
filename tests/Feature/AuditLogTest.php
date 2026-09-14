<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use App\Models\SponsorRequest;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_service_records_entry(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $user = User::factory()->create();
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

        $request = SponsorRequest::query()->create([
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

        app(AuditLogService::class)->record(
            'sponsor_request.approve',
            $request,
            $admin,
            'pending',
            'approved',
            'Looks good',
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sponsor_request.approve',
            'actor_id' => $admin->id,
            'auditable_id' => $request->id,
            'from_status' => 'pending',
            'to_status' => 'approved',
        ]);
        $this->assertSame(1, AuditLog::query()->count());
    }
}
