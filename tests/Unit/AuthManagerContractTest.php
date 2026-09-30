<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AuthManagerContractTest extends TestCase
{
    public function test_auth_manager_contract_defines_required_methods(): void
    {
        $reflection = new ReflectionClass(AuthManagerInterface::class);

        $this->assertTrue($reflection->hasMethod('login'));
        $this->assertTrue($reflection->hasMethod('logout'));
        $this->assertTrue($reflection->hasMethod('check'));
        $this->assertTrue($reflection->hasMethod('user'));
        $this->assertTrue($reflection->hasMethod('guard'));
    }

    public function test_login_accepts_credentials_and_remember_option(): void
    {
        $reflection = new ReflectionClass(AuthManagerInterface::class);
        $method = $reflection->getMethod('login');
        $parameters = $method->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertSame('credentials', $parameters[0]->getName());
        $this->assertSame('remember', $parameters[1]->getName());
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertFalse($parameters[1]->getDefaultValue());
    }
}