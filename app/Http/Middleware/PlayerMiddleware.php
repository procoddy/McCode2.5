<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\ClockInterface;
use App\Contracts\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Exceptions\AuthorizationException;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Staff\Services\StaffPermissions;

/**
 * Loads the logged-in player's game state (the legacy $ir) for the request
 * and the views. Must run after AuthMiddleware.
 */
final class PlayerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly PlayerRepositoryInterface $players,
        private readonly View $view,
        private readonly ClockInterface $clock,
        private readonly StaffPermissions $staff,
    ) {
    }

    public function handle(Request $request, callable $next, string ...$parameters): Response
    {
        $player = $this->players->find($request->attribute('user')->id)
            ?? throw new AuthorizationException('Player not found.');

        if ($player->exp >= $player->expNeeded() && $this->players->levelUp($player->id) > 0) {
            $player = $this->players->find($player->id);
        }

        $request->setAttribute('player', $player);
        $this->view->share('player', $player);
        $this->view->share('isStaff', $this->staff->isStaff($request->attribute('user')));

        $now = $this->clock->now();
        $this->players->touchLastSeen($player->id, $now->format('Y-m-d H:i:s'), $now->modify('-1 minute')->format('Y-m-d H:i:s'));

        // Legacy fedjail: nothing but the notice (and logging out) until released.
        $sentence = $this->players->federalJail($player->id, $now->format('Y-m-d H:i:s'));
        if ($sentence !== null && $request->path() !== '/logout') {
            return view('pages.federal-jail', ['sentence' => $sentence], status: 403);
        }

        return $next($request);
    }
}
