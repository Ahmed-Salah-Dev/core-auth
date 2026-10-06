<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\ApiAuthenticationManagerInterface;
use AhmedSalahDev\CoreAuth\Services\ApiAuthenticationManager;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use PHPUnit\Framework\TestCase;

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
            method_exists($manager, 'check')
        );

        $this->assertTrue(
            method_exists($manager, 'user')
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
}