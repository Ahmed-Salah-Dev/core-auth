<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\GuardInterface;
use AhmedSalahDev\CoreAuth\Exceptions\AuthenticationException;
use AhmedSalahDev\CoreAuth\Services\AuthGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard as LaravelGuard;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
final class AuthGuardTest extends TestCase
{
    private MockInterface $guard;

    private AuthGuard $authGuard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guard = Mockery::mock(LaravelGuard::class);

        $this->authGuard = new AuthGuard(
            $this->guard
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_auth_guard_implements_contract(): void
    {
        $this->assertInstanceOf(
            GuardInterface::class,
            $this->authGuard
        );
    }

    public function test_login_returns_true_when_credentials_are_valid(): void
    {
        $credentials = [
            'email' => 'user@example.com',
            'password' => 'correct-password',
        ];

        $this->guard
            ->shouldReceive('attempt')
            ->once()
            ->with($credentials)
            ->andReturn(true);

        $this->assertTrue(
            $this->authGuard->login($credentials)
        );
    }

    public function test_login_returns_false_when_credentials_are_invalid(): void
    {
        $credentials = [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ];

        $this->guard
            ->shouldReceive('attempt')
            ->once()
            ->with($credentials)
            ->andReturn(false);

        $this->assertFalse(
            $this->authGuard->login($credentials)
        );
    }

    public function test_logout_calls_the_guard_logout_method(): void
    {
        $this->guard
            ->shouldReceive('logout')
            ->once();

        $this->authGuard->logout();

        $this->assertTrue(true);
    }

    public function test_check_returns_true_when_user_is_authenticated(): void
    {
        $this->guard
            ->shouldReceive('check')
            ->once()
            ->andReturn(true);

        $this->assertTrue(
            $this->authGuard->check()
        );
    }

    public function test_check_returns_false_when_user_is_not_authenticated(): void
    {
        $this->guard
            ->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $this->assertFalse(
            $this->authGuard->check()
        );
    }

    public function test_user_returns_authenticated_user(): void
    {
        $user = Mockery::mock(Authenticatable::class);

        $this->guard
            ->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $this->assertSame(
            $user,
            $this->authGuard->user()
        );
    }

    public function test_user_returns_null_when_user_is_not_authenticated(): void
    {
        $this->guard
            ->shouldReceive('user')
            ->once()
            ->andReturn(null);

        $this->assertNull(
            $this->authGuard->user()
        );
    }

    public function test_login_throws_authentication_exception_when_guard_fails_unexpectedly(): void
    {
        $credentials = [
            'email' => 'user@example.com',
            'password' => 'correct-password',
        ];

        $this->guard
            ->shouldReceive('attempt')
            ->once()
            ->with($credentials)
            ->andThrow(
                new RuntimeException('Unexpected authentication failure.')
            );

        $this->expectException(
            AuthenticationException::class
        );

        $this->expectExceptionMessage(
            'Unexpected authentication failure.'
        );

        $this->authGuard->login($credentials);
    }

    public function test_login_preserves_the_original_exception(): void
    {
        $credentials = [
            'email' => 'user@example.com',
            'password' => 'correct-password',
        ];

        $originalException = new \RuntimeException(
            'Unexpected authentication failure.'
        );

        $this->guard
            ->shouldReceive('attempt')
            ->once()
            ->with($credentials)
            ->andThrow($originalException);

        try {
            $this->authGuard->login($credentials);

            $this->fail('AuthenticationException was not thrown.');
        } catch (AuthenticationException $exception) {
            $this->assertSame(
                $originalException,
                $exception->getPrevious()
            );
        }
    }
}