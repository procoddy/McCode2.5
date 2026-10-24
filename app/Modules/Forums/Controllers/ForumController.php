<?php

declare(strict_types=1);

namespace App\Modules\Forums\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Modules\Forums\Repositories\ForumRepository;
use App\Modules\Forums\Services\ForumService;

final class ForumController
{
    public function __construct(
        private readonly ForumService $service,
        private readonly ForumRepository $forums,
        private readonly Validator $validator,
        private readonly Session $session,
    ) {
    }

    public function index(Request $request): Response
    {
        return view('pages.forums.index', ['forums' => $this->service->visibleForums($request->attribute('user'), $request->attribute('player'))]);
    }

    public function forum(Request $request, int $id): Response
    {
        $forum = $this->service->readableForum($request->attribute('user'), $request->attribute('player'), $id);

        return view('pages.forums.forum', [
            'forum' => $forum,
            'topics' => $this->forums->topics($id, (int) $request->query('page', 1)),
            'banReason' => $this->service->banReason($request->attribute('player')->id),
        ]);
    }

    public function topic(Request $request, int $id): Response
    {
        $user = $request->attribute('user');
        $player = $request->attribute('player');
        $topic = $this->service->readableTopic($user, $player, $id);

        return view('pages.forums.topic', [
            'topic' => $topic,
            'posts' => $this->forums->posts($id, (int) $request->query('page', 1)),
            'isModerator' => $this->service->isModerator($user),
            'banReason' => $this->service->banReason($player->id),
            'forums' => $this->service->isModerator($user) ? $this->service->visibleForums($user, $player) : [],
        ]);
    }

    public function createTopic(Request $request, int $id): Response
    {
        $data = $this->validator->validate($request->all(), [
            'title' => 'required|string|max:' . ForumService::TITLE_MAX,
            'description' => 'string|max:255',
            'body' => 'required|string|max:' . ForumService::BODY_MAX,
        ]);
        $topicId = $this->service->createTopic(
            $request->attribute('user'),
            $request->attribute('player'),
            $id,
            trim($data['title']),
            trim((string) ($data['description'] ?? '')),
            trim($data['body']),
        );

        return Response::redirect("/forums/topics/{$topicId}");
    }

    public function reply(Request $request, int $id): Response
    {
        $body = trim($this->validator->validate($request->all(), ['body' => 'required|string|max:' . ForumService::BODY_MAX])['body']);
        $postId = $this->service->reply($request->attribute('user'), $request->attribute('player'), $id, $body);

        return Response::redirect("/forums/topics/{$id}?page=" . $this->forums->pageOf($id, $postId) . "#post-{$postId}");
    }

    public function editPost(Request $request, int $id): Response
    {
        return view('pages.forums.edit-post', [
            'post' => $this->service->editablePost($request->attribute('user'), $request->attribute('player'), $id),
        ]);
    }

    public function updatePost(Request $request, int $id): Response
    {
        $body = trim($this->validator->validate($request->all(), ['body' => 'required|string|max:' . ForumService::BODY_MAX])['body']);
        $post = $this->service->editPost($request->attribute('user'), $request->attribute('player'), $id, $body);
        $topicId = (int) $post['topic_id'];

        return Response::redirect("/forums/topics/{$topicId}?page=" . $this->forums->pageOf($topicId, $id) . "#post-{$id}");
    }

    public function deletePost(Request $request, int $id): Response
    {
        $result = $this->service->deletePost($request->attribute('user'), $request->attribute('player'), $id);
        $this->session->flash('status', $result['topicDeleted'] ? 'Post deleted, and the topic with it.' : 'Post deleted.');

        return Response::redirect($result['topicDeleted'] ? "/forums/{$result['post']['forum_id']}" : "/forums/topics/{$result['post']['topic_id']}");
    }

    public function moderate(Request $request, int $id, string $action): Response
    {
        $topic = $this->service->readableTopic($request->attribute('user'), $request->attribute('player'), $id);
        $message = $this->service->moderate($request->attribute('user'), $request->attribute('player'), $id, $action, (int) $request->input('forum_id') ?: null);
        $this->session->flash('status', $message);

        return Response::redirect($action === 'delete' ? "/forums/{$topic['forum_id']}" : "/forums/topics/{$id}");
    }
}
