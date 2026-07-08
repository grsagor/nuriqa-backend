<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Size;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPaginationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Size $size;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::query()->create(['name' => 'Cat']);
        $this->size = Size::query()->create(['name' => 'M', 'type' => 'general']);
        $this->owner = User::factory()->create();
    }

    private function createListedProduct(int $index): Product
    {
        return Product::query()->create([
            'owner_id' => $this->owner->id,
            'title' => "Product {$index}",
            'description' => 'Description',
            'size_id' => $this->size->id,
            'category_id' => $this->category->id,
            'condition' => 'new',
            'price' => 100 + $index,
            'is_free' => false,
            'platform_donation' => false,
            'donation_percentage' => 0,
            'stock' => 5,
            'type' => 'seller',
            'active_listing' => true,
        ]);
    }

    public function test_legacy_request_without_page_returns_flat_data_array(): void
    {
        $this->createListedProduct(1);
        $this->createListedProduct(2);
        $this->createListedProduct(3);

        $response = $this->getJson('/api/v1/products?type=seller');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing(['pagination']);
    }

    public function test_legacy_limit_without_page_caps_results(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createListedProduct($i);
        }

        $response = $this->getJson('/api/v1/products?type=seller&limit=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonMissing(['pagination']);
    }

    public function test_paginated_request_returns_pagination_metadata(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->createListedProduct($i);
        }

        $response = $this->getJson('/api/v1/products?type=seller&page=1&per_page=2');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 3)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.total', 5);
    }

    public function test_paginated_request_uses_limit_as_per_page_when_per_page_missing(): void
    {
        for ($i = 1; $i <= 4; $i++) {
            $this->createListedProduct($i);
        }

        $response = $this->getJson('/api/v1/products?type=seller&page=2&limit=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.current_page', 2)
            ->assertJsonPath('pagination.per_page', 2)
            ->assertJsonPath('pagination.total', 4);
    }
}
