<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Modules\Crimes\Contracts\CrimeRepositoryInterface;
use App\Modules\Crimes\Models\Crime;
use App\Modules\Crimes\Models\CrimeGroup;

final class InMemoryCrimeRepository implements CrimeRepositoryInterface
{
    /** @var array<int, Crime> */
    public array $crimes = [];

    public function add(Crime $crime): Crime
    {
        return $this->crimes[$crime->id] = $crime;
    }

    public function groupsWithCrimes(): array
    {
        return [new CrimeGroup(1, 'Crimes', array_values($this->crimes))];
    }

    public function find(int $id): ?Crime
    {
        return $this->crimes[$id] ?? null;
    }
}
