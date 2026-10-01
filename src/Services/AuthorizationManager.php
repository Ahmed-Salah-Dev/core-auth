<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\AuthorizationManagerInterface;
use AhmedSalahDev\CoreAuth\Exceptions\AuthorizationException;
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