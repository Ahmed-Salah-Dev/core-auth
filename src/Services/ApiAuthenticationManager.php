<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\ApiAuthenticationManagerInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;

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

    /**
     * Retrieve the Sanctum authentication guard.
     */
    private function guard(): Guard
    {
        return $this->auth->guard('sanctum');
    }
}