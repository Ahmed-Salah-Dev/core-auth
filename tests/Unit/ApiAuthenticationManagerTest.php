<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\ApiAuthenticationManagerInterface;
use AhmedSalahDev\CoreAuth\Services\ApiAuthenticationManager;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Tests\Fixtures\User;
final class ApiAuthenticationManagerTest extends TestCase
{
    public function test_it_implements_the_api_authentication_manager_contract(): void
    {
        $auth = $this->createMock(AuthFactory::class);

        $manager = new ApiAuthenticationManager($auth);

        $this->assertInstanceOf(
            ApiAuthenticationManagerInterface::class,
            $manager
        );
    }

    public function test_it_exposes_the_expected_public_methods(): void
    {
        $auth = $this->createMock(AuthFactory::class);

        $manager = new ApiAuthenticationManager($auth);

        $this->assertTrue(
            method_exists($manager, 'tokenCan')
        );

        $this->assertTrue(
            method_exists($manager, 'tokenCant')
        );
    }

    public function test_check_uses_the_sanctum_guard(): void
    {
        $guard = $this->createMock(Guard::class);

        $guard->expects($this->once())
            ->method('check')
            ->willReturn(true);

        $auth = $this->createMock(AuthFactory::class);

        $auth->expects($this->once())
            ->method('guard')
            ->with('sanctum')
            ->willReturn($guard);

        $manager = new ApiAuthenticationManager($auth);

        $this->assertTrue($manager->check());
    }

    public function test_user_uses_the_sanctum_guard(): void
    {
        $guard = $this->createMock(Guard::class);

        $user = new class implements \Illuminate\Contracts\Auth\Authenticatable {
            public function getAuthIdentifierName(): string
            {
                return 'id';
            }

            public function getAuthIdentifier(): mixed
            {
                return 1;
            }

            public function getAuthPasswordName(): string
            {
                return 'password';
            }

            public function getAuthPassword(): string
            {
                return '';
            }

            public function getRememberToken(): string
            {
                return '';
            }

            public function setRememberToken($value): void
            {
            }

            public function getRememberTokenName(): string
            {
                return 'remember_token';
            }
        };

        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);

        $auth->expects($this->once())
            ->method('guard')
            ->with('sanctum')
            ->willReturn($guard);

        $manager = new ApiAuthenticationManager($auth);

        $this->assertSame($user, $manager->user());
    }

    public function test_token_can_returns_true_when_the_current_token_has_the_ability(): void
    {
        $user = new User();

        $user->withAccessToken(
            new \Laravel\Sanctum\PersonalAccessToken([
                'abilities' => ['users:read'],
            ])
        );

        $guard = $this->createMock(Guard::class);

        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);

        $auth->expects($this->once())
            ->method('guard')
            ->with('sanctum')
            ->willReturn($guard);

        $manager = new ApiAuthenticationManager($auth);

        $this->assertTrue(
            $manager->tokenCan('users:read')
        );
    }

    public function test_token_can_returns_false_when_the_current_token_does_not_have_the_ability(): void
    {
        $user = new User();

        $user->withAccessToken(
            new \Laravel\Sanctum\PersonalAccessToken([
                'abilities' => ['users:read'],
            ])
        );

        $guard = $this->createMock(Guard::class);

        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);

        $auth->expects($this->once())
            ->method('guard')
            ->with('sanctum')
            ->willReturn($guard);

        $manager = new ApiAuthenticationManager($auth);

        $this->assertFalse(
            $manager->tokenCan('users:delete')
        );
    }

    public function test_token_cant_returns_true_when_the_current_token_does_not_have_the_ability(): void
    {
        $user = new User();

        $user->withAccessToken(
            new \Laravel\Sanctum\PersonalAccessToken([
                'abilities' => ['users:read'],
            ])
        );

        $guard = $this->createMock(Guard::class);

        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);

        $auth->expects($this->once())
            ->method('guard')
            ->with('sanctum')
            ->willReturn($guard);

        $manager = new ApiAuthenticationManager($auth);

        $this->assertTrue(
            $manager->tokenCant('users:delete')
        );
    }

    public function test_token_cant_returns_false_when_the_current_token_has_the_ability(): void
    {
        $user = new User();

        $user->withAccessToken(
            new \Laravel\Sanctum\PersonalAccessToken([
                'abilities' => ['users:read'],
            ])
        );

        $guard = $this->createMock(Guard::class);

        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);

        $auth->expects($this->once())
            ->method('guard')
            ->with('sanctum')
            ->willReturn($guard);

        $manager = new ApiAuthenticationManager($auth);

        $this->assertFalse(
            $manager->tokenCant('users:read')
        );
    }
}