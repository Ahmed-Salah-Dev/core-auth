<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

interface GuardInterface
{
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array<string, mixed> $credentials
     */
    public function login(array $credentials): bool;

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
    public function user(): mixed;
}
