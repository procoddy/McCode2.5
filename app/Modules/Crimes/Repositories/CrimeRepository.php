<?php

declare(strict_types=1);

namespace App\Modules\Crimes\Repositories;

use App\Core\Database;
use App\Modules\Crimes\Contracts\CrimeRepositoryInterface;
use App\Modules\Crimes\Models\Crime;
use App\Modules\Crimes\Models\CrimeGroup;

final class CrimeRepository implements CrimeRepositoryInterface
{
    public function __construct(private readonly Database $db)
    {
    }

    public function groupsWithCrimes(): array
    {
        $crimesByGroup = [];

        foreach ($this->db->fetchAll('SELECT * FROM crimes ORDER BY brave_cost, id') as $row) {
            $crimesByGroup[(int) $row['group_id']][] = Crime::fromRow($row);
        }

        return array_map(
            fn (array $group) => new CrimeGroup((int) $group['id'], (string) $group['name'], $crimesByGroup[(int) $group['id']] ?? []),
            $this->db->fetchAll('SELECT id, name FROM crime_groups ORDER BY sort_order, id'),
        );
    }

    public function find(int $id): ?Crime
    {
        $row = $this->db->fetch('SELECT * FROM crimes WHERE id = ?', [$id]);

        return $row === null ? null : Crime::fromRow($row);
    }
}
