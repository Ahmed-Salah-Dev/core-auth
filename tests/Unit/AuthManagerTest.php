<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Services\AuthManager;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

final class AuthManagerTest extends TestCase
{
    private MockInterface $auth;

    private AuthManager $authManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = Mockery::mock(AuthFactory::class);

        $this->authManager = new AuthManager(
            $this->auth
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_auth_manager_implements_contract(): void
    {
        $this->assertInstanceOf(
            \AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface::class,
            $this->authManager
        );
    }

    public function test_login_returns_false_when_credentials_are_invalid(): void
    {
        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn(
                Mockery::mock([
                    'attempt' => false,
                ])
            );

        $result = $this->authManager->login(
            'user@example.com',
            'wrong-password'
        );

        $this->assertFalse($result);
    }

    public function test_check_returns_false_when_user_is_not_authenticated(): void
    {
        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn(
                Mockery::mock([
                    'check' => false,
                ])
            );

        $this->assertFalse(
            $this->authManager->check()
        );
    }

    public function test_user_returns_null_when_user_is_not_authenticated(): void
    {
        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn(
                Mockery::mock([
                    'user' => null,
                ])
            );

        $this->assertNull(
            $this->authManager->user()
        );
    }

    public function test_logout_calls_the_auth_guard(): void
    {
        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn(
                Mockery::mock([
                    'logout' => null,
                ])
            );

        $this->authManager->logout();

        $this->assertTrue(true);
    }
}