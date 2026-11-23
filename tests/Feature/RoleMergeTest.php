<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMergeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // These tests build their own legacy roles; drop the Trader role that migrations create.
        Role::query()->delete();
    }

    private function runMergeMigration(): void
    {
        $migration = require database_path('migrations/2026_11_23_090000_merge_seller_and_customer_roles_into_individual.php');
        $migration->up();
    }

    /** @return array{admin: Role, seller: Role, customer: Role} */
    private function legacyRoles(): array
    {
        return [
            'admin' => Role::query()->create(['name' => 'Admin']),
            'seller' => Role::query()->create(['name' => 'Seller']),
            'customer' => Role::query()->create(['name' => 'Customer']),
        ];
    }

    public function test_sellers_and_customers_become_individuals_and_admins_are_untouched(): void
    {
        $roles = $this->legacyRoles();
        $admin = User::factory()->create(['role_id' => $roles['admin']->id]);
        $seller = User::factory()->create(['role_id' => $roles['seller']->id]);
        $customer = User::factory()->create(['role_id' => $roles['customer']->id]);

        $this->runMergeMigration();

        $this->assertEqualsCanonicalizing(['Admin', 'Individual'], Role::query()->pluck('name')->all());
        $individualId = Role::query()->where('name', 'Individual')->value('id');

        // The Customer role is kept (same id) and renamed; the Seller role is gone.
        $this->assertSame($roles['customer']->id, $individualId);
        $this->assertSame($individualId, $seller->fresh()->role_id);
        $this->assertSame($individualId, $customer->fresh()->role_id);
        $this->assertSame($roles['admin']->id, $admin->fresh()->role_id);
    }

    public function test_running_the_merge_twice_changes_nothing_more(): void
    {
        $roles = $this->legacyRoles();
        $seller = User::factory()->create(['role_id' => $roles['seller']->id]);

        $this->runMergeMigration();
        $this->runMergeMigration();

        $this->assertSame(2, Role::query()->count());
        $this->assertSame(Role::query()->where('name', 'Individual')->value('id'), $seller->fresh()->role_id);
    }

    public function test_merge_does_nothing_when_there_are_no_such_roles(): void
    {
        Role::query()->create(['name' => 'Admin']);

        $this->runMergeMigration();

        $this->assertSame(['Admin'], Role::query()->pluck('name')->all());
    }

    public function test_new_accounts_get_the_individual_role(): void
    {
        $this->legacyRoles();
        $this->runMergeMigration();

        $this->assertSame('Individual', Role::defaultForNewAccounts()?->name);
    }

    public function test_default_role_still_works_before_the_merge_has_run(): void
    {
        Role::query()->create(['name' => 'Admin']);
        Role::query()->create(['name' => 'Seller']);
        $customer = Role::query()->create(['name' => 'Customer']);

        $this->assertSame($customer->id, Role::defaultForNewAccounts()?->id);
    }

    public function test_signup_assigns_the_individual_role(): void
    {
        $this->legacyRoles();
        $this->runMergeMigration();

        $this->postJson('/api/v1/signup', [
            'name' => 'Test Person',
            'email' => 'person@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone_country_code' => '+44',
            'phone_number' => '7123456789',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'person@example.com')->firstOrFail();
        $this->assertSame('Individual', $user->role?->name);
    }
}
