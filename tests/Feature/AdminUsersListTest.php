<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminUsersListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // The admin check expects Admin to be role id 1, as in production.
        Role::query()->delete();
        DB::table('roles')->insert([
            ['id' => 1, 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Individual', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->admin = User::factory()->create(['role_id' => 1]);
    }

    private function rows(): array
    {
        return $this->actingAs($this->admin)->getJson('/admin/users/list', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data');
    }

    public function test_every_row_has_the_email_and_phone_the_table_asks_for(): void
    {
        User::factory()->create(['role_id' => 3, 'email' => 'sam@example.com', 'phone' => '+447111000111']);

        $row = collect($this->rows())->firstWhere('email', 'sam@example.com');

        $this->assertNotNull($row, 'email must be present in the row (it is hidden on the model, so it is added explicitly)');
        $this->assertSame('+447111000111', $row['phone']);
        $this->assertSame('<span class="badge bg-primary">Individual</span>', $row['role']);
    }

    public function test_user_typed_values_are_escaped_before_they_reach_the_admin_screen(): void
    {
        User::factory()->create(['role_id' => 3, 'name' => '<script>alert(1)</script>', 'email' => 'x@example.com', 'phone' => '<b>1</b>']);

        $row = collect($this->rows())->firstWhere('email', 'x@example.com');

        $this->assertStringNotContainsString('<script>', $row['name']);
        $this->assertStringContainsString('&lt;script&gt;', $row['name']);
        $this->assertStringNotContainsString('<b>', $row['phone']);
    }
}
