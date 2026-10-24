<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AuthorizationException;
use App\Modules\Auth\Services\AuthService;

/**
 * Must run after AuthMiddleware.
 */
final class AdminMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function handle(Request $request, callable $next, string ...$parameters): Response
    {
        if (!$this->auth->user()?->isAdmin()) {
            throw new AuthorizationException('This area is restricted to staff.');
        }

        return $next($request);
    }
}
