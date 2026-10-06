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

    /**
     * Determine whether the current API token has the given ability.
     */
    public function tokenCan(string $ability): bool;

    /**
     * Determine whether the current API token does not have the given ability.
     */
    public function tokenCant(string $ability): bool;
}