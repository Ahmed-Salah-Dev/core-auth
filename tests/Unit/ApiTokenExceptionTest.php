<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Exceptions\ApiTokenException;
use AhmedSalahDev\CoreAuth\Exceptions\CoreAuthException;
use PHPUnit\Framework\TestCase;

final class ApiTokenExceptionTest extends TestCase
{
    public function test_it_extends_core_auth_exception(): void
    {
        $exception = new ApiTokenException('API token error.');

        $this->assertInstanceOf(
            CoreAuthException::class,
            $exception
        );
    }

    public function test_it_preserves_the_exception_message(): void
    {
        $exception = new ApiTokenException('API token error.');

        $this->assertSame(
            'API token error.',
            $exception->getMessage()
        );
    }
}
