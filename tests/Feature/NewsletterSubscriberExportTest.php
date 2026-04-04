<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterSubscriberExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_csv_redirects_guests_to_login(): void
    {
        $this->get(route('admin.newsletter-subscribers.export-csv'))
            ->assertRedirect(route('auth.login'));
    }

    public function test_export_csv_streams_all_subscribers_for_admin(): void
    {
        $admin = User::factory()->create(['role_id' => 1]);

        NewsletterSubscriber::factory()->create([
            'email' => 'one@example.com',
            'locale' => 'en',
        ]);
        NewsletterSubscriber::factory()->create([
            'email' => 'two@example.com',
            'locale' => 'bn',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.newsletter-subscribers.export-csv'));

        $response->assertOk();
        $response->assertHeader('content-disposition');

        $content = $response->streamedContent();
        $this->assertStringContainsString('id,email,locale,subscribed_at', $content);
        $this->assertStringContainsString('one@example.com', $content);
        $this->assertStringContainsString('two@example.com', $content);
        $this->assertStringContainsString('en', $content);
        $this->assertStringContainsString('bn', $content);
    }
}
