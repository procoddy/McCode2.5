<?php

declare(strict_types=1);

namespace App\Modules\Players\Controllers;

use App\Contracts\ClockInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Repositories\UserListRepository;

/**
 * Legacy blacklist.php (/enemies) and friendslist.php (/friends).
 */
final class UserListController
{
    /** URL segment => [user_lists.list, title, singular] */
    private const LISTS = [
        'friends' => ['friend', 'Friends', 'friend'],
        'enemies' => ['enemy', 'Black List', 'enemy'],
    ];

    public function __construct(
        private readonly UserListRepository $lists,
        private readonly Validator $validator,
        private readonly Session $session,
        private readonly ClockInterface $clock,
        private readonly PlayerRepositoryInterface $players,
    ) {
    }

    public function index(Request $request, string $list): Response
    {
        [$type, $title, $singular] = $this->resolve($list);
        $playerId = $request->attribute('player')->id;

        return view('pages.lists.index', [
            'list' => $list,
            'title' => $title,
            'singular' => $singular,
            'entries' => $this->lists->entries($playerId, $type),
            'listedBy' => $this->lists->listedBy($playerId, $type),
            'mostListed' => $this->lists->mostListed($type),
            'prefill' => (string) $request->query('add', ''),
            'onlineSince' => $this->clock->now()->modify('-15 minutes')->format('Y-m-d H:i:s'),
        ]);
    }

    public function add(Request $request, string $list): Response
    {
        [$type, , $singular] = $this->resolve($list);
        $data = $this->validator->validate($request->all(), ['user_id' => 'required|integer|min:1', 'comment' => 'string|max:255']);
        $me = $request->attribute('player');
        $target = $this->players->find((int) $data['user_id']);

        if ($target === null) {
            throw ValidationException::withMessages(['user_id' => "Oh no, you're trying to add a ghost."]);
        }
        if ($target->id === $me->id) {
            throw ValidationException::withMessages(['user_id' => "You can't add yourself."]);
        }
        if (!$this->lists->add($me->id, $target->id, $type, trim((string) ($data['comment'] ?? '')), $this->clock->now()->format('Y-m-d H:i:s'))) {
            throw ValidationException::withMessages(['user_id' => "{$target->username} is already on your list."]);
        }

        $this->session->flash('status', "{$target->username} was added to your {$singular} list.");

        return Response::redirect("/{$list}");
    }

    public function remove(Request $request, string $list, int $id): Response
    {
        [$type] = $this->resolve($list);

        if (!$this->lists->remove($id, $request->attribute('player')->id, $type)) {
            throw new NotFoundException('That entry is not on your list.');
        }
        $this->session->flash('status', 'Removed from your list.');

        return Response::redirect("/{$list}");
    }

    public function comment(Request $request, string $list, int $id): Response
    {
        [$type] = $this->resolve($list);
        $comment = trim((string) ($this->validator->validate($request->all(), ['comment' => 'string|max:255'])['comment'] ?? ''));

        if (!$this->lists->comment($id, $request->attribute('player')->id, $type, $comment)) {
            throw new NotFoundException('That entry is not on your list.');
        }
        $this->session->flash('status', 'Comment saved.');

        return Response::redirect("/{$list}");
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function resolve(string $list): array
    {
        return self::LISTS[$list] ?? throw new NotFoundException();
    }
}
