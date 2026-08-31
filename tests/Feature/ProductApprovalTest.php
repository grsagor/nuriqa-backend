<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ProductApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function createSellerProduct(User $seller, string $approvalStatus = Product::APPROVAL_APPROVED): Product
    {
        $category = Category::query()->create(['name' => 'Men']);
        $size = Size::query()->create(['name' => 'M', 'type' => 'general']);

        return Product::query()->create([
            'owner_id' => $seller->id,
            'title' => 'Silk shirt',
            'description' => 'Black silk shirt',
            'size_id' => $size->id,
            'category_id' => $category->id,
            'condition' => 'used',
            'price' => 150,
            'is_free' => false,
            'platform_donation' => false,
            'donation_percentage' => 0,
            'stock' => 1,
            'type' => 'seller',
            'active_listing' => true,
            'approval_status' => $approvalStatus,
        ]);
    }

    public function test_pending_seller_products_are_hidden_from_public_listing(): void
    {
        $seller = User::factory()->create();
        $this->createSellerProduct($seller, Product::APPROVAL_PENDING);

        $this->getJson('/api/v1/products?type=seller')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_approved_seller_products_are_visible_publicly(): void
    {
        $seller = User::factory()->create();
        $product = $this->createSellerProduct($seller, Product::APPROVAL_APPROVED);

        $this->getJson('/api/v1/products?type=seller')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id);
    }

    public function test_seller_can_view_their_pending_product_via_myproduct(): void
    {
        $seller = User::factory()->create();
        $product = $this->createSellerProduct($seller, Product::APPROVAL_PENDING);
        $token = JWTAuth::fromUser($seller);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/products?myproduct=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.approval_status', Product::APPROVAL_PENDING);
    }

    public function test_seller_product_store_creates_pending_listing(): void
    {
        $seller = User::factory()->create();
        $category = Category::query()->create(['name' => 'Men']);
        $size = Size::query()->create(['name' => 'M', 'type' => 'general']);
        $token = JWTAuth::fromUser($seller);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/products/store', [
                'title' => 'New listing',
                'description' => 'Description',
                'size_id' => $size->id,
                'category_id' => $category->id,
                'condition' => 'used',
                'price' => 120,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('products', [
            'id' => $response->json('product_id'),
            'owner_id' => $seller->id,
            'type' => 'seller',
            'approval_status' => Product::APPROVAL_PENDING,
        ]);
    }

    public function test_admin_can_approve_pending_seller_product(): void
    {
        $seller = User::factory()->create();
        $admin = User::factory()->create(['role_id' => 1]);
        $product = $this->createSellerProduct($seller, Product::APPROVAL_PENDING);

        $this->actingAs($admin)
            ->post(route('admin.products.approve', $product->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'approval_status' => Product::APPROVAL_APPROVED,
        ]);

        $this->getJson('/api/v1/products?type=seller')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_view_all_products_page(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $seller = User::factory()->create();

        $this->createSellerProduct($seller, Product::APPROVAL_PENDING);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('All products', false);
    }

    public function test_admin_list_includes_seller_products(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $seller = User::factory()->create();
        $product = $this->createSellerProduct($seller, Product::APPROVAL_PENDING);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.products.list'), [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $payload = json_encode($response->json());
        $this->assertStringContainsString((string) $product->id, $payload);
        $this->assertStringContainsString('seller', $payload);
    }
}
