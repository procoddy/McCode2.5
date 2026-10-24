<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Contracts\ClockInterface;
use App\Core\Request;
use App\Core\Response;

final class DashboardController
{
    public function __construct(private readonly ClockInterface $clock)
    {
    }

    public function index(Request $request): Response
    {
        return view('pages.dashboard.index', [
            'user' => $request->attribute('user'),
            'updatedAt' => $this->clock->now()->format('H:i:s'),
        ]);
    }

    /** HTMX partial: only the stats block, no layout. */
    public function stats(Request $request): Response
    {
        return view('partials.dashboard-stats', [
            'player' => $request->attribute('user'),
            'updatedAt' => $this->clock->now()->format('H:i:s'),
        ], layout: null);
    }
}
