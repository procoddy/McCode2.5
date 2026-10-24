<?php

declare(strict_types=1);

namespace App\Modules\CrystalTemple\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Http\Concerns\RespondsWithPanel;
use App\Modules\CrystalTemple\Services\CrystalTempleService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;

final class CrystalTempleController
{
    use RespondsWithPanel;

    private const URL = '/crystal-temple';

    public function __construct(
        private readonly CrystalTempleService $temple,
        private readonly PlayerRepositoryInterface $players,
        private readonly Validator $validator,
    ) {
    }

    public function index(Request $request): Response
    {
        return view('pages.crystal-temple.index', ['temple' => $this->temple, 'player' => $request->attribute('player')]);
    }

    public function refill(Request $request): Response
    {
        return $this->panelAction(
            $request,
            self::URL,
            fn () => $this->temple->refill($request->attribute('player')),
            fn (array $data, int $status) => $this->panel($request, $data, $status),
        );
    }

    public function iq(Request $request): Response
    {
        return $this->panelAction(
            $request,
            self::URL,
            fn () => $this->temple->tradeForIq($request->attribute('player'), $this->crystals($request, 'iq_crystals')),
            fn (array $data, int $status) => $this->panel($request, $data, $status),
        );
    }

    public function money(Request $request): Response
    {
        return $this->panelAction(
            $request,
            self::URL,
            fn () => $this->temple->tradeForMoney($request->attribute('player'), $this->crystals($request, 'money_crystals')),
            fn (array $data, int $status) => $this->panel($request, $data, $status),
        );
    }

    private function crystals(Request $request, string $field): int
    {
        return (int) $this->validator->validate($request->all(), [$field => 'required|integer|min:1'])[$field];
    }

    private function panel(Request $request, array $data, int $status): Response
    {
        return view('partials.crystal-temple.panel', [
            'temple' => $this->temple,
            'player' => $this->players->find($request->attribute('player')->id),
            'oob' => true,
            ...$data,
        ], layout: null, status: $status);
    }
}
