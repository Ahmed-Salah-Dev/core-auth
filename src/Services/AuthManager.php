<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\GuardInterface;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Authenticatable;

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
     * @param bool $remember Whether the authenticated session should be remembered.
     *
     * @return bool True when authentication succeeds, otherwise false.
     */
    public function login(
        array $credentials,
        bool $remember = false
    ): bool {
        return $this->defaultGuard()->login(
            $credentials,
            $remember
        );
    }

    /**
     * Log out the currently authenticated user.
     */
    public function logout(): void
    {
        $this->defaultGuard()->logout();
    }

    /**
     * Determine whether the current user is authenticated.
     *
     * @return bool True when a user is authenticated, otherwise false.
     */
    public function check(): bool
    {
        return $this->defaultGuard()->check();
    }

    /**
     * Retrieve the currently authenticated user using the default guard.
     *
     * @return Authenticatable|null
     */
    public function user(): ?Authenticatable
    {
        return $this->defaultGuard()->user();
    }

    /**
     * Retrieve an authentication guard by name.
     */
    public function guard(string $name): GuardInterface
    {
        return new AuthGuard(
            $this->auth->guard($name)
        );
    }

    /**
     * Retrieve the default authentication guard.
     */
    private function defaultGuard(): GuardInterface
    {
        return new AuthGuard(
            $this->auth->guard()
        );
    }
}
