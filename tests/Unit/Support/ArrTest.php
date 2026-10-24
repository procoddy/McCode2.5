<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Arr;
use PHPUnit\Framework\TestCase;

final class ArrTest extends TestCase
{
    public function test_get_supports_dot_notation(): void
    {
        $config = ['database' => ['connections' => ['mysql' => ['host' => 'db']]]];

        $this->assertSame('db', Arr::get($config, 'database.connections.mysql.host'));
        $this->assertSame('fallback', Arr::get($config, 'database.missing', 'fallback'));
    }

    public function test_set_creates_nested_keys(): void
    {
        $array = [];
        Arr::set($array, 'a.b.c', 1);

        $this->assertSame(['a' => ['b' => ['c' => 1]]], $array);
        $this->assertTrue(Arr::has($array, 'a.b'));
    }
}
