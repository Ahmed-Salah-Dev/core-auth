<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\UserManagerInterface;
use AhmedSalahDev\CoreAuth\Exceptions\UserException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class UserManager implements UserManagerInterface
{
    /**
     * @param class-string<Model&Authenticatable>|null $modelClass
     *
     * @throws UserException
     */
    public function __construct(
        private readonly ?string $modelClass
    ) {
        if (
            $modelClass === null ||
            ! is_a($modelClass, Model::class, true) ||
            ! is_a($modelClass, Authenticatable::class, true)
        ) {
            throw new UserException(
                'The configured user model must be an Eloquent model that implements Authenticatable.'
            );
        }
    }

    public function find(
        int|string $id
    ): ?Authenticatable {
        return $this->modelClass::query()->find($id);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function findBy(
        array $attributes
    ): ?Authenticatable {
        return $this->modelClass::query()
            ->where($attributes)
            ->first();
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(
        array $attributes
    ): Authenticatable {
        /** @var Model&Authenticatable $user */
        $user = $this->modelClass::query()->create($attributes);

        return $user;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(
        Authenticatable $user,
        array $attributes
    ): Authenticatable {
        /** @var Model&Authenticatable $user */
        $user->update($attributes);

        return $user->refresh();
    }
}
