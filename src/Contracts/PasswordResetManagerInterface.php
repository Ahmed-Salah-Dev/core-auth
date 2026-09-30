<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

interface PasswordResetManagerInterface
{
    /**
     * Send a password reset link to the user.
     *
     * @param array<string, mixed> $credentials
     */
    public function sendResetLink(array $credentials): bool;

    /**
     * Reset the user's password using a valid reset token.
     *
     * @param array<string, mixed> $credentials
     */
    public function reset(
        array $credentials,
        string $token,
        string $password
    ): bool;
}