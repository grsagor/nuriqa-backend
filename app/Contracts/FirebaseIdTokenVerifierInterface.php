<?php

namespace App\Contracts;

use App\Exceptions\InvalidFirebaseIdTokenException;

interface FirebaseIdTokenVerifierInterface
{
    /**
     * @return array{
     *     uid: string,
     *     email: ?string,
     *     email_verified: bool,
     *     name: ?string,
     *     sign_in_provider: ?string
     * }
     *
     * @throws InvalidFirebaseIdTokenException
     */
    public function verify(string $idToken): array;
}
