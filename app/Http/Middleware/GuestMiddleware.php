<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Auth\Services\AuthService;

final class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function handle(Request $request, callable $next, string ...$parameters): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/dashboard');
        }

        return $next($request);
    }
}
