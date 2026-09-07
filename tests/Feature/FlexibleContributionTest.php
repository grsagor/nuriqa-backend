<?php

namespace Tests\Feature;

use App\Models\Cause;
use App\Models\Product;
use App\Models\User;
use App\Services\PlatformFeeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlexibleContributionTest extends TestCase
{
    use RefreshDatabase;

    public function test_flexible_mode_is_detected_and_admin_fee_applies(): void
    {
        $seller = User::factory()->create();
        $cause = Cause::factory()->create();

        $product = Product::query()->create([
            'owner_id' => $seller->id,
            'title' => 'Donated coat',
            'description' => 'd',
            'price' => 0,
            'guide_value' => 25,
            'is_free' => true,
            'contribution_mode' => Product::CONTRIBUTION_FLEXIBLE,
            'delivery_payer' => Product::DELIVERY_PAYER_DONOR,
            'cause_id' => $cause->id,
            'cause_allocation_type' => 'none',
            'type' => 'seller',
            'condition' => 'used',
            'stock' => 1,
            'active_listing' => true,
            'approval_status' => Product::APPROVAL_APPROVED,
            'size_id' => \App\Models\Size::query()->create(['name' => 'M', 'type' => 'general'])->id,
            'category_id' => \App\Models\Category::query()->create(['name' => 'C'])->id,
        ]);

        $this->assertTrue($product->allowsFlexibleContribution());
        $this->assertTrue(PlatformFeeService::isFlexibleContribution($product));
        $this->assertEquals(0.75, PlatformFeeService::platformFeeAmountForSellerSubtotal(0, $product));
        $this->assertSame('donor', $product->delivery_payer);
        $this->assertEquals(25.0, (float) $product->guide_value);
    }

    public function test_causes_index_returns_active_causes(): void
    {
        Cause::factory()->create(['name' => 'Active Cause', 'is_active' => true, 'slug' => 'active-cause']);
        Cause::factory()->create(['name' => 'Inactive', 'is_active' => false, 'slug' => 'inactive-cause']);

        $this->getJson('/api/v1/causes')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }
}
