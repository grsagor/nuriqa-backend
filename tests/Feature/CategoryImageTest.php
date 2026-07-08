<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CategoryImageTest extends TestCase
{
    use RefreshDatabase;

    private function fakeImage(string $name = 'category.jpg'): UploadedFile
    {
        $directory = storage_path('framework/testing/disks/category-images');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory.'/'.uniqid('category_image_', true).'.jpg';
        file_put_contents($path, base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k='
        ));

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    public function test_admin_can_create_category_with_image(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Men',
            'image' => $this->fakeImage(),
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $category = Category::query()->where('name', 'Men')->first();

        $this->assertNotNull($category);
        $this->assertNotNull($category->image);
        $this->assertStringStartsWith('uploads/categories/', $category->image);
        $this->assertFileExists(public_path($category->image));
    }

    public function test_admin_can_update_category_image(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $category = Category::query()->create(['name' => 'Women']);
        $oldImagePath = ImageService::upload($this->fakeImage('old.jpg'), 'categories');
        $category->update(['image' => $oldImagePath]);

        $response = $this->actingAs($admin)->post(route('admin.categories.update', $category), [
            '_method' => 'PUT',
            'id' => $category->id,
            'name' => 'Women',
            'image' => $this->fakeImage('new.jpg'),
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $category->refresh();

        $this->assertNotSame($oldImagePath, $category->image);
        $this->assertFileDoesNotExist(public_path($oldImagePath));
        $this->assertFileExists(public_path($category->image));
    }

    public function test_admin_can_remove_category_image(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $category = Category::query()->create(['name' => 'Kids']);
        $imagePath = ImageService::upload($this->fakeImage('kids.jpg'), 'categories');
        $category->update(['image' => $imagePath]);

        $response = $this->actingAs($admin)->post(route('admin.categories.update', $category), [
            '_method' => 'PUT',
            'id' => $category->id,
            'name' => 'Kids',
            'remove_image' => '1',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $category->refresh();

        $this->assertNull($category->image);
        $this->assertFileDoesNotExist(public_path($imagePath));
    }

    public function test_admin_delete_removes_category_image_file(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);
        $category = Category::query()->create(['name' => 'Sports']);
        $imagePath = ImageService::upload($this->fakeImage('sports.jpg'), 'categories');
        $category->update(['image' => $imagePath]);

        $response = $this->actingAs($admin)->delete(route('admin.categories.delete', $category));

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertFileDoesNotExist(public_path($imagePath));
    }

    public function test_categories_api_includes_image_url(): void
    {
        $imagePath = ImageService::upload($this->fakeImage('api.jpg'), 'categories');
        Category::query()->create([
            'name' => 'API Category',
            'image' => $imagePath,
        ]);

        $response = $this->getJson('/api/v1/products/categories');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    [
                        'id',
                        'name',
                        'image',
                        'image_url',
                    ],
                ],
            ]);
    }
}
