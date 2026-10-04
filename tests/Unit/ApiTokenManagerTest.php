<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\ApiTokenManagerInterface;
use AhmedSalahDev\CoreAuth\Exceptions\ApiTokenException;
use AhmedSalahDev\CoreAuth\Services\ApiTokenManager;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

final class ApiTokenManagerTest extends TestCase
{
    public function test_it_implements_the_api_token_manager_contract(): void
    {
        $manager = new ApiTokenManager();

        $this->assertInstanceOf(
            ApiTokenManagerInterface::class,
            $manager
        );
    }

    public function test_it_exposes_the_expected_public_methods(): void
    {
        $manager = new ApiTokenManager();

        $this->assertTrue(
            method_exists($manager, 'createToken')
        );

        $this->assertTrue(
            method_exists($manager, 'tokens')
        );

        $this->assertTrue(
            method_exists($manager, 'revoke')
        );

        $this->assertTrue(
            method_exists($manager, 'revokeAll')
        );
    }

    public function test_it_rejects_a_user_without_sanctum_support(): void
    {
        $manager = new ApiTokenManager();

        $user = new UnsupportedUser();

        $this->expectException(ApiTokenException::class);
        $this->expectExceptionMessage(
            'The given user must use Laravel Sanctum HasApiTokens trait.'
        );

        $manager->tokens($user);
    }

    public function test_it_rejects_an_unsupported_user_when_creating_a_token(): void
    {
        $manager = new ApiTokenManager();

        $user = new UnsupportedUser();

        $this->expectException(ApiTokenException::class);

        $manager->createToken(
            $user,
            'mobile-app'
        );
    }

    public function test_it_rejects_an_unsupported_user_when_revoking_a_token(): void
    {
        $manager = new ApiTokenManager();

        $user = new UnsupportedUser();

        $this->expectException(ApiTokenException::class);

        $manager->revoke(
            $user,
            1
        );
    }

    public function test_it_rejects_an_unsupported_user_when_revoking_all_tokens(): void
    {
        $manager = new ApiTokenManager();

        $user = new UnsupportedUser();

        $this->expectException(ApiTokenException::class);

        $manager->revokeAll($user);
    }
}

final class UnsupportedUser extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}
