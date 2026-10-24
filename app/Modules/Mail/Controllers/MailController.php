<?php

declare(strict_types=1);

namespace App\Modules\Mail\Controllers;

use App\Contracts\ClockInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Http\Responses\HtmlResponse;
use App\Modules\Mail\Repositories\MailRepository;
use App\Modules\Mail\Services\MailService;

/**
 * Legacy mailbox.php and dlarchive.php.
 */
final class MailController
{
    public function __construct(
        private readonly MailRepository $mail,
        private readonly MailService $service,
        private readonly Validator $validator,
        private readonly Session $session,
        private readonly ClockInterface $clock,
    ) {
    }

    public function inbox(Request $request): Response
    {
        return view('pages.mail.index', [
            'box' => 'inbox',
            'messages' => $this->mail->inbox($request->attribute('player')->id, (int) $request->query('page', 1)),
        ]);
    }

    public function outbox(Request $request): Response
    {
        return view('pages.mail.index', [
            'box' => 'outbox',
            'messages' => $this->mail->outbox($request->attribute('player')->id, (int) $request->query('page', 1)),
        ]);
    }

    public function compose(Request $request): Response
    {
        $replyTo = $request->query('reply') ? $this->mail->findFor((int) $request->query('reply'), $request->attribute('player')->id) : null;

        return view('pages.mail.compose', [
            'to' => $replyTo['sender_id'] ?? $request->query('to', ''),
            'subject' => $replyTo === null ? '' : (str_starts_with($replyTo['subject'], 'Re: ') ? $replyTo['subject'] : "Re: {$replyTo['subject']}"),
            'banReason' => $this->service->banReason($request->attribute('player')->id),
        ]);
    }

    public function send(Request $request): Response
    {
        $data = $this->validator->validate($request->all(), [
            'to' => 'required|integer|min:1',
            'subject' => 'string|max:' . MailService::SUBJECT_MAX,
            'body' => 'required|string|max:' . MailService::BODY_MAX,
        ]);

        $this->service->send($request->attribute('player')->id, (int) $data['to'], (string) ($data['subject'] ?? ''), trim((string) $data['body']));
        $this->session->flash('status', 'Message sent.');

        return Response::redirect('/mail/outbox');
    }

    public function show(Request $request, int $id): Response
    {
        $userId = $request->attribute('player')->id;
        $message = $this->mail->findFor($id, $userId) ?? throw new NotFoundException('That message does not exist.');

        if ((int) $message['recipient_id'] === $userId && $message['read_at'] === null) {
            $this->mail->markRead($id, $userId, $this->clock->now()->format('Y-m-d H:i:s'));
            $response = view('pages.mail.show', ['message' => $message, 'userId' => $userId]);

            return $response->withHeader('HX-Trigger', 'mail-read');
        }

        return view('pages.mail.show', ['message' => $message, 'userId' => $userId]);
    }

    public function destroy(Request $request, int $id): Response
    {
        if (!$this->mail->delete($id, $request->attribute('player')->id)) {
            throw new NotFoundException('That message is not in your inbox.');
        }
        $this->session->flash('status', 'Message deleted.');

        return Response::redirect('/mail');
    }

    public function destroyAll(Request $request): Response
    {
        $count = $this->mail->deleteAll($request->attribute('player')->id);
        $this->session->flash('status', "Deleted {$count} " . ($count === 1 ? 'message' : 'messages') . '.');

        return Response::redirect('/mail');
    }

    public function unreadBadge(Request $request): Response
    {
        return view('partials.mail.badge', ['count' => $this->mail->unreadCount($request->attribute('player')->id)], layout: null);
    }

    /** Legacy dlarchive.php: the whole inbox or outbox as a downloadable HTML file. */
    public function archive(Request $request, string $box): Response
    {
        if (!in_array($box, ['inbox', 'outbox'], true)) {
            throw new NotFoundException();
        }

        $userId = $request->attribute('player')->id;
        $html = app(\App\Core\View::class)->render('partials.mail.archive', [
            'box' => $box,
            'messages' => $this->mail->archive($userId, $box),
        ]);

        return new HtmlResponse($html, 200, [
            'Content-Disposition' => "attachment; filename=\"{$box}_archive_{$userId}_" . $this->clock->now()->format('Ymd_His') . '.htm"',
        ]);
    }
}
