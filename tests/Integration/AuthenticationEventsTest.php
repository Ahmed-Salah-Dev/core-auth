<?php

declare(strict_types=1);

namespace Tests\Integration;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\CoreAuthServiceProvider;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Orchestra\Testbench\TestCase;

final class AuthenticationEventsTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CoreAuthServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.defaults.guard', 'web');

        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        $app['config']->set('auth.providers.users', [
            'driver' => 'test',
        ]);

        $app['auth']->provider('test', function () {
            return new TestUserProvider();
        });
    }

    public function test_successful_login_dispatches_login_event(): void
    {
        $loginEvent = null;

        $this->app['events']->listen(
            Login::class,
            function (Login $event) use (&$loginEvent): void {
                $loginEvent = $event;
            }
        );

        $auth = $this->app->make(AuthManagerInterface::class);

        $result = $auth->login([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->assertTrue($result);
        $this->assertInstanceOf(Login::class, $loginEvent);
        $this->assertSame('web', $loginEvent->guard);
        $this->assertInstanceOf(TestUser::class, $loginEvent->user);
    }

    public function test_failed_login_dispatches_failed_event(): void
    {
        $failedEvent = null;

        $this->app['events']->listen(
            Failed::class,
            function (Failed $event) use (&$failedEvent): void {
                $failedEvent = $event;
            }
        );

        $auth = $this->app->make(AuthManagerInterface::class);

        $credentials = [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ];

        $result = $auth->login($credentials);

        $this->assertFalse($result);
        $this->assertInstanceOf(Failed::class, $failedEvent);
        $this->assertSame('web', $failedEvent->guard);
        $this->assertSame($credentials, $failedEvent->credentials);
    }

    public function test_logout_dispatches_logout_event(): void
    {
        $logoutEvent = null;

        $this->app['events']->listen(
            Logout::class,
            function (Logout $event) use (&$logoutEvent): void {
                $logoutEvent = $event;
            }
        );

        $auth = $this->app->make(AuthManagerInterface::class);

        $auth->login([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $auth->logout();

        $this->assertInstanceOf(Logout::class, $logoutEvent);
        $this->assertSame('web', $logoutEvent->guard);
        $this->assertInstanceOf(TestUser::class, $logoutEvent->user);
    }

    public function test_login_attempt_dispatches_attempting_event(): void
    {
        $eventDispatched = false;

        $this->app['events']->listen(
            Attempting::class,
            function (Attempting $event) use (&$eventDispatched): void {
                $eventDispatched = true;
            }
        );

        $auth = $this->app->make(AuthManagerInterface::class);

        $auth->login([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->assertTrue($eventDispatched);
    }

    public function test_successful_login_dispatches_authenticated_event(): void
    {
        $authenticatedEvent = null;

        $this->app['events']->listen(
            Authenticated::class,
            function (Authenticated $event) use (&$authenticatedEvent): void {
                $authenticatedEvent = $event;
            }
        );

        $auth = $this->app->make(AuthManagerInterface::class);

        $result = $auth->login([
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->assertTrue($result);
        $this->assertInstanceOf(Authenticated::class, $authenticatedEvent);
        $this->assertSame('web', $authenticatedEvent->guard);
        $this->assertInstanceOf(TestUser::class, $authenticatedEvent->user);
    }

    public function test_remember_me_stores_remember_token(): void
    {
        $provider = new TestUserProvider();

        $this->app['auth']->provider('test', function () use ($provider) {
            return $provider;
        });

        $auth = $this->app->make(AuthManagerInterface::class);

        $result = $auth->login([
            'email' => 'user@example.com',
            'password' => 'password',
        ], true);

        $this->assertTrue($result);
        $this->assertNotNull($provider->getLastUser());
        $this->assertNotNull($provider->getLastUser()?->getRememberToken());
    }
}

final class TestUser implements Authenticatable
{
    private ?string $rememberToken = null;

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return 1;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): ?string
    {
        return 'password';
    }

    public function getRememberToken(): ?string
    {
        return $this->rememberToken;
    }

    public function setRememberToken($value): void
    {
        $this->rememberToken = $value;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}

final class TestUserProvider implements UserProvider
{
    private ?TestUser $lastUser = null;

    public function retrieveById($identifier): ?Authenticatable
    {
        return new TestUser();
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(
        Authenticatable $user,
        $token
    ): void {
        $user->setRememberToken($token);
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        if (($credentials['email'] ?? null) !== 'user@example.com') {
            return null;
        }

        $this->lastUser = new TestUser();

        return $this->lastUser;
    }

    public function validateCredentials(
        Authenticatable $user,
        array $credentials
    ): bool {
        return ($credentials['password'] ?? null) === 'password';
    }

    public function rehashPasswordIfRequired(
        Authenticatable $user,
        array $credentials,
        bool $force = false
    ): void {
    }

    public function getLastUser(): ?TestUser
    {
        return $this->lastUser;
    }
}
