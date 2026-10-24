<?php

declare(strict_types=1);

namespace App\Modules\Community\Controllers;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Community\Repositories\PollRepository;

/**
 * Player side of polls (legacy polling.php / polls_view.php) and the newspaper (newspaper.php).
 */
final class CommunityController
{
    public function __construct(
        private readonly PollRepository $polls,
        private readonly Session $session,
        private readonly ClockInterface $clock,
    ) {
    }

    public function polls(Request $request): Response
    {
        return view('pages.community.polls', [
            'active' => $this->polls->list(true),
            'closed' => $this->polls->list(false),
            'voted' => $this->polls->votedIn($request->attribute('player')->id),
        ]);
    }

    public function vote(Request $request, int $id): Response
    {
        $poll = $this->polls->find($id);

        if ($poll === null || !$poll['active']) {
            throw new NotFoundException('That poll is not open.');
        }

        $choice = filter_var($request->input('choice'), FILTER_VALIDATE_INT);
        if ($choice === false || !isset($poll['choices'][$choice])) {
            throw ValidationException::withMessages(['choice' => 'Pick one of the choices.']);
        }
        if (!$this->polls->vote($id, $request->attribute('player')->id, $choice, $this->clock->now()->format('Y-m-d H:i:s'))) {
            throw ValidationException::withMessages(['choice' => "You've already voted in this poll."]);
        }

        $this->session->flash('status', 'Thanks for voting!');

        return Response::redirect('/polls');
    }

    public function newspaper(Database $db): Response
    {
        return view('pages.community.newspaper', [
            'paper' => $db->fetch('SELECT n.*, u.username AS editor FROM newspaper n LEFT JOIN users u ON u.id = n.updated_by_id ORDER BY n.id DESC LIMIT 1'),
        ]);
    }
}
