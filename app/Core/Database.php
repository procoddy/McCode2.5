<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use PDO;
use PDOStatement;
use Throwable;

final class Database
{
    private ?PDO $pdo = null;

    public function __construct(private readonly array $config)
    {
    }

    public function pdo(): PDO
    {
        return $this->pdo ??= $this->connect();
    }

    public function driver(): string
    {
        return (string) ($this->config['driver'] ?? 'mysql');
    }

    public function query(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $value = $this->query($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_map($this->quoteIdentifier(...), array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->query(
            "INSERT INTO {$this->quoteIdentifier($table)} ({$columns}) VALUES ({$placeholders})",
            array_values($data),
        );

        return (int) $this->pdo()->lastInsertId();
    }

    public function update(string $table, array $data, array $where): int
    {
        $set = implode(', ', array_map(fn ($column) => $this->quoteIdentifier($column) . ' = ?', array_keys($data)));
        [$whereSql, $whereParams] = $this->compileWhere($where);

        return $this->query(
            "UPDATE {$this->quoteIdentifier($table)} SET {$set} WHERE {$whereSql}",
            [...array_values($data), ...$whereParams],
        )->rowCount();
    }

    public function delete(string $table, array $where): int
    {
        [$whereSql, $whereParams] = $this->compileWhere($where);

        return $this->query("DELETE FROM {$this->quoteIdentifier($table)} WHERE {$whereSql}", $whereParams)->rowCount();
    }

    /**
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        // Already inside a transaction (e.g. an outer service call or a test):
        // join it instead of failing, so the outer commit/rollback decides.
        if ($this->pdo()->inTransaction()) {
            return $callback($this);
        }

        $this->pdo()->beginTransaction();

        try {
            $result = $callback($this);
            $this->pdo()->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo()->rollBack();
            throw $e;
        }
    }

    public function quoteIdentifier(string $identifier): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $identifier)) {
            throw new InvalidArgumentException("Invalid SQL identifier [{$identifier}].");
        }

        return "`{$identifier}`";
    }

    private function compileWhere(array $where): array
    {
        if ($where === []) {
            throw new InvalidArgumentException('Refusing to run an unconstrained UPDATE/DELETE.');
        }

        $clauses = array_map(fn ($column) => $this->quoteIdentifier($column) . ' = ?', array_keys($where));

        return [implode(' AND ', $clauses), array_values($where)];
    }

    private function connect(): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return match ($this->driver()) {
            'mysql' => new PDO(
                sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['database'],
                    $this->config['charset'] ?? 'utf8mb4',
                ),
                $this->config['username'],
                $this->config['password'],
                // rowCount() = rows matched, not rows changed, so a conditional
                // UPDATE that changes nothing (e.g. paying 0) still counts as applied.
                $options + [PDO::MYSQL_ATTR_FOUND_ROWS => true],
            ),
            'sqlite' => new PDO('sqlite:' . $this->config['database'], null, null, $options),
            default => throw new InvalidArgumentException("Unsupported database driver [{$this->driver()}]."),
        };
    }
}
