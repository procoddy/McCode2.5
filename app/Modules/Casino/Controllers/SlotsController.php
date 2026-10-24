<?php

declare(strict_types=1);

namespace App\Modules\Casino\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Exceptions\ValidationException;
use App\Modules\Casino\Services\SlotsService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;

final class SlotsController
{
    public function __construct(
        private readonly SlotsService $slots,
        private readonly PlayerRepositoryInterface $players,
        private readonly Validator $validator,
        private readonly Session $session,
    ) {
    }

    public function index(Request $request): Response
    {
        return view('pages.casino.slots', [
            'maxBet' => $this->slots->maxBet($request->attribute('player')),
            'result' => $this->session->pull('slots_result'),
        ]);
    }

    public function spin(Request $request): Response
    {
        try {
            $bet = (int) $this->validator->validate($request->all(), ['bet' => 'required|integer|min:1'])['bet'];
            $result = $this->slots->spin($request->attribute('player'), $bet);
        } catch (ValidationException $e) {
            if (!$request->isHtmx()) {
                throw $e;
            }
            app(View::class)->share('errors', $e->errors());

            return $this->panel($request, ['bet' => $request->input('bet')], 422);
        }

        if (!$request->isHtmx()) {
            $this->session->put('slots_result', $result);

            return Response::redirect('/slots');
        }

        return $this->panel($request, ['result' => $result, 'bet' => $result['bet']]);
    }

    private function panel(Request $request, array $data, int $status = 200): Response
    {
        $player = $this->players->find($request->attribute('player')->id);

        return view('partials.casino.slots-panel', [
            'player' => $player,
            'maxBet' => $this->slots->maxBet($player),
            'oob' => true,
            ...$data,
        ], layout: null, status: $status);
    }
}
