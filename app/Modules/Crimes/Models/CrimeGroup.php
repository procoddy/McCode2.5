<?php

declare(strict_types=1);

namespace App\Modules\Crimes\Models;

final class CrimeGroup
{
    /**
     * @param list<Crime> $crimes
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $crimes = [],
    ) {
    }
}
