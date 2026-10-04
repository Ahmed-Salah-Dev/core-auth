<?php

declare(strict_types=1);

namespace Tests\Integration;

use AhmedSalahDev\CoreAuth\Data\ApiTokenResult;
use AhmedSalahDev\CoreAuth\Services\ApiTokenManager;
use DateTimeImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\SanctumServiceProvider;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\User;

final class ApiTokenManagerTest extends TestCase
{
    private ApiTokenManager $apiTokenManager;

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

        $this->apiTokenManager = new ApiTokenManager();
    }

    public function test_it_creates_an_api_token(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $result = $this->apiTokenManager->createToken(
            $user,
            'mobile-app',
            ['orders:read'],
        );

        $this->assertInstanceOf(ApiTokenResult::class, $result);
        $this->assertSame('mobile-app', $result->name);
        $this->assertNotSame('', $result->token);
        $this->assertStringContainsString('|', $result->token);
        $this->assertNull($result->expiresAt);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->getKey(),
            'name' => 'mobile-app',
        ]);
    }

    public function test_it_creates_a_token_with_abilities_and_expiration(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $expiresAt = new DateTimeImmutable('+30 days');

        $result = $this->apiTokenManager->createToken(
            $user,
            'mobile-app',
            ['orders:read', 'orders:create'],
            $expiresAt,
        );

        $this->assertSame($expiresAt, $result->expiresAt);

        $token = $user->tokens()->first();

        $this->assertNotNull($token);
        $this->assertSame(
            ['orders:read', 'orders:create'],
            $token->abilities,
        );
        $this->assertNotNull($token->expires_at);
        $this->assertSame(
            $expiresAt->getTimestamp(),
            $token->expires_at->getTimestamp(),
        );
    }

    public function test_it_retrieves_user_tokens(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $user->createToken('web');
        $user->createToken('mobile');

        $tokens = $this->apiTokenManager->tokens($user);

        $this->assertCount(2, $tokens);
        $this->assertSame(
            ['web', 'mobile'],
            $tokens->pluck('name')->all(),
        );
    }

    public function test_it_revokes_a_specific_token(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $user->createToken('web');
        $user->createToken('mobile');

        $token = $user->tokens()->where('name', 'web')->first();

        $this->assertNotNull($token);

        $this->apiTokenManager->revoke(
            $user,
            $token->getKey(),
        );

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->getKey(),
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'mobile',
        ]);
    }

    public function test_it_revokes_all_user_tokens(): void
    {
        $user = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $user->createToken('web');
        $user->createToken('mobile');

        $this->apiTokenManager->revokeAll($user);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_does_not_revoke_a_token_belonging_to_another_user(): void
    {
        $firstUser = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $secondUser = User::query()->create([
            'name' => 'Omar',
            'email' => 'omar@example.com',
        ]);

        $token = $secondUser->createToken('mobile')->accessToken;

        $this->apiTokenManager->revoke(
            $firstUser,
            $token->getKey(),
        );

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->getKey(),
        ]);
    }
}
