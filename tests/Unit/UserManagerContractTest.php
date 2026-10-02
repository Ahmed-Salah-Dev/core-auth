<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\UserManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class UserManagerContractTest extends TestCase
{
    public function test_user_manager_contract_defines_required_methods(): void
    {
        $reflection = new ReflectionClass(UserManagerInterface::class);

        $this->assertTrue($reflection->hasMethod('find'));
        $this->assertTrue($reflection->hasMethod('findBy'));
        $this->assertTrue($reflection->hasMethod('create'));
    }

    public function test_find_accepts_identifier_and_returns_nullable_authenticatable(): void
    {
        $reflection = new ReflectionClass(UserManagerInterface::class);
        $method = $reflection->getMethod('find');

        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('id', $parameters[0]->getName());

        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
        $this->assertSame(
            '?Illuminate\Contracts\Auth\Authenticatable',
            (string) $returnType
        );
    }

    public function test_find_by_accepts_attributes_and_returns_nullable_authenticatable(): void
    {
        $reflection = new ReflectionClass(UserManagerInterface::class);
        $method = $reflection->getMethod('findBy');

        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('attributes', $parameters[0]->getName());

        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertTrue($returnType->allowsNull());
        $this->assertSame(
            '?Illuminate\Contracts\Auth\Authenticatable',
            (string) $returnType
        );
    }

    public function test_create_accepts_attributes_and_returns_authenticatable(): void
    {
        $reflection = new ReflectionClass(UserManagerInterface::class);
        $method = $reflection->getMethod('create');

        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('attributes', $parameters[0]->getName());

        $returnType = $method->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertFalse($returnType->allowsNull());
        $this->assertSame(
            'Illuminate\Contracts\Auth\Authenticatable',
            (string) $returnType
        );
    }
}