<?php

declare(strict_types=1);

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\PlayerMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\StaffMiddleware;

return [
    'name' => env('APP_NAME', 'McCode 2.5'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    // Runs on every request, in order.
    'middleware' => [
        SecurityHeadersMiddleware::class,
        CsrfMiddleware::class,
    ],

    // Short names usable in route definitions, e.g. 'throttle:5,1'.
    'middleware_aliases' => [
        'auth' => AuthMiddleware::class,
        'guest' => GuestMiddleware::class,
        'admin' => AdminMiddleware::class,
        'player' => PlayerMiddleware::class,
        'staff' => StaffMiddleware::class,
        'throttle' => RateLimitMiddleware::class,
    ],

    'routes' => ['web', 'admin'],
];
