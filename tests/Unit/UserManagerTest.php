<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\Exceptions\UserException;
use AhmedSalahDev\CoreAuth\Services\UserManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Tests\Fixtures\User;

final class UserManagerTest extends TestCase
{
    private UserManager $userManager;

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

        $this->userManager = new UserManager(User::class);
    }

    public function test_it_finds_a_user_by_id(): void
    {
        $createdUser = User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $user = $this->userManager->find($createdUser->getKey());

        $this->assertNotNull($user);
        $this->assertSame($createdUser->getKey(), $user->getAuthIdentifier());
        $this->assertSame('Ahmed', $user->getAttribute('name'));
    }

    public function test_it_returns_null_when_user_is_not_found_by_id(): void
    {
        $user = $this->userManager->find(999);

        $this->assertNull($user);
    }

    public function test_it_finds_a_user_by_attributes(): void
    {
        User::query()->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $user = $this->userManager->findBy([
            'email' => 'ahmed@example.com',
        ]);

        $this->assertNotNull($user);
        $this->assertSame('Ahmed', $user->getAttribute('name'));
    }

    public function test_it_returns_null_when_user_is_not_found_by_attributes(): void
    {
        $user = $this->userManager->findBy([
            'email' => 'missing@example.com',
        ]);

        $this->assertNull($user);
    }

    public function test_it_creates_a_user(): void
    {
        $user = $this->userManager->create([
            'name' => 'Ahmed',
            'email' => 'ahmed@example.com',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Ahmed', $user->getAttribute('name'));
        $this->assertSame('ahmed@example.com', $user->getAttribute('email'));
        $this->assertDatabaseHas('users', [
            'email' => 'ahmed@example.com',
        ]);
    }

    public function test_it_throws_an_exception_when_user_model_is_not_configured(): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'The configured user model must be an Eloquent model that implements Authenticatable.'
        );

        new UserManager(null);
    }

    public function test_it_throws_an_exception_when_user_model_is_invalid(): void
    {
        $this->expectException(UserException::class);
        $this->expectExceptionMessage(
            'The configured user model must be an Eloquent model that implements Authenticatable.'
        );

        new UserManager(stdClass::class);
    }
}