<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Tests\Unit;

use AhmedSalahDev\CoreAuth\Contracts\AuthorizationManagerInterface;
use AhmedSalahDev\CoreAuth\Exceptions\AuthorizationException;
use AhmedSalahDev\CoreAuth\Services\AuthorizationManager;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuthorizationManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_implements_authorization_manager_contract(): void
    {
        $gate = Mockery::mock(Gate::class);

        $manager = new AuthorizationManager($gate);

        $this->assertInstanceOf(
            AuthorizationManagerInterface::class,
            $manager
        );
    }

    public function test_allows_returns_true_when_gate_allows_ability(): void
    {
        $gate = Mockery::mock(Gate::class);

        $gate
            ->shouldReceive('allows')
            ->once()
            ->with('update', [])
            ->andReturnTrue();

        $manager = new AuthorizationManager($gate);

        $this->assertTrue(
            $manager->allows('update')
        );
    }

    public function test_allows_returns_false_when_gate_denies_ability(): void
    {
        $gate = Mockery::mock(Gate::class);

        $gate
            ->shouldReceive('allows')
            ->once()
            ->with('update', [])
            ->andReturnFalse();

        $manager = new AuthorizationManager($gate);

        $this->assertFalse(
            $manager->allows('update')
        );
    }

    public function test_denies_returns_true_when_gate_denies_ability(): void
    {
        $gate = Mockery::mock(Gate::class);

        $gate
            ->shouldReceive('denies')
            ->once()
            ->with('delete', [])
            ->andReturnTrue();

        $manager = new AuthorizationManager($gate);

        $this->assertTrue(
            $manager->denies('delete')
        );
    }

    public function test_denies_returns_false_when_gate_allows_ability(): void
    {
        $gate = Mockery::mock(Gate::class);

        $gate
            ->shouldReceive('denies')
            ->once()
            ->with('delete', [])
            ->andReturnFalse();

        $manager = new AuthorizationManager($gate);

        $this->assertFalse(
            $manager->denies('delete')
        );
    }

    public function test_authorize_succeeds_when_gate_authorizes_ability(): void
    {
        $gate = Mockery::mock(Gate::class);

        $gate
            ->shouldReceive('authorize')
            ->once()
            ->with('update', [])
            ->andReturn(
                Response::allow()
            );

        $manager = new AuthorizationManager($gate);

        $manager->authorize('update');

        $this->expectNotToPerformAssertions();
    }

    public function test_for_user_returns_new_authorization_manager(): void
    {
        $gate = Mockery::mock(Gate::class);
        $user = new \stdClass();

        $userGate = Mockery::mock(Gate::class);

        $gate
            ->shouldReceive('forUser')
            ->once()
            ->with($user)
            ->andReturn($userGate);

        $manager = new AuthorizationManager($gate);

        $userManager = $manager->forUser($user);

        $this->assertInstanceOf(
            AuthorizationManager::class,
            $userManager
        );

        $this->assertNotSame(
            $manager,
            $userManager
        );
    }

    public function test_for_user_uses_user_specific_gate_for_authorization(): void
    {
        $gate = Mockery::mock(Gate::class);
        $userGate = Mockery::mock(Gate::class);
        $user = new \stdClass();

        $gate
            ->shouldReceive('forUser')
            ->once()
            ->with($user)
            ->andReturn($userGate);

        $userGate
            ->shouldReceive('allows')
            ->once()
            ->with('update', [])
            ->andReturnTrue();

        $manager = new AuthorizationManager($gate);

        $this->assertTrue(
            $manager
                ->forUser($user)
                ->allows('update')
        );
    }
    public function test_authorize_wraps_unexpected_exception(): void
    {
        $gate = Mockery::mock(Gate::class);

        $originalException = new RuntimeException(
            'Authorization service failed.',
            500
        );

        $gate
            ->shouldReceive('authorize')
            ->once()
            ->with('update', [])
            ->andThrow($originalException);

        $manager = new AuthorizationManager($gate);

        try {
            $manager->authorize('update');

            $this->fail(
                'AuthorizationException was not thrown.'
            );
        } catch (AuthorizationException $exception) {
            $this->assertSame(
                'Authorization service failed.',
                $exception->getMessage()
            );

            $this->assertSame(
                500,
                $exception->getCode()
            );

            $this->assertSame(
                $originalException,
                $exception->getPrevious()
            );
        }
    }
    public function test_authorization_arguments_are_forwarded_to_gate(): void
    {
        $gate = Mockery::mock(Gate::class);

        $post = new \stdClass();

        $gate
            ->shouldReceive('allows')
            ->once()
            ->with('update', $post)
            ->andReturnTrue();

        $manager = new AuthorizationManager($gate);

        $this->assertTrue(
            $manager->allows('update', $post)
        );
    }
}