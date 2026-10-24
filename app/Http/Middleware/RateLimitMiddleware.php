<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\ClockInterface;
use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\ApplicationException;

/**
 * Usage in routes: 'throttle:5,1' = 5 requests per 1 minute per IP and path.
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ClockInterface $clock)
    {
    }

    public function handle(Request $request, callable $next, string ...$parameters): Response
    {
        $maxAttempts = (int) ($parameters[0] ?? 60);
        $decaySeconds = (int) ($parameters[1] ?? 1) * 60;

        $directory = storage_path('cache/rate-limits');
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $handle = fopen($directory . '/' . sha1($request->ip() . '|' . $request->path()), 'c+');
        flock($handle, LOCK_EX);

        try {
            $now = $this->clock->now()->getTimestamp();
            $state = json_decode((string) stream_get_contents($handle), true) ?: [];

            if (($state['reset_at'] ?? 0) <= $now) {
                $state = ['hits' => 0, 'reset_at' => $now + $decaySeconds];
            }

            $state['hits']++;

            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, json_encode($state));
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        if ($state['hits'] > $maxAttempts) {
            $wait = max(1, $state['reset_at'] - $now);
            throw new ApplicationException("Too many attempts. Please try again in {$wait} seconds.", 429);
        }

        return $next($request)
            ->withHeader('X-RateLimit-Limit', (string) $maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $maxAttempts - $state['hits']));
    }
}
