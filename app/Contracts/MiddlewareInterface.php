<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Core\Request;
use App\Core\Response;

interface MiddlewareInterface
{
    /**
     * @param callable(Request): Response $next
     * @param string ...$parameters Values after the colon in the route definition, e.g. 'throttle:5,1'.
     */
    public function handle(Request $request, callable $next, string ...$parameters): Response;
}
