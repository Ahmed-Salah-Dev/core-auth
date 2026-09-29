<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface GuardInterface
{
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array<string, mixed> $credentials
     * @param bool $remember Whether the authenticated session should be remembered.
     */
    public function login(
        array $credentials,
        bool $remember = false
    ): bool;

    /**
     * Log out the currently authenticated user.
     */
    public function logout(): void;

    /**
     * Determine whether the current user is authenticated.
     */
    public function check(): bool;

    /**
     * Retrieve the currently authenticated user.
     */
//    public function user(): mixed;
    public function user(): ?Authenticatable;

}
