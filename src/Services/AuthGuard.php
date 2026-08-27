<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\GuardInterface;
use AhmedSalahDev\CoreAuth\Exceptions\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard as LaravelGuard;
use Throwable;
final class AuthGuard implements GuardInterface
{
    /**
     * Create a new authentication guard adapter.
     */
    public function __construct(
        private readonly LaravelGuard $guard
    ) {
    }
    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array<string, mixed> $credentials
     *
     * @throws AuthenticationException
     */
    public function login(array $credentials): bool
    {
        try {
            return $this->guard->attempt($credentials);
        } catch (Throwable $exception) {
            throw new AuthenticationException(
                $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Log out the currently authenticated user.
     */
    public function logout(): void
    {
        $this->guard->logout();
    }

    /**
     * Determine whether the current user is authenticated.
     */
    public function check(): bool
    {
        return $this->guard->check();
    }

    /**
     * Retrieve the currently authenticated user.
     */
    public function user(): ?Authenticatable
    {
        return $this->guard->user();
    }
}
