<?php

declare(strict_types=1);

namespace App\Modules\Players\Controllers;

use App\Contracts\ClockInterface;
use App\Core\Request;
use App\Core\Response;
use App\Modules\Players\Repositories\PlayerDirectoryRepository;

final class PlayerDirectoryController
{
    /** Seen within this many minutes = online (legacy: 15). */
    public const ONLINE_MINUTES = 15;

    public function __construct(private readonly PlayerDirectoryRepository $directory, private readonly ClockInterface $clock)
    {
    }

    public function index(Request $request): Response
    {
        $sort = (string) $request->query('sort', 'id');
        $direction = (string) $request->query('dir', 'asc');

        return view('pages.players.index', [
            'players' => $this->directory->paginate($sort, $direction, (int) $request->query('page', 1)),
            'sort' => in_array($sort, PlayerDirectoryRepository::SORTABLE, true) ? $sort : 'id',
            'direction' => strtolower($direction) === 'desc' ? 'desc' : 'asc',
            'onlineSince' => $this->onlineSince(),
        ]);
    }

    public function online(): Response
    {
        return view('pages.players.online', ['players' => $this->directory->onlineSince($this->onlineSince())]);
    }

    public function staff(): Response
    {
        return view('pages.players.staff', ['staff' => $this->directory->staff(), 'onlineSince' => $this->onlineSince()]);
    }

    public function stats(): Response
    {
        return view('pages.players.stats', ['stats' => $this->directory->statistics()]);
    }

    private function onlineSince(): string
    {
        return $this->clock->now()->modify('-' . self::ONLINE_MINUTES . ' minutes')->format('Y-m-d H:i:s');
    }
}
