<?php
//
//declare(strict_types=1);
//
//namespace Tests\Unit;
//
//use AhmedSalahDev\CoreAuth\Services\AuthManager;
//use Illuminate\Contracts\Auth\Factory as AuthFactory;
//use Mockery;
//use Mockery\MockInterface;
//use PHPUnit\Framework\TestCase;
//
//final class AuthManagerTest extends TestCase
//{
//    private MockInterface $auth;
//
//    private AuthManager $authManager;
//
//    protected function setUp(): void
//    {
//        parent::setUp();
//
//        $this->auth = Mockery::mock(AuthFactory::class);
//
//        $this->authManager = new AuthManager(
//            $this->auth
//        );
//    }
//
//    protected function tearDown(): void
//    {
//        Mockery::close();
//
//        parent::tearDown();
//    }
//
//    public function test_auth_manager_implements_contract(): void
//    {
//        $this->assertInstanceOf(
//            \AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface::class,
//            $this->authManager
//        );
//    }
//
//    public function test_login_returns_false_when_credentials_are_invalid(): void
//    {
//        $this->auth
//            ->shouldReceive('guard')
//            ->once()
//            ->andReturn(
//                Mockery::mock([
//                    'attempt' => false,
//                ])
//            );
//
//        $result = $this->authManager->login(
//            'user@example.com',
//            'wrong-password'
//        );
//
//        $this->assertFalse($result);
//    }
//
//    public function test_login_returns_true_when_credentials_are_valid(): void
//    {
//        $guard = Mockery::mock();
//
//        $guard
//            ->shouldReceive('attempt')
//            ->once()
//            ->with([
//                'email' => 'user@example.com',
//                'password' => 'correct-password',
//            ])
//            ->andReturn(true);
//
//        $this->auth
//            ->shouldReceive('guard')
//            ->once()
//            ->andReturn($guard);
//
//        $this->assertTrue(
//            $this->authManager->login(
//                'user@example.com',
//                'correct-password'
//            )
//        );
//    }
//    public function test_login_uses_identifier_as_email(): void
//    {
//        $guard = Mockery::mock();
//
//        $guard
//            ->shouldReceive('attempt')
//            ->once()
//            ->with([
//                'email' => 'user@example.com',
//                'password' => 'correct-password',
//            ])
//            ->andReturn(true);
//
//        $this->auth
//            ->shouldReceive('guard')
//            ->once()
//            ->andReturn($guard);
//
//        $this->assertTrue(
//            $this->authManager->login(
//                'user@example.com',
//                'correct-password'
//            )
//        );
//    }
//
//    public function test_login_returns_false_when_guard_rejects_credentials(): void
//    {
//        $guard = Mockery::mock();
//
//        $guard
//            ->shouldReceive('attempt')
//            ->once()
//            ->with([
//                'email' => 'user@example.com',
//                'password' => 'wrong-password',
//            ])
//            ->andReturn(false);
//
//        $this->auth
//            ->shouldReceive('guard')
//            ->once()
//            ->andReturn($guard);
//
//        $this->assertFalse(
//            $this->authManager->login(
//                'user@example.com',
//                'wrong-password'
//            )
//        );
//    }
//
//    public function test_check_returns_false_when_user_is_not_authenticated(): void
//    {
//        $guard = Mockery::mock();
//
//        $guard
//            ->shouldReceive('check')
//            ->once()
//            ->andReturn(false);
//
//        $this->auth
//            ->shouldReceive('guard')
//            ->once()
//            ->andReturn($guard);
//
//        $this->assertFalse(
//            $this->authManager->check()
//        );
//    }
//
//    public function test_check_returns_true_when_user_is_authenticated(): void
//    {
//        $guard = Mockery::mock();
//
//        $guard
//            ->shouldReceive('check')
//            ->once()
//            ->andReturn(true);
//
//        $this->auth
//            ->shouldReceive('guard')
//            ->once()
//            ->andReturn($guard);
//
//        $this->assertTrue(
//            $this->authManager->check()
//        );
//    }
//    public function test_user_returns_null_when_user_is_not_authenticated(): void
//    {
//        $guard = Mockery::mock();
//
//        $guard
//            ->shouldReceive('user')
//            ->once()
//            ->andReturn(null);
//
//        $this->auth
//            ->shouldReceive('guard')
//            ->once()
//            ->andReturn($guard);
//
//        $this->assertNull(
//            $this->authManager->user()
//        );
//    }
//
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
//    public function test_logout_calls_the_auth_guard(): void
//    {
//        $guard = Mockery::mock();
//
//        $guard
//            ->shouldReceive('logout')
//            ->once();
//
//        $this->auth
//            ->shouldReceive('guard')
//            ->once()
//            ->andReturn($guard);
//
//        $this->authManager->logout();
//
//        $this->assertTrue(true);
//    }
//
//
//}


declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\AuthManagerInterface;
use AhmedSalahDev\CoreAuth\Services\AuthManager;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

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
            $this->authManager->login(
                'user@example.com',
                'correct-password'
            )
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
            $this->authManager->login(
                'user@example.com',
                'wrong-password'
            )
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
    public function test_user_returns_authenticated_user(): void
    {
        $user = new \stdClass();
        $user->id = 1;
        $user->email = 'user@example.com';

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
}
