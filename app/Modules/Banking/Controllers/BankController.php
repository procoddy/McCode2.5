<?php

declare(strict_types=1);

namespace App\Modules\Banking\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Http\Concerns\RespondsWithPanel;
use App\Modules\Banking\Models\BankAccountType;
use App\Modules\Banking\Services\BankService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;

/**
 * Serves both banks; routes pass the account type key ('bank', 'cyber').
 */
final class BankController
{
    use RespondsWithPanel;

    public function __construct(
        private readonly BankService $bank,
        private readonly PlayerRepositoryInterface $players,
        private readonly Validator $validator,
    ) {
    }

    public function show(Request $request, string $type): Response
    {
        $account = BankAccountType::fromConfig($type);

        return view('pages.banking.show', ['account' => $account, 'player' => $request->attribute('player')]);
    }

    public function open(Request $request, string $type): Response
    {
        $account = BankAccountType::fromConfig($type);

        return $this->panelAction(
            $request,
            $account->url,
            fn () => $this->bank->open($request->attribute('player'), $account),
            fn (array $data, int $status) => $this->panel($request, $account, $data, $status),
        );
    }

    public function deposit(Request $request, string $type): Response
    {
        $account = BankAccountType::fromConfig($type);

        return $this->panelAction(
            $request,
            $account->url,
            function () use ($request, $account) {
                $amount = (int) $this->validator->validate($request->all(), ['deposit' => 'required|integer|min:1'])['deposit'];

                return $this->bank->deposit($request->attribute('player'), $account, $amount);
            },
            fn (array $data, int $status) => $this->panel($request, $account, $data, $status),
        );
    }

    public function withdraw(Request $request, string $type): Response
    {
        $account = BankAccountType::fromConfig($type);

        return $this->panelAction(
            $request,
            $account->url,
            function () use ($request, $account) {
                $amount = (int) $this->validator->validate($request->all(), ['withdraw' => 'required|integer|min:1'])['withdraw'];

                return $this->bank->withdraw($request->attribute('player'), $account, $amount);
            },
            fn (array $data, int $status) => $this->panel($request, $account, $data, $status),
        );
    }

    private function panel(Request $request, BankAccountType $account, array $data, int $status): Response
    {
        return view('partials.banking.panel', [
            'account' => $account,
            'player' => $this->players->find($request->attribute('player')->id),
            'oob' => true,
            ...$data,
        ], layout: null, status: $status);
    }
}
