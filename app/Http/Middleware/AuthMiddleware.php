<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Modules\Auth\Services\AuthService;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly AuthService $auth, private readonly View $view)
    {
    }

    public function handle(Request $request, callable $next, string ...$parameters): Response
    {
        $user = $this->auth->user();

        if ($user === null) {
            if ($request->isMethod('GET')) {
                $this->auth->rememberIntendedUrl($request->path());
            }

            return Response::redirect('/login');
        }

        $request->setAttribute('user', $user);
        $this->view->share('user', $user);

        return $next($request);
    }
}
