<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

interface AuthManagerInterface
{
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array<string, mixed> $credentials
     */
    public function login(array $credentials): bool;

    public function logout(): void;

    public function check(): bool;

    public function user(): mixed;
}