<?php

declare(strict_types=1);

namespace App\Modules\Staff\Controllers;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Community\Repositories\PollRepository;
use App\Modules\Donations\Services\DonatorPackService;
use App\Modules\Mail\Services\MailService;
use App\Modules\Staff\Contracts\StaffRoleRepositoryInterface;
use App\Modules\Staff\Repositories\StaffUserRepository;
use App\Modules\Staff\Services\StaffLog;

/**
 * Legacy staff_polls.php and staff_special.php (newspaper, donator packs, mass mail).
 */
final class CommunityAdminController
{
    public function __construct(
        private readonly Validator $validator,
        private readonly StaffLog $staffLog,
        private readonly Session $session,
        private readonly ClockInterface $clock,
    ) {
    }

    public function polls(PollRepository $polls): Response
    {
        return view('pages.staff.community.polls', ['active' => $polls->list(true)], 'admin');
    }

    public function startPoll(Request $request, PollRepository $polls): Response
    {
        $data = $this->validator->validate($request->all(), ['question' => 'required|string|max:255']);
        $choices = array_values(array_filter(array_map(
            fn ($choice) => mb_substr(trim((string) $choice), 0, 255),
            array_slice((array) $request->input('choices', []), 0, 10),
        ), fn (string $choice) => $choice !== ''));

        if (count($choices) < 2) {
            throw ValidationException::withMessages(['choices' => 'A poll needs at least two choices.']);
        }

        $polls->create(trim($data['question']), $choices, (bool) $request->input('hidden'), $this->now());
        $this->staffLog->add("Started poll: {$data['question']}");

        return $this->done('/admin/polls', 'Poll started.');
    }

    public function endPoll(int $id, PollRepository $polls): Response
    {
        $poll = $polls->find($id) ?? throw new NotFoundException('That poll does not exist.');
        $winner = $poll['total_votes'] > 0 ? array_keys($poll['votes'], max($poll['votes']))[0] : null;

        $polls->close($id, $winner, $this->now());
        $this->staffLog->add("Ended poll: {$poll['question']}");

        return $this->done('/admin/polls', 'Poll closed.');
    }

    public function newspaper(Database $db): Response
    {
        return view('pages.staff.community.newspaper', ['body' => (string) $db->fetchColumn('SELECT body FROM newspaper ORDER BY id DESC LIMIT 1')], 'admin');
    }

    public function saveNewspaper(Request $request, Database $db): Response
    {
        $body = trim($this->validator->validate($request->all(), ['body' => 'required|string|max:65535'])['body']);
        $db->insert('newspaper', ['body' => $body, 'updated_by_id' => $request->attribute('user')->id, 'updated_at' => $this->now()]);
        $this->staffLog->add('Updated the newspaper');

        return $this->done('/admin/newspaper', 'Newspaper updated.');
    }

    public function massMail(StaffRoleRepositoryInterface $roles): Response
    {
        return view('pages.staff.community.mass-mail', ['roles' => array_column($roles->all(), 'name', 'id')], 'admin');
    }

    public function sendMassMail(Request $request, MailService $mail): Response
    {
        $data = $this->validator->validate($request->all(), [
            'subject' => 'string|max:255',
            'body' => 'required|string|max:' . MailService::BODY_MAX,
            'role_id' => 'integer|min:0',
        ]);
        $roleId = (int) ($data['role_id'] ?? 0) ?: null;
        $count = $mail->sendToAll($request->attribute('user')->id, (string) ($data['subject'] ?? ''), trim($data['body']), $roleId);
        $this->staffLog->add("Sent mass mail to {$count} players" . ($roleId ? " (role #{$roleId})" : ''));

        return $this->done('/admin/mass-mail', "Mail sent to {$count} players.");
    }

    public function donatorPacks(DonatorPackService $packs): Response
    {
        return view('pages.staff.community.donator-packs', ['packs' => $packs->packs()], 'admin');
    }

    public function giveDonatorPack(Request $request, DonatorPackService $packs, StaffUserRepository $users): Response
    {
        $data = $this->validator->validate($request->all(), ['username' => 'required|string', 'pack' => 'required|string']);
        $player = $users->findByUsername(trim($data['username'])) ?? throw ValidationException::withMessages(['username' => 'No player with that username.']);

        if (!isset($packs->packs()[$data['pack']])) {
            throw ValidationException::withMessages(['pack' => 'Choose a pack from the list.']);
        }

        $name = $packs->grant((int) $player['id'], $data['pack']);
        $this->staffLog->add("Gave {$player['username']} [{$player['id']}] a donator pack: {$name}");

        return $this->done('/admin/donator-packs', "{$player['username']} was given {$name}.");
    }

    private function done(string $url, string $message): Response
    {
        $this->session->flash('status', $message);

        return Response::redirect($url);
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
