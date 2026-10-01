<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Services\EmailVerificationManager;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use PHPUnit\Framework\TestCase;

final class EmailVerificationManagerTest extends TestCase
{
    public function test_has_verified_email_returns_false_for_unverified_user(): void
    {
        $user = $this->createMock(MustVerifyEmail::class);
        $user->expects($this->once())
            ->method('hasVerifiedEmail')
            ->willReturn(false);

        $guard = $this->createMock(Guard::class);
        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);
        $auth->expects($this->once())
            ->method('guard')
            ->with(null)
            ->willReturn($guard);

        $manager = new EmailVerificationManager($auth);

        $this->assertFalse($manager->hasVerifiedEmail());
    }

    public function test_has_verified_email_returns_true_for_verified_user(): void
    {
        $user = $this->createMock(MustVerifyEmail::class);
        $user->expects($this->once())
            ->method('hasVerifiedEmail')
            ->willReturn(true);

        $guard = $this->createMock(Guard::class);
        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);
        $auth->expects($this->once())
            ->method('guard')
            ->with(null)
            ->willReturn($guard);

        $manager = new EmailVerificationManager($auth);

        $this->assertTrue($manager->hasVerifiedEmail());
    }

    public function test_send_verification_notification_sends_notification_for_unverified_user(): void
    {
        $user = $this->createMock(MustVerifyEmail::class);
        $user->expects($this->once())
            ->method('hasVerifiedEmail')
            ->willReturn(false);
        $user->expects($this->once())
            ->method('sendEmailVerificationNotification');

        $guard = $this->createMock(Guard::class);
        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);
        $auth->expects($this->once())
            ->method('guard')
            ->with(null)
            ->willReturn($guard);

        $manager = new EmailVerificationManager($auth);

        $manager->sendVerificationNotification();
    }

    public function test_send_verification_notification_does_not_send_for_verified_user(): void
    {
        $user = $this->createMock(MustVerifyEmail::class);
        $user->expects($this->once())
            ->method('hasVerifiedEmail')
            ->willReturn(true);
        $user->expects($this->never())
            ->method('sendEmailVerificationNotification');

        $guard = $this->createMock(Guard::class);
        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);
        $auth->expects($this->once())
            ->method('guard')
            ->with(null)
            ->willReturn($guard);

        $manager = new EmailVerificationManager($auth);

        $manager->sendVerificationNotification();
    }

    public function test_has_verified_email_throws_exception_for_unsupported_user(): void
    {
        $user = new \stdClass();

        $guard = $this->createMock(Guard::class);
        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);
        $auth->expects($this->once())
            ->method('guard')
            ->with(null)
            ->willReturn($guard);

        $manager = new EmailVerificationManager($auth);

        $this->expectException(\AhmedSalahDev\CoreAuth\Exceptions\EmailVerificationException::class);

        $manager->hasVerifiedEmail();
    }

    public function test_send_verification_notification_throws_exception_for_unsupported_user(): void
    {
        $user = new \stdClass();

        $guard = $this->createMock(Guard::class);
        $guard->expects($this->once())
            ->method('user')
            ->willReturn($user);

        $auth = $this->createMock(AuthFactory::class);
        $auth->expects($this->once())
            ->method('guard')
            ->with(null)
            ->willReturn($guard);

        $manager = new EmailVerificationManager($auth);

        $this->expectException(\AhmedSalahDev\CoreAuth\Exceptions\EmailVerificationException::class);

        $manager->sendVerificationNotification();
    }
}