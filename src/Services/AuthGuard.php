<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\GuardInterface;
use Illuminate\Contracts\Auth\Guard as LaravelGuard;
use Illuminate\Contracts\Auth\Authenticatable;
final class AuthGuard implements GuardInterface
{
    /**
     * Create a new authentication guard instance.
     */
    public function __construct(
        private readonly LaravelGuard $guard
    ) {
    }

    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param array<string, mixed> $credentials
     */
    public function login(array $credentials): bool
    {
        return $this->guard->attempt($credentials);
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
//    public function user(): mixed
//
//    {
//        return $this->guard->user();
//    }

    /**
     * Retrieve the currently authenticated user.
     *
     * @return Authenticatable|null
     */
    public function user(): ?Authenticatable
    {
        return $this->guard->user();
    }

}
