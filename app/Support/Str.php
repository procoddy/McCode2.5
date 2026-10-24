<?php

declare(strict_types=1);

namespace App\Support;

final class Str
{
    public static function slug(string $value, string $separator = '-'): string
    {
        $value = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', $separator, $value), $separator));

        return preg_replace('/' . preg_quote($separator, '/') . '+/', $separator, $value);
    }

    public static function random(int $length = 16): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $result;
    }

    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        return mb_strlen($value) <= $limit ? $value : rtrim(mb_substr($value, 0, $limit)) . $end;
    }

    /**
     * English plural of the last word: item → items, category → categories,
     * box → boxes, person → people. Works on snake_case input.
     */
    public static function plural(string $value): string
    {
        static $irregular = ['person' => 'people', 'child' => 'children', 'man' => 'men', 'woman' => 'women'];

        $position = strrpos($value, '_');
        $prefix = $position === false ? '' : substr($value, 0, $position + 1);
        $word = $position === false ? $value : substr($value, $position + 1);

        return $prefix . match (true) {
            isset($irregular[strtolower($word)]) => $irregular[strtolower($word)],
            (bool) preg_match('/[^aeiou]y$/i', $word) => substr($word, 0, -1) . 'ies',
            (bool) preg_match('/(s|x|z|ch|sh)$/i', $word) => $word . 'es',
            default => $word . 's',
        };
    }

    public static function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    public static function camel(string $value): string
    {
        return lcfirst(self::studly($value));
    }

    public static function snake(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', str_replace(['-', ' '], '_', $value)));
    }
}
