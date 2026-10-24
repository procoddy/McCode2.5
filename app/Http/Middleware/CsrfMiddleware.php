<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\ApplicationException;

final class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function __construct(private readonly Session $session)
    {
    }

    public function handle(Request $request, callable $next, string ...$parameters): Response
    {
        if (in_array($request->method(), self::SAFE_METHODS, true) || $this->isExcluded($request)) {
            return $next($request);
        }

        // Forms send _token; HTMX sends the header configured in the layout.
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

        if (!is_string($token) || !hash_equals($this->session->token(), $token)) {
            throw new ApplicationException('Your session has expired. Please refresh the page and try again.', 419);
        }

        return $next($request);
    }

    private function isExcluded(Request $request): bool
    {
        return in_array($request->path(), config('security.csrf_except', []), true);
    }
}
