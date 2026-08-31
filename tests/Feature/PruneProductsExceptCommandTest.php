<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneProductsExceptCommandTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Size $size;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::query()->create(['name' => 'Men']);
        $this->size = Size::query()->create(['name' => 'M', 'type' => 'general']);
        $this->owner = User::factory()->create();
    }

    private function createProduct(string $title, string $type = 'seller'): Product
    {
        return Product::query()->create([
            'owner_id' => $this->owner->id,
            'title' => $title,
            'description' => 'Description',
            'size_id' => $this->size->id,
            'category_id' => $this->category->id,
            'condition' => 'used',
            'price' => 100,
            'is_free' => false,
            'platform_donation' => false,
            'donation_percentage' => 0,
            'stock' => 1,
            'type' => $type,
            'active_listing' => true,
        ]);
    }

    public function test_it_deletes_all_products_except_the_one_specified_by_id(): void
    {
        $keep = $this->createProduct('D&G silk shirt');
        $this->createProduct('Other seller item', 'seller');
        $this->createProduct('Merch item', 'merchandise');
        $this->createProduct('Hajra item', 'hajra');

        $this->artisan('products:prune-except', [
            '--keep-id' => $keep->id,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('products', ['id' => $keep->id, 'title' => 'D&G silk shirt']);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_it_finds_product_to_keep_by_title(): void
    {
        $keep = $this->createProduct('D&G silk shirt');
        $this->createProduct('Delete me');

        $this->artisan('products:prune-except', [
            '--keep-title' => 'D&G silk',
            '--force' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('products', ['id' => $keep->id]);
        $this->assertDatabaseCount('products', 1);
    }

    public function test_it_fails_when_keep_product_is_not_found(): void
    {
        $this->artisan('products:prune-except', [
            '--keep-id' => 999,
        ])->assertFailed();
    }
}
