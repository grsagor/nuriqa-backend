<?php

namespace Tests\Feature;

use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterSubscribeTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_creates_subscriber(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'News@Example.com',
            'locale' => 'en',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'already_subscribed' => false,
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'news@example.com',
            'locale' => 'en',
        ]);

        Mail::assertSent(NewsletterWelcomeMail::class);
    }

    public function test_subscribe_with_duplicate_email_returns_already_subscribed(): void
    {
        Mail::fake();

        NewsletterSubscriber::factory()->create([
            'email' => 'existing@example.com',
            'locale' => 'en',
        ]);

        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'Existing@Example.com',
            'locale' => 'ar',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'already_subscribed' => true,
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'existing@example.com',
            'locale' => 'ar',
        ]);

        Mail::assertNotSent(NewsletterWelcomeMail::class);
    }

    public function test_subscribe_validates_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/newsletter/subscribe', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseCount('newsletter_subscribers', 0);
        Mail::assertNothingSent();
    }
}
