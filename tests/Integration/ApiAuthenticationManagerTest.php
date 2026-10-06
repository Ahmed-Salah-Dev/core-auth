<?php

declare(strict_types=1);

namespace Tests\Integration;

use AhmedSalahDev\CoreAuth\Services\ApiAuthenticationManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\User;

final class ApiAuthenticationManagerTest extends TestCase
{
    private ApiAuthenticationManager $apiAuthenticationManager;

    protected function getPackageProviders($app): array
    {
        return [
            SanctumServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        $this->apiAuthenticationManager = app(
            ApiAuthenticationManager::class
        );
    }

    public function test_it_returns_the_authenticated_user_from_a_sanctum_guard(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        Sanctum::actingAs($user);

        $this->assertSame(
            $user->getKey(),
            $this->apiAuthenticationManager->user()?->getAuthIdentifier()
        );
    }

    public function test_it_returns_true_when_the_request_is_authenticated(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        Sanctum::actingAs($user);

        $this->assertTrue(
            $this->apiAuthenticationManager->check()
        );
    }

    public function test_it_returns_true_when_the_current_token_has_the_ability(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        Sanctum::actingAs($user, ['users:read']);

        $this->assertTrue(
            $this->apiAuthenticationManager->tokenCan('users:read')
        );
    }

    public function test_it_returns_false_when_the_current_token_does_not_have_the_ability(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        Sanctum::actingAs($user, ['users:read']);

        $this->assertFalse(
            $this->apiAuthenticationManager->tokenCan('users:delete')
        );
    }

    public function test_it_returns_true_when_the_current_token_cannot_perform_the_ability(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        Sanctum::actingAs($user, ['users:read']);

        $this->assertTrue(
            $this->apiAuthenticationManager->tokenCant('users:delete')
        );
    }

    public function test_it_returns_false_when_the_current_token_can_perform_the_ability(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        Sanctum::actingAs($user, ['users:read']);

        $this->assertFalse(
            $this->apiAuthenticationManager->tokenCant('users:read')
        );
    }

    public function test_it_returns_false_when_the_request_is_not_authenticated(): void
    {
        $this->assertFalse(
            $this->apiAuthenticationManager->check()
        );

        $this->assertNull(
            $this->apiAuthenticationManager->user()
        );
    }
}
