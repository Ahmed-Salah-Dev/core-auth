<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth;

use AhmedSalahDev\CoreAuth\Contracts\ApiAuthenticationManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\ApiTokenManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\AuthorizationManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\EmailVerificationManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\PasswordResetManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\UserManagerInterface;
use AhmedSalahDev\CoreAuth\Services\ApiAuthenticationManager;
use AhmedSalahDev\CoreAuth\Services\ApiTokenManager;
use AhmedSalahDev\CoreAuth\Services\AuthManager;
use AhmedSalahDev\CoreAuth\Services\AuthorizationManager;
use AhmedSalahDev\CoreAuth\Services\EmailVerificationManager;
use AhmedSalahDev\CoreAuth\Services\PasswordResetManager;
use AhmedSalahDev\CoreAuth\Services\UserManager;
use Illuminate\Support\ServiceProvider;

final class CoreAuthServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/core-auth.php',
            'core-auth'
        );

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

        $this->app->singleton(
            AuthorizationManagerInterface::class,
            AuthorizationManager::class
        );

        $this->app->singleton(
            UserManagerInterface::class,
            fn ($app) => new UserManager(
                $app['config']->get('core-auth.user.model')
            )
        );

        $this->app->singleton(
            ApiTokenManagerInterface::class,
            ApiTokenManager::class
        );

        $this->app->singleton(
            ApiAuthenticationManagerInterface::class,
            ApiAuthenticationManager::class
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