<?php

declare(strict_types=1);

namespace App\Modules\Settings\Repositories;

use App\Core\Database;
use App\Modules\Settings\Contracts\SettingsRepositoryInterface;

/**
 * Staff-editable values in the `settings` table (legacy $set), cast by type.
 */
final class SettingsRepository implements SettingsRepositoryInterface
{
    private ?array $cache = null;

    public function __construct(private readonly Database $db)
    {
    }

    public function all(): array
    {
        if ($this->cache === null) {
            $this->cache = [];
            foreach ($this->db->fetchAll('SELECT name, value, type FROM settings ORDER BY name') as $row) {
                $this->cache[$row['name']] = ['value' => (string) $row['value'], 'type' => (string) $row['type']];
            }
        }

        return $this->cache;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        $setting = $this->all()[$name] ?? null;

        if ($setting === null) {
            return $default;
        }

        return match ($setting['type']) {
            'int' => (int) $setting['value'],
            'bool' => $setting['value'] === '1',
            default => $setting['value'],
        };
    }

    public function set(string $name, string $value): void
    {
        $this->db->update('settings', ['value' => $value], ['name' => $name]);
        $this->cache = null;
    }
}
