<?php

declare(strict_types=1);

namespace App\Modules\Gangs\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Modules\Gangs\Repositories\GangRepository;
use App\Modules\Gangs\Services\GangService;

/**
 * Legacy yourgang.php: member pages, and the leaders' staff room.
 */
final class YourGangController
{
    public function __construct(
        private readonly GangRepository $gangs,
        private readonly GangService $service,
        private readonly Validator $validator,
        private readonly Session $session,
    ) {
    }

    public function home(Request $request): Response
    {
        $membership = $this->service->membership($request->attribute('player'));

        return view('pages.gang.home', [
            ...$membership,
            'events' => $this->gangs->events((int) $membership['gang']['id']),
        ]);
    }

    public function members(Request $request): Response
    {
        $membership = $this->service->membership($request->attribute('player'));

        return view('pages.gang.members', [...$membership, 'members' => $this->gangs->members((int) $membership['gang']['id'])]);
    }

    public function wars(Request $request): Response
    {
        $membership = $this->service->membership($request->attribute('player'));

        return view('pages.gang.wars', [...$membership, 'wars' => $this->gangs->wars((int) $membership['gang']['id'])]);
    }

    public function attackLogs(Request $request): Response
    {
        $membership = $this->service->membership($request->attribute('player'));

        return view('pages.gang.attack-logs', [...$membership, 'logs' => $this->gangs->attackLogs((int) $membership['gang']['id'])]);
    }

    public function crimes(Request $request): Response
    {
        $membership = $this->service->membership($request->attribute('player'));

        return view('pages.gang.crimes', [...$membership, 'logs' => $this->gangs->crimeLogs((int) $membership['gang']['id'])]);
    }

    public function forum(Request $request): Response
    {
        return Response::redirect('/forums/' . $this->service->forumId($request->attribute('player')));
    }

    public function donate(Request $request): Response
    {
        $data = $this->validator->validate($request->all(), ['money' => 'integer|min:0', 'crystals' => 'integer|min:0']);

        return $this->done('/gang', $this->service->donate($request->attribute('player'), (int) ($data['money'] ?? 0), (int) ($data['crystals'] ?? 0)));
    }

    public function leave(Request $request): Response
    {
        return $this->done('/gangs', $this->service->leave($request->attribute('player')));
    }

    public function kick(Request $request, int $id): Response
    {
        return $this->done('/gang/members', $this->service->kick($request->attribute('player'), $id));
    }

    public function staff(Request $request): Response
    {
        $membership = $this->service->membership($request->attribute('player'));
        $gangId = (int) $membership['gang']['id'];

        if (!$membership['isLeader']) {
            throw new \App\Exceptions\AuthorizationException('Only the president and vice-president can use the staff room.');
        }

        return view('pages.gang.staff', [
            ...$membership,
            'members' => $this->gangs->members($gangId),
            'applications' => $this->gangs->applications($gangId),
            'crimes' => $this->gangs->availableCrimes((int) $membership['gang']['member_count']),
            'wars' => $this->gangs->wars($gangId),
            'surrenders' => $this->gangs->surrendersTo($gangId),
            'otherGangs' => array_filter($this->gangs->all(), fn (array $g) => (int) $g['id'] !== $gangId),
        ]);
    }

    /** POST /gang/staff/{action}: one form each on the staff room page. */
    public function staffAction(Request $request, string $action): Response
    {
        $player = $request->attribute('player');
        $input = $request->all();
        $int = fn (string $field, int $min = 1) => (int) $this->validator->validate($input, [$field => "required|integer|min:{$min}"])[$field];

        $message = match ($action) {
            'vault' => $this->service->giveFromVault($player, $int('user_id'), $int('money', 0), $int('crystals', 0)),
            'vice-president' => $this->service->setVicePresident($player, $int('user_id')),
            'president' => $this->service->setPresident($player, $int('user_id')),
            'upgrade' => $this->service->upgradeCapacity($player, $int('slots')),
            'crime' => $this->service->planCrime($player, $int('crime_id')),
            'mass-payment' => $this->service->massPayment($player, $int('amount')),
            'announcement' => $this->service->setAnnouncement($player, trim((string) ($this->validator->validate($input, ['announcement' => 'string|max:5000'])['announcement'] ?? ''))),
            'details' => (function () use ($player, $input) {
                $data = $this->validator->validate($input, ['name' => 'required|string|max:255', 'description' => 'string|max:5000', 'prefix' => 'string|max:12']);

                return $this->service->updateDetails($player, trim($data['name']), trim((string) ($data['description'] ?? '')), trim((string) ($data['prefix'] ?? '')));
            })(),
            'mass-mail' => (function () use ($player, $input) {
                $data = $this->validator->validate($input, ['subject' => 'string|max:255', 'body' => 'required|string|max:5000']);

                return $this->service->massMail($player, (string) ($data['subject'] ?? ''), trim($data['body']));
            })(),
            'war' => $this->service->declareWar($player, $int('gang_id')),
            'surrender' => $this->service->offerSurrender($player, $int('war_id'), trim((string) ($this->validator->validate($input, ['message' => 'string|max:255'])['message'] ?? ''))),
            default => throw new NotFoundException(),
        };

        return $this->done('/gang/staff', $message);
    }

    public function decideApplication(Request $request, int $id, string $decision): Response
    {
        if (!in_array($decision, ['accept', 'decline'], true)) {
            throw new NotFoundException();
        }

        return $this->done('/gang/staff', $this->service->decideApplication($request->attribute('player'), $id, $decision === 'accept'));
    }

    public function acceptSurrender(Request $request, int $id): Response
    {
        return $this->done('/gang/staff', $this->service->acceptSurrender($request->attribute('player'), $id));
    }

    private function done(string $url, string $message): Response
    {
        $this->session->flash('status', $message);

        return Response::redirect($url);
    }
}
