<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

interface AuthManagerInterface
{
    public function login(
        string $identifier,
        string $password
    ): bool;

    public function logout(): void;

    public function check(): bool;

    public function user(): mixed;
}