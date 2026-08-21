<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Contracts\GuardInterface;
use AhmedSalahDev\CoreAuth\Services\AuthManager;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard as LaravelGuard;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
final class AuthManagerTest extends TestCase
{
    private MockInterface $auth;

    private AuthManager $authManager;

    /**
     * Create the authentication factory mock and AuthManager instance
     * before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->auth = Mockery::mock(AuthFactory::class);

        $this->authManager = new AuthManager(
            $this->auth
        );
    }

    /**
     * Close all Mockery mocks after each test.
     */
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * Verify that AuthManager implements the package authentication contract.
     */
    public function test_auth_manager_implements_contract(): void
    {
        $this->assertInstanceOf(
            AuthManagerInterface::class,
            $this->authManager
        );
    }

    /**
     * Verify that login returns true when valid credentials are accepted
     * by the authentication guard.
     */
    public function test_login_returns_true_when_credentials_are_valid(): void
    {
        $guard = Mockery::mock();

        $guard
            ->shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'user@example.com',
                'password' => 'correct-password',
            ])
            ->andReturn(true);

        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn($guard);

        $this->assertTrue(
            $this->authManager->login([
                'email' => 'user@example.com',
                'password' => 'correct-password',
            ])
        );
    }

    /**
     * Verify that login returns false when the authentication guard
     * rejects the provided credentials.
     */
    public function test_login_returns_false_when_credentials_are_invalid(): void
    {
        $guard = Mockery::mock();

        $guard
            ->shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ])
            ->andReturn(false);

        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn($guard);

        $this->assertFalse(
            $this->authManager->login([
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ])
        );
    }

    /**
     * Verify that login accepts credentials that do not use email
     * as the authentication identifier.
     */
    public function test_login_accepts_non_email_credentials(): void
    {
        $guard = Mockery::mock();

        $guard
            ->shouldReceive('attempt')
            ->once()
            ->with([
                'username' => 'ahmed',
                'password' => 'correct-password',
            ])
            ->andReturn(true);

        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn($guard);

        $this->assertTrue(
            $this->authManager->login([
                'username' => 'ahmed',
                'password' => 'correct-password',
            ])
        );
    }

    /**
     * Verify that check returns true when the user is authenticated.
     */
    public function test_check_returns_true_when_user_is_authenticated(): void
    {
        $guard = Mockery::mock();

        $guard
            ->shouldReceive('check')
            ->once()
            ->andReturn(true);

        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn($guard);

        $this->assertTrue(
            $this->authManager->check()
        );
    }

    /**
     * Verify that check returns false when the user is not authenticated.
     */
    public function test_check_returns_false_when_user_is_not_authenticated(): void
    {
        $guard = Mockery::mock();

        $guard
            ->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn($guard);

        $this->assertFalse(
            $this->authManager->check()
        );
    }

    /**
     * Verify that user returns null when no authenticated user exists.
     */
    public function test_user_returns_null_when_user_is_not_authenticated(): void
    {
        $guard = Mockery::mock();

        $guard
            ->shouldReceive('user')
            ->once()
            ->andReturn(null);

        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn($guard);

        $this->assertNull(
            $this->authManager->user()
        );
    }

    /**
     * Verify that user returns the currently authenticated user.
     */
//    public function test_user_returns_authenticated_user(): void
//    {
//        $user = new \stdClass();
//        $user->id = 1;
//        $user->email = 'user@example.com';
//
//        $guard = Mockery::mock();
//
//        $guard
//            ->shouldReceive('user')
//            ->once()
//            ->andReturn($user);
//
//        $this->auth
//            ->shouldReceive('guard')
//            ->once()
//            ->andReturn($guard);
//
//        $result = $this->authManager->user();
//
//        $this->assertSame($user, $result);
//    }

    public function test_user_returns_authenticated_user(): void
    {
        $user = Mockery::mock(Authenticatable::class);

        $guard = Mockery::mock();

        $guard
            ->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn($guard);

        $result = $this->authManager->user();

        $this->assertSame($user, $result);
    }
    /**
     * Verify that logout calls the logout method on the authentication guard.
     */
    public function test_logout_calls_the_auth_guard(): void
    {
        $guard = Mockery::mock();

        $guard
            ->shouldReceive('logout')
            ->once();

        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->andReturn($guard);

        $this->authManager->logout();

        $this->assertTrue(true);
    }

    public function test_guard_returns_auth_guard_for_the_given_name(): void
    {
        $guard = Mockery::mock(LaravelGuard::class);

        $this->auth
            ->shouldReceive('guard')
            ->once()
            ->with('api')
            ->andReturn($guard);

        $result = $this->authManager->guard('api');

        $this->assertInstanceOf(
            GuardInterface::class,
            $result
        );
    }
}