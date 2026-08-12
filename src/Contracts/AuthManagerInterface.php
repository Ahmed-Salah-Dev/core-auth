<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

interface AuthManagerInterface
{
    /**
     * Attempt to authenticate a user using the default guard.
     *
     * @param array<string, mixed> $credentials
     */
    public function login(array $credentials): bool;

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
    public function user(): mixed;

    /**
     * Retrieve an authentication guard by name.
     */
    public function guard(string $name): GuardInterface;
}
