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
    }
}