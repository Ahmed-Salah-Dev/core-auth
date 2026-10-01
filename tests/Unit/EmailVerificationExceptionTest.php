<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Exceptions\EmailVerificationException;
use PHPUnit\Framework\TestCase;

final class EmailVerificationExceptionTest extends TestCase
{
    public function test_email_verification_exception_extends_core_auth_exception(): void
    {
        $exception = new EmailVerificationException('Email verification failed.');

        $this->assertInstanceOf(
            \AhmedSalahDev\CoreAuth\Exceptions\CoreAuthException::class,
            $exception
        );
    }

    public function test_email_verification_exception_preserves_message(): void
    {
        $message = 'Email verification failed.';

        $exception = new EmailVerificationException($message);

        $this->assertSame($message, $exception->getMessage());
    }
}
