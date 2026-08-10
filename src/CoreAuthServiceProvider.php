<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Services\AuthManager;
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
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        //
    }
}