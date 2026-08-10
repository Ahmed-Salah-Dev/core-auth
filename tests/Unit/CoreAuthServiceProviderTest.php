<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\CoreAuthServiceProvider;
use Orchestra\Testbench\TestCase;

final class CoreAuthServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CoreAuthServiceProvider::class,
        ];
    }

    public function test_service_provider_is_registered(): void
    {
        $this->assertTrue(
            $this->app->getProvider(CoreAuthServiceProvider::class) !== null
        );
    }
}