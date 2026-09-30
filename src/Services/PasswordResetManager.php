<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\PasswordResetManagerInterface;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Hashing\Hasher;

final class PasswordResetManager implements PasswordResetManagerInterface
{
    /**
     * Create a new password reset manager instance.
     */
    public function __construct(
        private readonly PasswordBroker $broker,
        private readonly Hasher $hasher
    ) {
    }

    /**
     * Send a password reset link to the user.
     *
     * @param array<string, mixed> $credentials
     *
     * @return bool True when the reset link is sent successfully, otherwise false.
     */
    public function sendResetLink(array $credentials): bool
    {
        $status = $this->broker->sendResetLink($credentials);

        return $status === PasswordBroker::RESET_LINK_SENT;
    }

    /**
     * Reset the user's password using a valid reset token.
     *
     * @param array<string, mixed> $credentials
     *
     * @return bool True when the password is reset successfully, otherwise false.
     */
    public function reset(
        array $credentials,
        string $token,
        string $password
    ): bool {
        $status = $this->broker->reset(
            $credentials,
            function ($user) use ($password): void {
                $user->forceFill([
                    'password' => $this->hasher->make($password),
                ])->save();
            },
            $token
        );

        return $status === PasswordBroker::PASSWORD_RESET;
    }
}