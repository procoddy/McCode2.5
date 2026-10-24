<?php

declare(strict_types=1);

namespace App\Modules\Announcements\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\ValidationException;
use App\Modules\Announcements\Services\AnnouncementService;

final class AdminAnnouncementController
{
    private const RULES = ['body' => 'required|string|max:5000'];

    public function __construct(
        private readonly AnnouncementService $announcements,
        private readonly Validator $validator,
        private readonly Session $session,
    ) {
    }

    public function index(): Response
    {
        return view('pages.administration.announcements', [
            'announcements' => $this->announcements->latest(),
        ], 'admin');
    }

    /**
     * The form posts with hx-post and hx-target="#announcements-panel", so an
     * HTMX request gets just the panel back: with errors (422) or with the new
     * announcement at the top of the list. Without JavaScript the normal
     * redirect flow is used.
     */
    public function store(Request $request): Response
    {
        $input = ['body' => trim((string) $request->input('body', ''))];

        try {
            $data = $this->validator->validate($input, self::RULES);
        } catch (ValidationException $e) {
            if (!$request->isHtmx()) {
                throw $e;
            }

            return $this->panel(['errors' => $e->errors(), 'body' => $input['body']], 422);
        }

        $this->announcements->post($request->attribute('user'), $data['body']);

        if (!$request->isHtmx()) {
            $this->session->flash('status', 'Announcement posted.');

            return Response::redirect('/admin/announcements');
        }

        return $this->panel(['message' => 'Announcement posted.']);
    }

    private function panel(array $data, int $status = 200): Response
    {
        return view('partials.announcements.admin-panel', [
            'announcements' => $this->announcements->latest(),
            'errors' => [],
            'body' => '',
            'message' => null,
            ...$data,
        ], layout: null, status: $status);
    }
}
