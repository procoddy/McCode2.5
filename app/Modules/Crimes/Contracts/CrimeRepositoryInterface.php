<?php

declare(strict_types=1);

namespace App\Modules\Crimes\Contracts;

use App\Modules\Crimes\Models\Crime;
use App\Modules\Crimes\Models\CrimeGroup;

interface CrimeRepositoryInterface
{
    /** @return list<CrimeGroup> Groups in display order, each with its crimes by brave cost. */
    public function groupsWithCrimes(): array;

    public function find(int $id): ?Crime;
}
