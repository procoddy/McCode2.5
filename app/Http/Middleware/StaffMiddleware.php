<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Exceptions\AuthorizationException;
use App\Modules\Staff\Services\StaffPermissions;

/**
 * 'staff'                 any staff member (or the owner)
 * 'staff:manage_items'    needs that permission
 * 'staff:credit_item,manage_items'  needs at least one of them
 *
 * Must run after AuthMiddleware. Shares $staffCan with views for the menu.
 */
final class StaffMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly StaffPermissions $permissions, private readonly View $view)
    {
    }

    public function handle(Request $request, callable $next, string ...$parameters): Response
    {
        $user = $request->attribute('user');

        if (!$this->permissions->isStaff($user)) {
            throw new AuthorizationException('This area is restricted to staff.');
        }

        if ($parameters !== [] && !$this->permissions->can($user, ...$parameters)) {
            throw new AuthorizationException('You do not have permission to do that.');
        }

        $this->view->share('staffCan', fn (string ...$permissions) => $permissions === [] || $this->permissions->can($user, ...$permissions));

        return $next($request);
    }
}
