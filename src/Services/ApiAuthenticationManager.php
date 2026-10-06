<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\ApiAuthenticationManagerInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Laravel\Sanctum\HasApiTokens;

final class ApiAuthenticationManager implements ApiAuthenticationManagerInterface
{
    /**
     * Create a new API authentication manager instance.
     */
    public function __construct(
        private readonly AuthFactory $auth
    ) {
    }

    /**
     * Determine whether the current API request is authenticated.
     */
    public function check(): bool
    {
        return $this->guard()->check();
    }

    /**
     * Retrieve the currently authenticated API user.
     */
    public function user(): ?Authenticatable
    {
        return $this->guard()->user();
    }

    public function tokenCan(string $ability): bool
    {
        $user = $this->user();

        if (
            ! $user ||
            ! in_array(HasApiTokens::class, class_uses_recursive($user), true)
        ) {
            return false;
        }

        return $user->tokenCan($ability);
    }

    public function tokenCant(string $ability): bool
    {
        return ! $this->tokenCan($ability);
    }

    /**
     * Retrieve the Sanctum authentication guard.
     */
    private function guard(): Guard
    {
        return $this->auth->guard('sanctum');
    }
}