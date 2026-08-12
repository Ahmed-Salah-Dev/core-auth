<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\GuardInterface;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

final class AuthManager implements AuthManagerInterface
{
    /**
     * Create a new authentication manager instance.
     */
    public function __construct(
        private readonly AuthFactory $auth
    ) {
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array<string, mixed> $credentials
     *
     * @return bool True when authentication succeeds, otherwise false.
     */
    public function login(array $credentials): bool
    {
        return $this->auth
            ->guard()
            ->attempt($credentials);
    }

    /**
     * Log out the currently authenticated user.
     */
    public function logout(): void
    {
        $this->auth
            ->guard()
            ->logout();
    }

    /**
     * Determine whether the current user is authenticated.
     *
     * @return bool True when a user is authenticated, otherwise false.
     */
    public function check(): bool
    {
        return $this->auth
            ->guard()
            ->check();
    }

    /**
     * Retrieve the currently authenticated user.
     *
     * @return mixed The authenticated user, or null when no user is authenticated.
     */
    public function user(): mixed
    {
        return $this->auth
            ->guard()
            ->user();
    }

    public function guard(string $name): GuardInterface
    {
        return new AuthGuard(
            $this->auth->guard($name)
        );
    }
}
