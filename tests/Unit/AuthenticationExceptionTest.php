<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Exceptions\AuthenticationException;
use AhmedSalahDev\CoreAuth\Exceptions\CoreAuthException;
use PHPUnit\Framework\TestCase;

final class AuthenticationExceptionTest extends TestCase
{
    public function test_authentication_exception_extends_core_auth_exception(): void
    {
        $exception = new AuthenticationException();

        $this->assertInstanceOf(
            CoreAuthException::class,
            $exception
        );
    }

    public function test_authentication_exception_can_have_a_custom_message(): void
    {
        $message = 'Authentication failed.';

        $exception = new AuthenticationException($message);

        $this->assertSame(
            $message,
            $exception->getMessage()
        );
    }
}