<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Formula;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormulaTest extends TestCase
{
    public function test_legacy_crime_formula(): void
    {
        $this->assertEqualsWithDelta(
            ((100 * 0.8) / 2.5) + (8 / 4),
            Formula::evaluate('((WILL*0.8)/2.5)+(LEVEL/4)', ['WILL' => 100, 'LEVEL' => 8]),
            0.0001,
        );
    }

    public function test_precedence_unary_minus_and_case(): void
    {
        $this->assertSame(7.0, Formula::evaluate('1 + 2 * 3', []));
        $this->assertSame(9.0, Formula::evaluate('(1 + 2) * 3', []));
        $this->assertSame(-4.0, Formula::evaluate('-level * 2', ['LEVEL' => 2]));
        $this->assertSame(0.5, Formula::evaluate('.5', []));
    }

    /** @return iterable<string, array{string}> */
    public static function rejected(): iterable
    {
        yield 'php code' => ['system("rm -rf /")'];
        yield 'variable syntax' => ['$x + 1'];
        yield 'unknown name' => ['STRENGTH * 2'];
        yield 'semicolon' => ['1; 2'];
        yield 'unbalanced' => ['(1 + 2'];
        yield 'trailing operator' => ['1 +'];
        yield 'division by zero' => ['1 / 0'];
    }

    #[DataProvider('rejected')]
    public function test_rejects_anything_but_arithmetic(string $formula): void
    {
        $this->expectException(InvalidArgumentException::class);
        Formula::evaluate($formula, ['LEVEL' => 1]);
    }
}
