<?php

declare(strict_types=1);

use App\Console\Generator;
use App\Core\Application;
use App\Core\Database;

/*
 * CLI commands, run with: php bin/<command>.php
 * Each command receives the application and the remaining argv and returns an exit code.
 */
return [
    'migrate' => static function (Application $app, array $args): int {
        $db = $app->container()->make(Database::class);
        $table = $db->quoteIdentifier((string) config('database.migrations_table', 'migrations'));
        $autoIncrement = $db->driver() === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';

        $db->query("CREATE TABLE IF NOT EXISTS {$table} (
            id {$autoIncrement},
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL
        )");

        $ran = array_column($db->fetchAll("SELECT migration, batch FROM {$table}"), 'batch', 'migration');
        $files = glob($app->basePath('database/migrations/*.php'));
        sort($files);

        if (in_array('--rollback', $args, true)) {
            $batch = $ran === [] ? 0 : max($ran);
            foreach (array_reverse($files) as $file) {
                $name = basename($file, '.php');
                if (($ran[$name] ?? null) === $batch && $batch > 0) {
                    (require $file)->down($db);
                    $db->query("DELETE FROM {$table} WHERE migration = ?", [$name]);
                    echo "Rolled back: {$name}\n";
                }
            }
            echo "Rollback completed successfully.\n";
            return 0;
        }

        $batch = ($ran === [] ? 0 : max($ran)) + 1;
        $count = 0;

        foreach ($files as $file) {
            $name = basename($file, '.php');
            if (isset($ran[$name])) {
                continue;
            }
            (require $file)->up($db);
            $db->insert(trim($table, '`'), ['migration' => $name, 'batch' => $batch]);
            echo "Migrated: {$name}\n";
            $count++;
        }

        echo $count === 0 ? "Nothing to migrate. Database is up to date.\n" : "Successfully migrated ({$count}).\n";
        return 0;
    },

    'seed' => static function (Application $app, array $args): int {
        $class = 'Database\\Seeders\\' . ($args[0] ?? 'DatabaseSeeder');

        if (!class_exists($class)) {
            fwrite(STDERR, "Seeder [{$class}] not found.\n");
            return 1;
        }

        (new $class())->run($app->container()->make(Database::class));
        echo "Database seeded successfully.\n";
        return 0;
    },

    'make' => static function (Application $app, array $args): int {
        $options = [];
        $positional = [];

        foreach ($args as $arg) {
            if (preg_match('/^--([a-z-]+)=(.*)$/', $arg, $m)) {
                $options[$m[1]] = $m[2];
            } else {
                $positional[] = $arg;
            }
        }

        [$type, $name] = array_pad($positional, 2, null);
        $generator = new Generator($app->basePath());

        if ($type === 'resource' && $name !== null) {
            $result = $generator->resource($name, $options['module'] ?? null, $options['fields'] ?? null);

            foreach ($result['created'] as $file) {
                echo "Created: {$file}\n";
            }
            foreach ($result['updated'] as $file) {
                echo "Updated: {$file}\n";
            }
            foreach ($result['notes'] as $note) {
                echo "\nNote: {$note}\n";
            }

            echo "\nResource created successfully.\n";
            echo "Next: run `php bin/migrate.php`, then open {$result['url']}\n";
            return 0;
        }

        if ($type === 'migration' && $name !== null) {
            echo 'Created: ' . $generator->migration($name) . "\n";
            echo "Migration created successfully.\n";
            return 0;
        }

        fwrite(STDERR, <<<'USAGE'
            Usage:
              php bin/make.php resource <Name> [--module=<Module>] [--fields="<field:type,...>"]
              php bin/make.php migration <name_in_snake_case>

            Field types: string, text, int, bool (default: name:string)

            Examples:
              php bin/make.php resource Item --module=Inventory --fields="name:string,quantity:int,description:text,is_tradable:bool"
              php bin/make.php migration add_bio_to_users_table

            USAGE);
        return 1;
    },

    'cron' => static function (Application $app, array $args): int {
        $job = $args[0] ?? '';

        if (!isset(App\Console\CronJobs::JOBS[$job])) {
            fwrite(STDERR, 'Usage: php bin/cron.php ' . implode('|', array_keys(App\Console\CronJobs::JOBS)) . "\n");
            return 1;
        }

        foreach ($app->container()->make(App\Console\CronJobs::class)->run($job) as $line) {
            echo $line, "\n";
        }
        return 0;
    },
];
