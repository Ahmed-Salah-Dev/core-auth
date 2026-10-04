<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\AuthorizationManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\EmailVerificationManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\PasswordResetManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\ApiTokenManagerInterface;
use AhmedSalahDev\CoreAuth\CoreAuthServiceProvider;
use AhmedSalahDev\CoreAuth\Services\AuthManager;
use AhmedSalahDev\CoreAuth\Services\AuthorizationManager;
use AhmedSalahDev\CoreAuth\Services\EmailVerificationManager;
use AhmedSalahDev\CoreAuth\Services\PasswordResetManager;
use AhmedSalahDev\CoreAuth\Services\ApiTokenManager;
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

    public function test_authorization_manager_is_bound_to_container(): void
    {
        $authorizationManager = $this->app->make(
            AuthorizationManagerInterface::class
        );

        $this->assertInstanceOf(
            AuthorizationManager::class,
            $authorizationManager
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

    public function test_email_verification_manager_is_bound_to_container(): void
    {
        $emailVerificationManager = $this->app->make(
            EmailVerificationManagerInterface::class
        );

        $this->assertInstanceOf(
            EmailVerificationManager::class,
            $emailVerificationManager
        );
    }

    public function test_email_verification_manager_is_registered_as_singleton(): void
    {
        $first = $this->app->make(
            EmailVerificationManagerInterface::class
        );

        $second = $this->app->make(
            EmailVerificationManagerInterface::class
        );

        $this->assertSame($first, $second);
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

    public function test_authorization_manager_is_registered_as_singleton(): void
    {
        $first = $this->app->make(
            AuthorizationManagerInterface::class
        );

        $second = $this->app->make(
            AuthorizationManagerInterface::class
        );

        $this->assertSame($first, $second);
    }

    public function test_api_token_manager_is_registered_as_singleton(): void
    {
        $first = $this->app->make(
            ApiTokenManagerInterface::class
        );

        $second = $this->app->make(
            ApiTokenManagerInterface::class
        );

        $this->assertSame($first, $second);
    }

    public function test_api_token_manager_is_bound_to_container(): void
    {
        $apiTokenManager = $this->app->make(
            ApiTokenManagerInterface::class
        );

        $this->assertInstanceOf(
            ApiTokenManager::class,
            $apiTokenManager
        );
    }

    public function test_core_auth_configuration_is_loaded(): void
    {
        $this->assertSame(
            [
                'model' => null,
            ],
            config('core-auth.user')
        );
    }

    public function test_user_manager_is_resolved_from_the_container(): void
    {
        config()->set(
            'core-auth.user.model',
            \Tests\Fixtures\User::class
        );

        $manager = $this->app->make(
            \AhmedSalahDev\CoreAuth\Contracts\UserManagerInterface::class
        );

        $this->assertInstanceOf(
            \AhmedSalahDev\CoreAuth\Services\UserManager::class,
            $manager
        );
    }

    public function test_user_manager_is_registered_as_a_singleton(): void
    {
        config()->set(
            'core-auth.user.model',
            \Tests\Fixtures\User::class
        );

        $first = $this->app->make(
            \AhmedSalahDev\CoreAuth\Contracts\UserManagerInterface::class
        );

        $second = $this->app->make(
            \AhmedSalahDev\CoreAuth\Contracts\UserManagerInterface::class
        );

        $this->assertSame($first, $second);
    }
}
