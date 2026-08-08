<?php

declare(strict_types=1);

namespace Tests\Unit;

use AhmedSalahDev\CoreAuth\CoreAuthServiceProvider;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

final class CoreAuthServiceProviderTest extends TestCase
{
    public function test_service_provider_can_be_instantiated(): void
    {
        $container = new Container();

        $provider = new CoreAuthServiceProvider($container);

        $this->assertInstanceOf(
            CoreAuthServiceProvider::class,
            $provider
        );
    }
}