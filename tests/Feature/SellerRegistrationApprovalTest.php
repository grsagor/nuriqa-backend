<?php

namespace Tests\Feature;

use Tests\TestCase;

class SellerRegistrationApprovalTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        // Root redirects to the admin dashboard (same behaviour as ExampleTest).
        $response->assertRedirect(route('admin.dashboard.index'));
    }
}
