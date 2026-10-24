<?php

declare(strict_types=1);

namespace App\Modules\Forums\Services;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Auth\Models\User;
use App\Modules\Forums\Repositories\ForumRepository;
use App\Modules\Players\Models\Player;
use App\Modules\Staff\Services\StaffPermissions;

/**
 * Who may read, post in and moderate which forum. Public forums are open to
 * all; staff forums need use_staff_forums; a gang forum is for its members.
 * Moderators (manage_forums) can edit, pin, lock, move and delete anywhere they can read.
 */
final class ForumService
{
    public const TITLE_MAX = 150;
    public const BODY_MAX = 10000;

    public function __construct(
        private readonly ForumRepository $forums,
        private readonly StaffPermissions $permissions,
        private readonly Database $db,
        private readonly ClockInterface $clock,
    ) {
    }

    public function visibleForums(User $user, Player $player): array
    {
        return $this->forums->visibleForums($player->gangId, $this->permissions->can($user, 'use_staff_forums'));
    }

    public function readableForum(User $user, Player $player, int $forumId): array
    {
        $forum = $this->forums->forum($forumId) ?? throw new NotFoundException('That forum does not exist.');
        $this->authorizeRead($user, $player, $forum['access'], $forum['gang_id'] === null ? null : (int) $forum['gang_id']);

        return $forum;
    }

    public function readableTopic(User $user, Player $player, int $topicId): array
    {
        $topic = $this->forums->topic($topicId) ?? throw new NotFoundException('That topic does not exist.');
        $this->authorizeRead($user, $player, $topic['access'], $topic['gang_id'] === null ? null : (int) $topic['gang_id']);

        return $topic;
    }

    public function isModerator(User $user): bool
    {
        return $this->permissions->can($user, 'manage_forums');
    }

    /** Why this player can't post, or null. */
    public function banReason(int $userId): ?string
    {
        $row = $this->db->fetch('SELECT forum_banned_until, forum_ban_reason FROM users WHERE id = ?', [$userId]);

        if ($row === null || $row['forum_banned_until'] === null || $row['forum_banned_until'] <= $this->now()) {
            return null;
        }

        return 'You are banned from the forums until ' . date('F j, Y, g:i a', strtotime($row['forum_banned_until']))
            . ($row['forum_ban_reason'] ? ". Reason: {$row['forum_ban_reason']}" : '.');
    }

    public function createTopic(User $user, Player $player, int $forumId, string $title, string $description, string $body): int
    {
        $forum = $this->readableForum($user, $player, $forumId);
        $this->assertCanPost($player->id);

        return $this->forums->createTopic((int) $forum['id'], $player->id, $title, $description, $body, $this->now());
    }

    public function reply(User $user, Player $player, int $topicId, string $body): int
    {
        $topic = $this->readableTopic($user, $player, $topicId);
        $this->assertCanPost($player->id);

        if ($topic['locked'] && !$this->isModerator($user)) {
            throw ValidationException::withMessages(['body' => 'This topic is locked.']);
        }

        return $this->forums->reply($topicId, $player->id, $body, $this->now());
    }

    /** @return array post row, after checking the user may edit it */
    public function editablePost(User $user, Player $player, int $postId): array
    {
        $post = $this->forums->post($postId) ?? throw new NotFoundException('That post does not exist.');
        $this->readableTopic($user, $player, (int) $post['topic_id']);

        if (!$this->isModerator($user) && ((int) $post['user_id'] !== $player->id || $post['locked'])) {
            throw new AuthorizationException('You can only edit your own posts in unlocked topics.');
        }

        return $post;
    }

    public function editPost(User $user, Player $player, int $postId, string $body): array
    {
        $post = $this->editablePost($user, $player, $postId);
        $this->assertCanPost($player->id);
        $this->forums->editPost($postId, $body, $player->id, $this->now());

        return $post;
    }

    /** Moderator actions on a topic: pin, unpin, lock, unlock, move, delete. */
    public function moderate(User $user, Player $player, int $topicId, string $action, ?int $toForum = null): string
    {
        $topic = $this->readableTopic($user, $player, $topicId);

        if (!$this->isModerator($user)) {
            throw new AuthorizationException('Only forum moderators can do that.');
        }

        switch ($action) {
            case 'pin': case 'unpin':
                $this->forums->updateTopic($topicId, ['pinned' => $action === 'pin' ? 1 : 0]);
                return $action === 'pin' ? 'Topic pinned.' : 'Topic unpinned.';
            case 'lock': case 'unlock':
                $this->forums->updateTopic($topicId, ['locked' => $action === 'lock' ? 1 : 0]);
                return $action === 'lock' ? 'Topic locked.' : 'Topic unlocked.';
            case 'move':
                $target = $this->readableForum($user, $player, (int) $toForum);
                $this->forums->updateTopic($topicId, ['forum_id' => (int) $target['id']]);
                return "Topic moved to {$target['name']}.";
            case 'delete':
                $this->forums->deleteTopic($topicId);
                return "Topic \"{$topic['title']}\" deleted.";
            default:
                throw new NotFoundException();
        }
    }

    public function deletePost(User $user, Player $player, int $postId): array
    {
        $post = $this->forums->post($postId) ?? throw new NotFoundException('That post does not exist.');
        $this->readableTopic($user, $player, (int) $post['topic_id']);

        if (!$this->isModerator($user)) {
            throw new AuthorizationException('Only forum moderators can delete posts.');
        }

        return ['post' => $post, 'topicDeleted' => $this->forums->deletePost($postId)];
    }

    private function authorizeRead(User $user, Player $player, string $access, ?int $gangId): void
    {
        $allowed = match ($access) {
            'public' => true,
            'staff' => $this->permissions->can($user, 'use_staff_forums'),
            'gang' => $gangId !== null && $gangId === $player->gangId,
            default => false,
        };

        if (!$allowed) {
            throw new AuthorizationException("You don't have access to that forum.");
        }
    }

    private function assertCanPost(int $userId): void
    {
        if (($reason = $this->banReason($userId)) !== null) {
            throw ValidationException::withMessages(['body' => $reason]);
        }
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
