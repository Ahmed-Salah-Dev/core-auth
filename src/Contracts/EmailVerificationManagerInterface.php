<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

interface EmailVerificationManagerInterface
{
    /**
     * Determine whether the current user's email address has been verified.
     */
    public function hasVerifiedEmail(): bool;

    /**
     * Send the email verification notification to the current user.
     */
    public function sendVerificationNotification(): void;
}
