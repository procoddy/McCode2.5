<?php

declare(strict_types=1);

namespace App\Support;

final class Arr
{
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    public static function set(array &$array, string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $current = &$array;

        foreach ($segments as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    public static function has(array $array, string $key): bool
    {
        $missing = new \stdClass();

        return self::get($array, $key, $missing) !== $missing;
    }

    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    public static function pluck(array $rows, string $value, ?string $key = null): array
    {
        $result = [];

        foreach ($rows as $row) {
            $row = (array) $row;
            $key === null ? $result[] = $row[$value] ?? null : $result[$row[$key]] = $row[$value] ?? null;
        }

        return $result;
    }
}
