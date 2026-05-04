<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard.index'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_admin_can_view_dashboard_with_summary_cards(): void
    {
        $admin = User::factory()->create([
            'role_id' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.index'))
            ->assertOk()
            ->assertSee('Total orders', false)
            ->assertSee('Total revenue', false)
            ->assertSee('Donation generated', false)
            ->assertSee('Active sellers', false)
            ->assertSee('Active products', false)
            ->assertSee('Pending withdrawals', false)
            ->assertSee('Revenue vs donation', false)
            ->assertSee('Order type mix', false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard.index', ['range' => 7]))
            ->assertOk()
            ->assertSee('dashboardRevenueDonationChart', false);
    }
}
