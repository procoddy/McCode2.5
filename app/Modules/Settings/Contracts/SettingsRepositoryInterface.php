<?php

declare(strict_types=1);

namespace App\Modules\Settings\Contracts;

interface SettingsRepositoryInterface
{
    /** @return array<string, array{value: string, type: string}> */
    public function all(): array;

    public function get(string $name, mixed $default = null): mixed;

    public function set(string $name, string $value): void;
}
