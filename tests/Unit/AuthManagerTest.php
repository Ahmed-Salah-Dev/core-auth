<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Services\AuthManager;
use PHPUnit\Framework\TestCase;

final class AuthManagerTest extends TestCase
{
    private AuthManager $authManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authManager = new AuthManager();
    }

    public function test_auth_manager_implements_contract(): void
    {
        $this->assertInstanceOf(
            AuthManagerInterface::class,
            $this->authManager
        );
    }

    public function test_login_returns_false_by_default(): void
    {
        $this->assertFalse(
            $this->authManager->login('user@example.com', 'password')
        );
    }

    public function test_check_returns_false_when_not_authenticated(): void
    {
        $this->assertFalse(
            $this->authManager->check()
        );
    }

    public function test_user_returns_null_when_not_authenticated(): void
    {
        $this->assertNull(
            $this->authManager->user()
        );
    }

    public function test_logout_can_be_called(): void
    {
        $this->authManager->logout();

        $this->assertTrue(true);
    }
}