<?php

namespace App\Services;

use App\Contracts\FirebaseIdTokenVerifierInterface;
use App\Exceptions\InvalidFirebaseIdTokenException;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use RuntimeException;

class SocialAuthService
{
    public function __construct(
        private FirebaseIdTokenVerifierInterface $tokenVerifier,
    ) {}

    /**
     * Verify the Firebase (Google/Apple) ID token, then load the app user from the database by email.
     *
     * @return array{
     *     user: User,
     *     token: string,
     *     expires_in: int,
     *     token_type: string
     * }
     *
     * @throws InvalidFirebaseIdTokenException
     * @throws RuntimeException
     */
    public function authenticateWithIdToken(string $idToken): array
    {
        $claims = $this->tokenVerifier->verify($idToken);

        if ($claims['email'] === null || $claims['email'] === '') {
            throw new RuntimeException('A verified email is required to sign in with Google or Apple.');
        }

        $user = $this->resolveUserByEmail($claims);
        $token = JWTAuth::fromUser($user);

        return [
            'user' => $user->loadMissing('role'),
            'token' => $token,
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * @param  array{
     *     uid: string,
     *     email: string,
     *     email_verified: bool,
     *     name: ?string,
     *     sign_in_provider: ?string
     * }  $claims
     */
    private function resolveUserByEmail(array $claims): User
    {
        $user = User::query()->where('email', $claims['email'])->first();

        if ($user) {
            if ($user->firebase_uid !== null && $user->firebase_uid !== $claims['uid']) {
                throw new RuntimeException('This email is already linked to another social account.');
            }

            $updates = [];

            if ($user->firebase_uid === null) {
                $updates['firebase_uid'] = $claims['uid'];
            }

            if ($user->email_verified_at === null && $claims['email_verified']) {
                $updates['email_verified_at'] = now();
            }

            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }

            return $user->fresh() ?? $user;
        }

        $byUid = User::query()->where('firebase_uid', $claims['uid'])->first();
        if ($byUid) {
            return $byUid;
        }

        return $this->createUser($claims);
    }

    /**
     * @param  array{
     *     uid: string,
     *     email: string,
     *     email_verified: bool,
     *     name: ?string,
     *     sign_in_provider: ?string
     * }  $claims
     */
    private function createUser(array $claims): User
    {
        $defaultRole = Role::where('name', 'user')->first()
            ?? Role::where('name', 'customer')->first()
            ?? Role::where('name', 'seller')->first();

        return User::query()->create([
            'name' => $claims['name'] ?? Str::before($claims['email'], '@'),
            'email' => $claims['email'],
            'firebase_uid' => $claims['uid'],
            'password' => Hash::make(Str::random(32)),
            'phone' => null,
            'role_id' => $defaultRole?->id,
            'email_verified_at' => $claims['email_verified'] ? now() : null,
            'signup_date' => now(),
        ]);
    }
}
