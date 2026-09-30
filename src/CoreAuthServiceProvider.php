<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\PasswordResetManagerInterface;
use AhmedSalahDev\CoreAuth\Services\AuthManager;
use AhmedSalahDev\CoreAuth\Services\PasswordResetManager;
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
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        //
    }
}