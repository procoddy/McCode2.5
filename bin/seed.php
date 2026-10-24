<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$commands = require $app->basePath('routes/console.php');

try {
    exit($commands['seed']($app, array_slice($argv, 1)));
} catch (Throwable $e) {
    fwrite(STDERR, 'Seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}
