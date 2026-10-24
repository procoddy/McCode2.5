<?php

declare(strict_types=1);

namespace App\Modules\Crimes\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\ValidationException;
use App\Modules\Crimes\Contracts\CrimeRepositoryInterface;
use App\Modules\Crimes\Services\CrimeService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;

final class CrimeController
{
    public function __construct(
        private readonly CrimeService $crimeService,
        private readonly CrimeRepositoryInterface $crimes,
        private readonly PlayerRepositoryInterface $players,
        private readonly Session $session,
    ) {
    }

    public function index(Request $request): Response
    {
        $player = $request->attribute('player');

        return view('pages.crimes.index', [
            'groups' => $this->crimes->groupsWithCrimes(),
            'crimeService' => $this->crimeService,
            'blockedReason' => $this->crimeService->blockedReason($player),
        ]);
    }

    /**
     * HTMX: returns the result box (hx-target="#crime-result") plus the updated
     * top bar. Without JavaScript: flashes the result and redirects to the list.
     */
    public function commit(Request $request, int $id): Response
    {
        $player = $request->attribute('player');

        try {
            $outcome = $this->crimeService->commit($player, $id);
        } catch (ValidationException $e) {
            if (!$request->isHtmx()) {
                throw $e;
            }

            return $this->result($request, ['error' => $e->errors()['crime'][0] ?? 'Something went wrong.'], 422);
        }

        if (!$request->isHtmx()) {
            $this->session->flash('status', trim($outcome->crime->initialText . ' ' . $outcome->text));

            return Response::redirect('/crimes');
        }

        return $this->result($request, ['outcome' => $outcome]);
    }

    private function result(Request $request, array $data, int $status = 200): Response
    {
        return view('partials.crimes.result', [
            'player' => $this->players->find($request->attribute('player')->id),
            ...$data,
        ], layout: null, status: $status);
    }
}
