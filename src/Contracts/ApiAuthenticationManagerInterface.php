<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface ApiAuthenticationManagerInterface
{
    /**
     * Determine whether the current API request is authenticated.
     */
    public function check(): bool;

    /**
     * Retrieve the currently authenticated API user.
     */
    public function user(): ?Authenticatable;
}