<?php

namespace Tests\Feature;

use App\Contracts\FirebaseIdTokenVerifierInterface;
use App\Exceptions\InvalidFirebaseIdTokenException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array{
     *     uid: string,
     *     email: ?string,
     *     email_verified: bool,
     *     name: ?string,
     *     sign_in_provider: ?string
     * }|null  $claims
     */
    private function fakeFirebaseVerifier(?array $claims = null, bool $invalid = false): void
    {
        $this->app->instance(FirebaseIdTokenVerifierInterface::class, new class($claims, $invalid) implements FirebaseIdTokenVerifierInterface
        {
            /**
             * @param  array{
             *     uid: string,
             *     email: ?string,
             *     email_verified: bool,
             *     name: ?string,
             *     sign_in_provider: ?string
             * }|null  $claims
             */
            public function __construct(
                private ?array $claims,
                private bool $invalid,
            ) {}

            public function verify(string $idToken): array
            {
                if ($this->invalid) {
                    throw new InvalidFirebaseIdTokenException('bad token');
                }

                return $this->claims ?? [
                    'uid' => 'firebase-uid-1',
                    'email' => 'social@example.com',
                    'email_verified' => true,
                    'name' => 'Social User',
                    'sign_in_provider' => 'google.com',
                ];
            }
        });
    }

    public function test_social_login_creates_user_and_returns_jwt(): void
    {
        $this->fakeFirebaseVerifier();

        $response = $this->postJson('/api/v1/auth/social', [
            'id_token' => 'fake-firebase-token',
            'provider' => 'google',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'social@example.com')
            ->assertJsonStructure([
                'data' => ['token', 'expires_in', 'token_type', 'user' => ['id', 'email', 'name']],
            ]);

        $this->assertSame('Bearer', $response->json('data.token_type'));
        $this->assertDatabaseHas('users', [
            'email' => 'social@example.com',
            'firebase_uid' => 'firebase-uid-1',
        ]);
    }

    public function test_social_login_links_existing_email_account(): void
    {
        $existing = User::factory()->create([
            'email' => 'linked@example.com',
            'name' => 'Existing User',
            'firebase_uid' => null,
        ]);

        $this->fakeFirebaseVerifier([
            'uid' => 'firebase-uid-link',
            'email' => 'linked@example.com',
            'email_verified' => true,
            'name' => 'From Google',
            'sign_in_provider' => 'google.com',
        ]);

        $this->postJson('/api/v1/auth/social', [
            'id_token' => 'fake-firebase-token',
            'provider' => 'google',
        ])->assertOk()
            ->assertJsonPath('data.user.id', $existing->id)
            ->assertJsonPath('data.user.email', 'linked@example.com');

        $this->assertSame('firebase-uid-link', $existing->fresh()->firebase_uid);
        $this->assertSame(1, User::query()->where('email', 'linked@example.com')->count());
    }

    public function test_social_login_rejects_invalid_token(): void
    {
        $this->fakeFirebaseVerifier(invalid: true);

        $this->postJson('/api/v1/auth/social', [
            'id_token' => 'bad-token',
        ])->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    public function test_social_login_requires_email(): void
    {
        $this->fakeFirebaseVerifier([
            'uid' => 'new-uid-no-email',
            'email' => null,
            'email_verified' => false,
            'name' => null,
            'sign_in_provider' => 'apple.com',
        ]);

        $this->postJson('/api/v1/auth/social', [
            'id_token' => 'fake-firebase-token',
            'provider' => 'apple',
        ])->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
