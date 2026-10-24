<?php

declare(strict_types=1);

/*
 * Composer's autoloader is used when available. The fallback keeps the app
 * bootable on hosts where `composer install` has not been run yet.
 */
$composer = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($composer)) {
    require_once $composer;
    return;
}

spl_autoload_register(static function (string $class): void {
    static $prefixes = [
        'App\\' => '/app/',
        'Database\\Seeders\\' => '/database/seeders/',
        'Database\\Factories\\' => '/database/factories/',
        'Tests\\' => '/tests/',
    ];

    foreach ($prefixes as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = dirname(__DIR__) . $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
            return;
        }
    }
});

require_once dirname(__DIR__) . '/app/helpers.php';
