<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\ApiTokenManagerInterface;
use AhmedSalahDev\CoreAuth\Data\ApiTokenResult;
use AhmedSalahDev\CoreAuth\Exceptions\ApiTokenException;
use DateTimeInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

final class ApiTokenManager implements ApiTokenManagerInterface
{
    public function createToken(
        Authenticatable $user,
        string $name,
        array $abilities = [],
        ?DateTimeInterface $expiresAt = null
    ): ApiTokenResult {
        $this->ensureTokenSupport($user);

        $token = $user->createToken(
            $name,
            $abilities,
            $expiresAt
        );

        return new ApiTokenResult(
            id: $token->accessToken->getKey(),
            name: $name,
            token: $token->plainTextToken,
            expiresAt: $expiresAt,
        );
    }

    public function tokens(
        Authenticatable $user
    ): Collection {
        $this->ensureTokenSupport($user);

        return $user->tokens;
    }

    public function revoke(
        Authenticatable $user,
        int|string $tokenId
    ): void {
        $this->ensureTokenSupport($user);

        $user->tokens()
            ->whereKey($tokenId)
            ->delete();
    }

    public function revokeAll(
        Authenticatable $user
    ): void {
        $this->ensureTokenSupport($user);

        $user->tokens()->delete();
    }

    private function ensureTokenSupport(
        Authenticatable $user
    ): void {
        if (! in_array(
            HasApiTokens::class,
            class_uses_recursive($user),
            true
        )) {
            throw new ApiTokenException(
                'The given user must use Laravel Sanctum HasApiTokens trait.'
            );
        }
    }
}
