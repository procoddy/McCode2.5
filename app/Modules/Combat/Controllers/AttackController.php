<?php

declare(strict_types=1);

namespace App\Modules\Combat\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\ValidationException;
use App\Modules\Combat\Repositories\CombatRepository;
use App\Modules\Combat\Services\AttackService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;

/**
 * Each strike posts into #attack-panel (HTMX) or reloads the page (no JavaScript).
 */
final class AttackController
{
    public function __construct(
        private readonly AttackService $attacks,
        private readonly PlayerRepositoryInterface $players,
        private readonly Session $session,
    ) {
    }

    public function show(Request $request, int $id): Response
    {
        return view('pages.attack.show', $this->panelData($request, $id));
    }

    public function strike(Request $request, int $id): Response
    {
        try {
            $round = $this->attacks->strike($request->attribute('player'), $id, (int) $request->input('weapon_id'));
        } catch (ValidationException $e) {
            if (!$request->isHtmx()) {
                throw $e;
            }
            app(View::class)->share('errors', $e->errors());

            return $this->panel($request, $id, [], 422);
        }

        if (!$request->isHtmx()) {
            if ($round['message'] !== null) {
                $this->session->flash('status', $round['message']);
            }

            return Response::redirect("/attack/{$id}");
        }

        return $this->panel($request, $id, ['lastLines' => $round['lines'], 'message' => $round['message'], 'lost' => $round['result'] === 'lost']);
    }

    public function finish(Request $request, int $id): Response
    {
        $this->session->flash('status', $this->attacks->finish($request->attribute('player'), $id, (string) $request->input('choice')));

        return Response::redirect("/players/{$id}");
    }

    public function flee(Request $request, int $id): Response
    {
        $this->session->flash('status', $this->attacks->flee($request->attribute('player'), $id));

        return Response::redirect("/players/{$id}");
    }

    public function battleTent(Request $request, CombatRepository $combat): Response
    {
        return view('pages.attack.battle-tent', ['bots' => $combat->challengeBots($request->attribute('player')->id)]);
    }

    private function panel(Request $request, int $id, array $data, int $status = 200): Response
    {
        return view('partials.attack.panel', [
            ...$this->panelData($request, $id),
            'player' => $this->players->find($request->attribute('player')->id),
            'oob' => true,
            ...$data,
        ], layout: null, status: $status);
    }

    private function panelData(Request $request, int $id): array
    {
        [$me, $target] = $this->attacks->fighters($request->attribute('player'), $id);

        return [
            'me' => $me,
            'target' => $target,
            'blockedReason' => $this->attacks->blockedReason($me, $target),
            'awaitingChoice' => $this->attacks->awaitingChoice($target->id),
            'log' => $this->attacks->log($target->id),
            'now' => date('Y-m-d H:i:s'),
        ];
    }
}
