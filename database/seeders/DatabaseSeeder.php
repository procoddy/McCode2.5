<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database;

final class DatabaseSeeder
{
    public function run(Database $db): void
    {
        (new UserSeeder())->run($db);
        (new AnnouncementSeeder())->run($db);
        (new CrimeSeeder())->run($db);
    }
}
