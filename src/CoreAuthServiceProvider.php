<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\PasswordResetManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\EmailVerificationManagerInterface;
use AhmedSalahDev\CoreAuth\Services\AuthManager;
use AhmedSalahDev\CoreAuth\Services\PasswordResetManager;
use AhmedSalahDev\CoreAuth\Services\EmailVerificationManager;
use Illuminate\Support\ServiceProvider;

final class CoreAuthServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->app->singleton(
            AuthManagerInterface::class,
            AuthManager::class
        );

        $this->app->singleton(
            PasswordResetManagerInterface::class,
            PasswordResetManager::class
        );

        $this->app->singleton(
            EmailVerificationManagerInterface::class,
            EmailVerificationManager::class
        );
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        //
    }
}
