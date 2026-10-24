<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Generator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class GeneratorTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/mccode-generator-' . bin2hex(random_bytes(4));
        $root = dirname(__DIR__, 3);

        foreach (['routes/web.php', 'resources/views/partials/sidebar.php'] as $file) {
            mkdir(dirname("{$this->dir}/{$file}"), 0777, true);
            copy("{$root}/{$file}", "{$this->dir}/{$file}");
        }
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
    }

    public function test_resource_generates_valid_php_and_registers_route(): void
    {
        $result = (new Generator($this->dir))->resource(
            'BankAccount',
            'Banking',
            'owner_name:string,balance:int,notes:text,is_frozen:bool',
        );

        $this->assertCount(10, $result['created']);
        $this->assertSame('/bank-accounts', $result['url']);
        $this->assertContains('app/Modules/Banking/Controllers/BankAccountController.php', $result['created']);
        $this->assertContains('resources/views/pages/bank_accounts/_form.php', $result['created']);

        foreach ($result['created'] as $file) {
            exec('php -l ' . escapeshellarg("{$this->dir}/{$file}") . ' 2>&1', $output, $exitCode);
            $this->assertSame(0, $exitCode, "{$file} has a syntax error: " . implode("\n", $output));
            $this->assertStringNotContainsString('{{', file_get_contents("{$this->dir}/{$file}"), "{$file} has an unreplaced placeholder");
        }

        $routes = file_get_contents("{$this->dir}/routes/web.php");
        $this->assertStringContainsString(
            "\$router->resource('/bank-accounts', \\App\\Modules\\Banking\\Controllers\\BankAccountController::class);",
            $routes,
        );
        $this->assertStringContainsString('// make:resource routes', $routes);
        $this->assertStringContainsString('<a href="/bank-accounts">Bank accounts</a>', file_get_contents("{$this->dir}/resources/views/partials/sidebar.php"));

        $model = file_get_contents("{$this->dir}/app/Modules/Banking/Models/BankAccount.php");
        $this->assertStringContainsString('public readonly bool $isFrozen,', $model);
    }

    public function test_refuses_to_overwrite_and_writes_nothing(): void
    {
        $generator = new Generator($this->dir);
        $generator->resource('Item');
        $routesBefore = file_get_contents("{$this->dir}/routes/web.php");

        $this->expectException(InvalidArgumentException::class);

        try {
            $generator->resource('Item');
        } finally {
            $this->assertSame($routesBefore, file_get_contents("{$this->dir}/routes/web.php"));
        }
    }

    public function test_rejects_bad_input(): void
    {
        $generator = new Generator($this->dir);

        foreach (['1Item', 'Title'] as $name) {
            try {
                $generator->resource($name);
                $this->fail("Expected [{$name}] to be rejected");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->expectExceptionMessage('Unknown type [float]');
        $generator->resource('Item', null, 'price:float');
    }
}
