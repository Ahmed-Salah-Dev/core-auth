<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface UserManagerInterface
{
    /**
     * Retrieve a user by their unique identifier.
     */
    public function find(
        int|string $id
    ): ?Authenticatable;

    /**
     * Retrieve a user by the given attributes.
     *
     * @param array<string, mixed> $attributes
     */
    public function findBy(
        array $attributes
    ): ?Authenticatable;

    /**
     * Create a new user using the given attributes.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(
        array $attributes
    ): Authenticatable;

    /**
     * Update an existing user using the given attributes.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(
        Authenticatable $user,
        array $attributes
    ): Authenticatable;
}
