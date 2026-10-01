<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\EmailVerificationManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EmailVerificationManagerContractTest extends TestCase
{
    public function test_email_verification_manager_contract_defines_required_methods(): void
    {
        $reflection = new ReflectionClass(EmailVerificationManagerInterface::class);

        $this->assertTrue($reflection->hasMethod('hasVerifiedEmail'));
        $this->assertTrue($reflection->hasMethod('sendVerificationNotification'));
    }
}
