<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;

final class AuthManager implements AuthManagerInterface
{
    public function login(
        string $identifier,
        string $password
    ): bool {
        return false;
    }

    public function logout(): void
    {
        //
    }

    public function check(): bool
    {
        return false;
    }

    public function user(): mixed
    {
        return null;
    }
}