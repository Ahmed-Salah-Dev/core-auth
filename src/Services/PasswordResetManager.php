<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\PasswordResetManagerInterface;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Hashing\Hasher;
final class PasswordResetManager implements PasswordResetManagerInterface
{
    public function __construct(
        private readonly PasswordBroker $broker,
        private readonly Hasher $hasher
    ) {
    }

    public function sendResetLink(array $credentials): bool
    {
        $status = $this->broker->sendResetLink($credentials);

        return $status === PasswordBroker::RESET_LINK_SENT;
    }

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