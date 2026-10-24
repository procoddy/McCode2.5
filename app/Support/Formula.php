<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/**
 * Evaluates staff-written arithmetic such as "((WILL*0.8)/2.5)+(LEVEL/4)".
 *
 * Replaces the legacy eval() of crimePERCFORM: only numbers, the given
 * variable names, + - * / and parentheses are understood, so a formula
 * stored in the database can never run PHP code.
 */
final class Formula
{
    /**
     * @param array<string, int|float> $variables Upper-case names, e.g. ['LEVEL' => 5]
     */
    public static function evaluate(string $expression, array $variables): float
    {
        $tokens = self::tokenize($expression);
        $position = 0;
        $value = self::expression($tokens, $position, $variables);

        if ($position < count($tokens)) {
            throw new InvalidArgumentException("Unexpected '{$tokens[$position]}' in formula.");
        }

        return $value;
    }

    /** @return list<string> */
    private static function tokenize(string $expression): array
    {
        $tokens = [];
        $offset = 0;
        $length = strlen($expression);

        while ($offset < $length) {
            if (!preg_match('/\s*(\d+(?:\.\d+)?|\.\d+|[A-Za-z_]+|[-+*\/()])\s*/A', $expression, $match, 0, $offset)) {
                throw new InvalidArgumentException("Invalid character in formula at position {$offset}.");
            }

            $tokens[] = $match[1];
            $offset += strlen($match[0]);
        }

        return $tokens;
    }

    private static function expression(array $tokens, int &$position, array $variables): float
    {
        $value = self::term($tokens, $position, $variables);

        while (in_array($tokens[$position] ?? null, ['+', '-'], true)) {
            $operator = $tokens[$position++];
            $right = self::term($tokens, $position, $variables);
            $value = $operator === '+' ? $value + $right : $value - $right;
        }

        return $value;
    }

    private static function term(array $tokens, int &$position, array $variables): float
    {
        $value = self::factor($tokens, $position, $variables);

        while (in_array($tokens[$position] ?? null, ['*', '/'], true)) {
            $operator = $tokens[$position++];
            $right = self::factor($tokens, $position, $variables);

            if ($operator === '/' && $right == 0.0) {
                throw new InvalidArgumentException('Division by zero in formula.');
            }

            $value = $operator === '*' ? $value * $right : $value / $right;
        }

        return $value;
    }

    private static function factor(array $tokens, int &$position, array $variables): float
    {
        $token = $tokens[$position++] ?? throw new InvalidArgumentException('Formula ended unexpectedly.');

        return match (true) {
            $token === '-' => -self::factor($tokens, $position, $variables),
            $token === '+' => self::factor($tokens, $position, $variables),
            $token === '(' => self::parenthesised($tokens, $position, $variables),
            is_numeric($token) => (float) $token,
            array_key_exists(strtoupper($token), $variables) => (float) $variables[strtoupper($token)],
            default => throw new InvalidArgumentException("Unknown name '{$token}' in formula."),
        };
    }

    private static function parenthesised(array $tokens, int &$position, array $variables): float
    {
        $value = self::expression($tokens, $position, $variables);

        if (($tokens[$position++] ?? null) !== ')') {
            throw new InvalidArgumentException('Missing closing parenthesis in formula.');
        }

        return $value;
    }
}
