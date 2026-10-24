<?php

declare(strict_types=1);

return [
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self'; "
            . "style-src 'self' 'unsafe-inline'; img-src 'self' data:; frame-ancestors 'none'; form-action 'self'",
    ],

    // Paths excluded from CSRF verification (e.g. payment webhooks).
    'csrf_except' => [],

    'password' => [
        'algo' => PASSWORD_DEFAULT,
        'options' => [],
    ],
];
