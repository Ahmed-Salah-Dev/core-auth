<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

use AhmedSalahDev\CoreAuth\Data\ApiTokenResult;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;

interface ApiTokenManagerInterface
{
    /**
     * Create a new API token for the given user.
     *
     * @param array<int, string> $abilities
     */
    public function createToken(
        Authenticatable $user,
        string $name,
        array $abilities = [],
        ?DateTimeInterface $expiresAt = null
    ): ApiTokenResult;

    /**
     * Retrieve all API tokens belonging to the given user.
     *
     * @return Collection<int, object>
     */
    public function tokens(
        Authenticatable $user
    ): Collection;

    /**
     * Revoke a specific API token belonging to the given user.
     */
    public function revoke(
        Authenticatable $user,
        int|string $tokenId
    ): void;

    /**
     * Revoke all API tokens belonging to the given user.
     */
    public function revokeAll(
        Authenticatable $user
    ): void;
}
