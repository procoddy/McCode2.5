<?php

declare(strict_types=1);

namespace App\Modules\Staff\Resources;

use App\Exceptions\NotFoundException;

/**
 * One editable table from config/staff_resources.php.
 */
final class ResourceDefinition
{
    /**
     * @param array<string, array<string, mixed>> $fields
     * @param list<string> $columns
     */
    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $singular,
        public readonly string $table,
        public readonly string $permission,
        public readonly array $fields,
        public readonly array $columns,
        public readonly string $orderBy = 'id',
        public readonly array $protectedIds = [],
        public readonly array $reassignOnDelete = [],
        public readonly array $blockDeleteIf = [],
    ) {
    }

    public static function find(string $key): self
    {
        $c = config("staff_resources.{$key}") ?? throw new NotFoundException();

        return new self(
            key: $key,
            title: $c['title'],
            singular: $c['singular'],
            table: $c['table'],
            permission: $c['permission'],
            fields: $c['fields'],
            columns: $c['columns'],
            orderBy: $c['order_by'] ?? 'id',
            protectedIds: $c['protected_ids'] ?? [],
            reassignOnDelete: $c['reassign_on_delete'] ?? [],
            blockDeleteIf: $c['block_delete_if'] ?? [],
        );
    }

    public function url(string $suffix = ''): string
    {
        return "/admin/data/{$this->key}" . ($suffix !== '' ? '/' . ltrim($suffix, '/') : '');
    }

    /** @return array<string, array<string, mixed>> Fields stored in this table (not pivots). */
    public function columnFields(): array
    {
        return array_filter($this->fields, fn (array $f) => $f['type'] !== 'pivot');
    }

    /** @return array<string, array<string, mixed>> */
    public function pivotFields(): array
    {
        return array_filter($this->fields, fn (array $f) => $f['type'] === 'pivot');
    }

    public function label(string $field): string
    {
        return $this->fields[$field]['label'] ?? ucfirst(str_replace('_', ' ', $field));
    }
}
