<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\AuthorizationManagerInterface;
use AhmedSalahDev\CoreAuth\Exceptions\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Throwable;

final class AuthorizationManager implements AuthorizationManagerInterface
{
    public function __construct(
        private readonly Gate $gate
    ) {
    }

    public function allows(
        mixed $ability,
        mixed $arguments = []
    ): bool {
        return $this->gate->allows(
            $ability,
            $arguments
        );
    }

    public function denies(
        mixed $ability,
        mixed $arguments = []
    ): bool {
        return $this->gate->denies(
            $ability,
            $arguments
        );
    }

    /**
     * Determine if the current user is authorized to perform an ability.
     *
     * @param mixed $ability
     * @param mixed $arguments
     */
    public function check(
        mixed $ability,
        mixed $arguments = []
    ): bool {
        return $this->gate->check(
            $ability,
            $arguments
        );
    }

    /**
     * Determine if the current user is authorized to perform any of the given abilities.
     *
     * @param mixed $abilities
     * @param mixed $arguments
     */
    public function any(
        mixed $abilities,
        mixed $arguments = []
    ): bool {
        return $this->gate->any(
            $abilities,
            $arguments
        );
    }

    /**
     * Determine if the current user is not authorized to perform any of the given abilities.
     *
     * @param mixed $abilities
     * @param mixed $arguments
     */
    public function none(
        mixed $abilities,
        mixed $arguments = []
    ): bool {
        return $this->gate->none(
            $abilities,
            $arguments
        );
    }

    /**
     * Get the authorization response for an ability.
     *
     * @param mixed $ability
     * @param mixed $arguments
     *
     * @return \Illuminate\Auth\Access\Response
     */
    public function inspect(
        mixed $ability,
        mixed $arguments = []
    ): Response {
        return $this->gate->inspect(
            $ability,
            $arguments
        );
    }

    /**
     * Create an authorization manager for a specific user.
     *
     * @param mixed $user
     *
     * @return static
     */
    public function forUser(
        mixed $user
    ): static {
        return new static(
            $this->gate->forUser($user)
        );
    }

    public function authorize(
        mixed $ability,
        mixed $arguments = []
    ): void {
        try {
            $this->gate->authorize(
                $ability,
                $arguments
            );
        } catch (Throwable $exception) {
            throw new AuthorizationException(
                $exception->getMessage(),
                (int) $exception->getCode(),
                $exception
            );
        }
    }
}