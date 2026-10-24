<?php

declare(strict_types=1);

// Let `php -S` serve real files (assets) directly during local development.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

/** @var App\Core\Application $app */
$app = require dirname(__DIR__) . '/bootstrap/app.php';

$app->run();
