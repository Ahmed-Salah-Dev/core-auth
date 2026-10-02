<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Contracts;

interface AuthorizationManagerInterface
{
    /**
     * Determine if the current user is authorized to perform an ability.
     *
     * @param mixed $ability
     * @param mixed $arguments
     */
    public function allows(
        mixed $ability,
        mixed $arguments = []
    ): bool;

    /**
     * Determine if the current user is not authorized to perform an ability.
     *
     * @param mixed $ability
     * @param mixed $arguments
     */
    public function denies(
        mixed $ability,
        mixed $arguments = []
    ): bool;

    /**
     * Create an authorization manager for a specific user.
     *
     * @param mixed $user
     *
     * @return static
     */
    public function forUser(
        mixed $user
    ): static;

    /**
     * Authorize the current user to perform an ability.
     *
     * @param mixed $ability
     * @param mixed $arguments
     *
     * @throws \AhmedSalahDev\CoreAuth\Exceptions\AuthorizationException
     */
    public function authorize(
        mixed $ability,
        mixed $arguments = []
    ): void;
}