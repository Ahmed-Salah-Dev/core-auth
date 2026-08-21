<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Exceptions\CoreAuthException;
use PHPUnit\Framework\TestCase;

final class CoreAuthExceptionTest extends TestCase
{
    public function test_core_auth_exception_extends_runtime_exception(): void
    {
        $exception = new class extends CoreAuthException {
        };

        $this->assertInstanceOf(
            \RuntimeException::class,
            $exception
        );
    }

    public function test_core_auth_exception_can_be_caught_by_base_type(): void
    {
        $exception = new class extends CoreAuthException {
        };

        try {
            throw $exception;
        } catch (CoreAuthException $caught) {
            $this->assertSame($exception, $caught);
        }
    }
}