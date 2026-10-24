<?php

declare(strict_types=1);

namespace App\Modules\Events\Controllers;

use App\Contracts\ClockInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Modules\Events\Contracts\EventRepositoryInterface;

/**
 * Legacy events.php: the player's notification feed.
 */
final class EventController
{
    public function __construct(private readonly EventRepositoryInterface $events, private readonly ClockInterface $clock)
    {
    }

    public function index(Request $request): Response
    {
        $userId = $request->attribute('user')->id;
        $hadUnread = $this->events->countUnread($userId) > 0;
        $page = $this->events->paginateFor($userId, (int) $request->query('page', 1), 50);

        if ($hadUnread) {
            $this->events->markAllRead($userId, $this->clock->now()->format('Y-m-d H:i:s'));
        }

        $response = view('pages.events.index', ['events' => $page]);

        return $hadUnread ? $response->withHeader('HX-Trigger', 'events-read') : $response;
    }

    public function unreadBadge(Request $request): Response
    {
        return view('partials.announcements.badge', [
            'count' => $this->events->countUnread($request->attribute('user')->id),
        ], layout: null);
    }

    public function clear(Request $request, Session $session): Response
    {
        $this->events->deleteAll($request->attribute('user')->id);
        $session->flash('status', 'All events deleted.');

        return Response::redirect('/events');
    }
}
