<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * @template T of object
 */
interface RepositoryInterface
{
    /** @return T|null */
    public function find(int $id): ?object;

    /** @return int The new record's id. */
    public function create(array $attributes): int;

    public function update(int $id, array $attributes): bool;

    public function delete(int $id): bool;
}
