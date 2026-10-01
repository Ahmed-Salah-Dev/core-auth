<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Services;

use AhmedSalahDev\CoreAuth\Contracts\EmailVerificationManagerInterface;
use AhmedSalahDev\CoreAuth\Exceptions\EmailVerificationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;

final class EmailVerificationManager implements EmailVerificationManagerInterface
{
    /**
     * Create a new email verification manager instance.
     */
    public function __construct(
        private readonly AuthFactory $auth
    ) {
    }

    /**
     * Determine whether the current user's email address has been verified.
     *
     * @throws EmailVerificationException
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->getVerifiableUser()->hasVerifiedEmail();
    }

    /**
     * Send the email verification notification to the current user.
     *
     * @throws EmailVerificationException
     */
    public function sendVerificationNotification(): void
    {
        $user = $this->getVerifiableUser();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
    }

    /**
     * Retrieve the current user that supports email verification.
     *
     * @throws EmailVerificationException
     */
    private function getVerifiableUser(): MustVerifyEmail
    {
        $user = $this->auth->guard()->user();

        if (! $user instanceof MustVerifyEmail) {
            throw new EmailVerificationException(
                'The authenticated user does not support email verification.'
            );
        }

        return $user;
    }
}
