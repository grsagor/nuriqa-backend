<?php

namespace App\Services\Firebase;

use App\Contracts\FirebaseIdTokenVerifierInterface;
use App\Exceptions\InvalidFirebaseIdTokenException;
use InvalidArgumentException;
use Kreait\Firebase\JWT\Error\IdTokenVerificationFailed;
use Kreait\Firebase\JWT\IdTokenVerifier;
use RuntimeException;
use Throwable;

class KreaitFirebaseIdTokenVerifier implements FirebaseIdTokenVerifierInterface
{
    /**
     * @return array{
     *     uid: string,
     *     email: ?string,
     *     email_verified: bool,
     *     name: ?string,
     *     sign_in_provider: ?string
     * }
     */
    public function verify(string $idToken): array
    {
        $projectId = config('services.firebase.project_id');

        if (! is_string($projectId) || $projectId === '') {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        }

        try {
            $token = IdTokenVerifier::createWithProjectId($projectId)->verifyIdToken($idToken);
        } catch (IdTokenVerificationFailed|InvalidArgumentException $e) {
            throw new InvalidFirebaseIdTokenException($e->getMessage(), previous: $e);
        } catch (Throwable $e) {
            throw new InvalidFirebaseIdTokenException('Unable to verify Firebase ID token.', previous: $e);
        }

        $payload = $token->payload();
        $uid = $payload['sub'] ?? null;

        if (! is_string($uid) || $uid === '') {
            throw new InvalidFirebaseIdTokenException('Firebase ID token is missing a subject.');
        }

        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? null;
        $firebaseClaim = $payload['firebase'] ?? null;
        $signInProvider = null;

        if (is_array($firebaseClaim) && isset($firebaseClaim['sign_in_provider']) && is_string($firebaseClaim['sign_in_provider'])) {
            $signInProvider = $firebaseClaim['sign_in_provider'];
        }

        return [
            'uid' => $uid,
            'email' => is_string($email) && $email !== '' ? $email : null,
            'email_verified' => (bool) ($payload['email_verified'] ?? false),
            'name' => is_string($name) && $name !== '' ? $name : null,
            'sign_in_provider' => $signInProvider,
        ];
    }
}
