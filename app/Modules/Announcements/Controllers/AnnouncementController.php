<?php

declare(strict_types=1);

namespace App\Modules\Announcements\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Modules\Announcements\Services\AnnouncementService;

final class AnnouncementController
{
    public function __construct(private readonly AnnouncementService $announcements)
    {
    }

    public function index(Request $request): Response
    {
        $feed = $this->announcements->feedFor($request->attribute('user'), (int) $request->query('page', 1));

        $response = view('pages.announcements.index', $feed);

        // Tells the sidebar badge (hx-trigger="... announcements-read from:body") to refresh.
        return $feed['hadUnread'] ? $response->withHeader('HX-Trigger', 'announcements-read') : $response;
    }

    /** HTMX partial for the sidebar badge. */
    public function unreadBadge(Request $request): Response
    {
        return view('partials.announcements.badge', [
            'count' => $this->announcements->unreadCount($request->attribute('user')),
        ], layout: null);
    }
}
