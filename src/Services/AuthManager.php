<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

final class AuthManager implements AuthManagerInterface
{
    public function __construct(
        private readonly AuthFactory $auth
    ) {
    }

    public function login(
        string $identifier,
        string $password
    ): bool {
        return $this->auth
            ->guard()
            ->attempt([
                'email' => $identifier,
                'password' => $password,
            ]);
    }

    public function logout(): void
    {
        $this->auth
            ->guard()
            ->logout();
    }

    public function check(): bool
    {
        return $this->auth
            ->guard()
            ->check();
    }

    public function user(): mixed
    {
        return $this->auth
            ->guard()
            ->user();
    }
}
