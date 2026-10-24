<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Contracts\ClockInterface;
use App\Core\Container;
use App\Support\SystemClock;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function test_autowires_constructor_dependencies(): void
    {
        $container = new Container();
        $container->bind(ClockInterface::class, SystemClock::class);

        $service = $container->make(NeedsClock::class);

        $this->assertInstanceOf(SystemClock::class, $service->clock);
    }

    public function test_singletons_are_shared(): void
    {
        $container = new Container();
        $container->singleton(SystemClock::class);

        $this->assertSame($container->make(SystemClock::class), $container->make(SystemClock::class));
    }

    public function test_call_injects_given_values_by_name(): void
    {
        $container = new Container();
        $container->bind(ClockInterface::class, SystemClock::class);

        $result = $container->call(fn (ClockInterface $clock, int $id) => [$clock::class, $id], ['id' => 7]);

        $this->assertSame([SystemClock::class, 7], $result);
    }
}

final class NeedsClock
{
    public function __construct(public readonly ClockInterface $clock)
    {
    }
}
