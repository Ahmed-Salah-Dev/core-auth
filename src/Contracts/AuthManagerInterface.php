<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

use AhmedSalahDev\CoreAuth\Exceptions\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;

interface AuthManagerInterface
{
    /**
     * Attempt to authenticate a user using the default guard.
     *
     * @param array<string, mixed> $credentials
     * @param bool $remember Whether the authenticated session should be remembered.
     *
     * @throws AuthenticationException
     */
    public function login(
        array $credentials,
        bool $remember = false
    ): bool;

    /**
     * Log out the currently authenticated user using the default guard.
     */
    public function logout(): void;

    /**
     * Determine whether the current user is authenticated using the default guard.
     */
    public function check(): bool;

    /**
     * Retrieve the currently authenticated user using the default guard.
     */
    public function user(): ?Authenticatable;

    /**
     * Retrieve an authentication guard by name.
     */
    public function guard(string $name): GuardInterface;
}
