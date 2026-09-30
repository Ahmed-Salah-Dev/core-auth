<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\PasswordResetManagerInterface;
use AhmedSalahDev\CoreAuth\CoreAuthServiceProvider;
use AhmedSalahDev\CoreAuth\Services\AuthManager;
use AhmedSalahDev\CoreAuth\Services\PasswordResetManager;
use Orchestra\Testbench\TestCase;

final class CoreAuthServiceProviderTest extends TestCase
{

    protected function getPackageProviders($app): array
    {
        return [
            CoreAuthServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(
                random_bytes(32)
            ));
    }

    public function test_service_provider_is_registered(): void
    {
        $this->assertTrue(
            $this->app->getProvider(CoreAuthServiceProvider::class) !== null
        );
    }

    public function test_auth_manager_is_bound_to_container(): void
    {
        $authManager = $this->app->make(AuthManagerInterface::class);

        $this->assertInstanceOf(
            AuthManager::class,
            $authManager
        );
    }

    public function test_password_reset_manager_is_bound_to_container(): void
    {
        $passwordResetManager = $this->app->make(
            PasswordResetManagerInterface::class
        );

        $this->assertInstanceOf(
            PasswordResetManager::class,
            $passwordResetManager
        );
    }

    public function test_auth_manager_is_registered_as_singleton(): void
    {
        $first = $this->app->make(AuthManagerInterface::class);
        $second = $this->app->make(AuthManagerInterface::class);

        $this->assertSame($first, $second);
    }

    public function test_password_reset_manager_is_registered_as_singleton(): void
    {
        $first = $this->app->make(
            PasswordResetManagerInterface::class
        );

        $second = $this->app->make(
            PasswordResetManagerInterface::class
        );

        $this->assertSame($first, $second);
    }
}