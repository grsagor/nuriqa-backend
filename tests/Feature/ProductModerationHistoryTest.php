<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductApprovalHistory;
use App\Models\Role;
use App\Models\Size;
use App\Models\User;
use App\Services\ProductModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModerationHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderation_transition_writes_history(): void
    {
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $seller = User::factory()->create();
        $category = Category::query()->create(['name' => 'Cat']);
        $size = Size::query()->create(['name' => 'M', 'type' => 'general']);

        $product = Product::query()->create([
            'owner_id' => $seller->id,
            'title' => 'Needs review',
            'description' => 'd',
            'size_id' => $size->id,
            'category_id' => $category->id,
            'condition' => 'used',
            'price' => 12,
            'type' => 'seller',
            'approval_status' => Product::APPROVAL_PENDING,
            'active_listing' => true,
            'stock' => 1,
        ]);

        $service = app(ProductModerationService::class);
        $service->transition(
            $product,
            Product::APPROVAL_RETURNED,
            'return_for_correction',
            $admin,
            'Please add clearer photos.',
        );

        $this->assertDatabaseHas('product_approval_histories', [
            'product_id' => $product->id,
            'to_status' => Product::APPROVAL_RETURNED,
            'action' => 'return_for_correction',
        ]);

        $this->assertSame(Product::APPROVAL_RETURNED, $product->fresh()->approval_status);
        $this->assertSame('Please add clearer photos.', $product->fresh()->moderation_message);
        $this->assertSame(1, ProductApprovalHistory::query()->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'product.return_for_correction',
            'auditable_id' => $product->id,
            'to_status' => Product::APPROVAL_RETURNED,
        ]);
    }
}
