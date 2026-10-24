<?php

declare(strict_types=1);

namespace App\Legacy;

use App\Core\Database;
use PDOStatement;

/**
 * Exposes the McCodes 2.x `$db` API on top of the new PDO connection so
 * legacy pages can run unchanged while they are migrated into modules.
 *
 * @deprecated Use App\Core\Database with bound parameters in new code.
 */
final class LegacyDatabaseAdapter
{
    private int $affectedRows = 0;

    public function __construct(private readonly Database $db)
    {
    }

    public function query(string $sql): PDOStatement
    {
        $statement = $this->db->pdo()->query($sql);
        $this->affectedRows = $statement->rowCount();

        return $statement;
    }

    public function fetch_row(PDOStatement $result): array|false
    {
        return $result->fetch();
    }

    public function fetch_single(PDOStatement $result): mixed
    {
        return $result->fetchColumn();
    }

    /** Reliable for SELECT on MySQL (buffered queries); not portable to other drivers. */
    public function num_rows(PDOStatement $result): int
    {
        return $result->rowCount();
    }

    public function escape(string $value): string
    {
        return substr($this->db->pdo()->quote($value), 1, -1);
    }

    public function insert_id(): int
    {
        return (int) $this->db->pdo()->lastInsertId();
    }

    public function affected_rows(): int
    {
        return $this->affectedRows;
    }

    public function free_result(PDOStatement $result): void
    {
        $result->closeCursor();
    }
}
