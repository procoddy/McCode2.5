<?php

declare(strict_types=1);

namespace App\Modules\Staff\Resources;

use App\Core\Database;
use App\Support\Pagination;

/**
 * Generic SQL for staff-editable tables. Table and column names come only from
 * config/staff_resources.php and are still checked by Database::quoteIdentifier().
 */
class ResourceRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function paginate(ResourceDefinition $def, int $page, int $perPage = 50): Pagination
    {
        $table = $this->db->quoteIdentifier($def->table);
        $order = implode(', ', array_map(
            fn (string $column) => $this->db->quoteIdentifier(trim($column)),
            explode(',', $def->orderBy),
        ));
        $pagination = new Pagination((int) $this->db->fetchColumn("SELECT COUNT(*) FROM {$table}"), $perPage, $page);

        return $pagination->withItems($this->db->fetchAll(
            "SELECT * FROM {$table} ORDER BY {$order}, id LIMIT {$pagination->perPage} OFFSET {$pagination->offset()}",
        ));
    }

    public function find(ResourceDefinition $def, int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM {$this->db->quoteIdentifier($def->table)} WHERE id = ?", [$id]);
    }

    public function create(ResourceDefinition $def, array $values, array $pivots): int
    {
        return $this->db->transaction(function () use ($def, $values, $pivots): int {
            $id = $this->db->insert($def->table, $values);
            $this->syncPivots($def, $id, $pivots);

            return $id;
        });
    }

    public function update(ResourceDefinition $def, int $id, array $values, array $pivots): void
    {
        $this->db->transaction(function () use ($def, $id, $values, $pivots): void {
            if ($values !== []) {
                $this->db->update($def->table, $values, ['id' => $id]);
            }
            $this->syncPivots($def, $id, $pivots);
        });
    }

    public function delete(ResourceDefinition $def, int $id): void
    {
        $this->db->transaction(function () use ($def, $id): void {
            foreach ($def->reassignOnDelete as [$table, $column, $to]) {
                $this->db->query(
                    "UPDATE {$this->db->quoteIdentifier($table)} SET {$this->db->quoteIdentifier($column)} = ? WHERE {$this->db->quoteIdentifier($column)} = ?",
                    [$to, $id],
                );
            }
            $this->db->delete($def->table, ['id' => $id]);
        });
    }

    public function usedBy(string $table, string $column, int $id): bool
    {
        return $this->db->fetchColumn(
            "SELECT 1 FROM {$this->db->quoteIdentifier($table)} WHERE {$this->db->quoteIdentifier($column)} = ? LIMIT 1",
            [$id],
        ) !== null;
    }

    public function valueTaken(ResourceDefinition $def, string $column, mixed $value, ?int $exceptId): bool
    {
        return $this->db->fetchColumn(
            "SELECT 1 FROM {$this->db->quoteIdentifier($def->table)} WHERE {$this->db->quoteIdentifier($column)} = ? AND id <> ? LIMIT 1",
            [$value, $exceptId ?? 0],
        ) !== null;
    }

    /** @return array<int, string> id => name for a select field's source table. */
    public function options(string $table): array
    {
        $rows = $this->db->fetchAll("SELECT id, name FROM {$this->db->quoteIdentifier($table)} ORDER BY name, id");

        return array_column($rows, 'name', 'id');
    }

    /** @return list<int> */
    public function pivotIds(array $field, int $id): array
    {
        return array_map('intval', array_column($this->db->fetchAll(
            "SELECT {$this->db->quoteIdentifier($field['foreign_key'])} AS fid FROM {$this->db->quoteIdentifier($field['pivot'])}
             WHERE {$this->db->quoteIdentifier($field['local_key'])} = ?",
            [$id],
        ), 'fid'));
    }

    /** @param array<string, list<int>> $pivots field name => selected ids */
    private function syncPivots(ResourceDefinition $def, int $id, array $pivots): void
    {
        foreach ($pivots as $name => $ids) {
            $field = $def->fields[$name];
            $this->db->delete($field['pivot'], [$field['local_key'] => $id]);
            foreach (array_unique($ids) as $foreignId) {
                $this->db->insert($field['pivot'], [$field['local_key'] => $id, $field['foreign_key'] => $foreignId]);
            }
        }
    }
}
