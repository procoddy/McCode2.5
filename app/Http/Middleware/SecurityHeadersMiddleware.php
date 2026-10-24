<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\ExceptionHandler;
use App\Core\Request;
use App\Core\Response;
use Throwable;

/**
 * Registered first in config/app.php so it wraps everything else; exceptions
 * are rendered here so error pages carry the same headers.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ExceptionHandler $exceptions)
    {
    }

    public function handle(Request $request, callable $next, string ...$parameters): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $response = $this->exceptions->render($e, $request);
        }

        foreach (config('security.headers', []) as $name => $value) {
            if ($response->getHeader($name) === null) {
                $response->withHeader($name, $value);
            }
        }

        return $response;
    }
}
