<?php

declare(strict_types=1);

return [
    'name' => env('SESSION_NAME', 'mccode_session'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120), // minutes
    'path' => storage_path('sessions'),
    'secure' => (bool) env('SESSION_SECURE_COOKIE', false),
    'same_site' => 'Lax',
];
