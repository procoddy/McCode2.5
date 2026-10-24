<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Modules\Settings\Contracts\SettingsRepositoryInterface;

final class InMemorySettingsRepository implements SettingsRepositoryInterface
{
    /** @var array<string, array{value: string, type: string}> */
    public array $settings = [
        'sendbank_on' => ['value' => '1', 'type' => 'bool'],
        'sendcrys_on' => ['value' => '1', 'type' => 'bool'],
        'staff_pad' => ['value' => 'Hello staff', 'type' => 'string'],
    ];

    public function all(): array
    {
        return $this->settings;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        $s = $this->settings[$name] ?? null;

        return $s === null ? $default : match ($s['type']) {
            'bool' => $s['value'] === '1',
            'int' => (int) $s['value'],
            default => $s['value'],
        };
    }

    public function set(string $name, string $value): void
    {
        $this->settings[$name]['value'] = $value;
    }
}
