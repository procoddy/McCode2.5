<?php

declare(strict_types=1);

namespace App\Modules\Banking\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Modules\Banking\Models\BankAccountType;
use App\Modules\Banking\Services\TransferService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Settings\Contracts\SettingsRepositoryInterface;

final class TransferController
{
    public function __construct(
        private readonly PlayerRepositoryInterface $players,
        private readonly SettingsRepositoryInterface $settings,
    ) {
    }

    public function form(Request $request, int $id, string $type): Response
    {
        [$account, $receiver] = $this->resolve($id, $type);

        return view('pages.banking.transfer', [
            'account' => $account,
            'receiver' => $receiver,
            'sender' => $request->attribute('player'),
        ]);
    }

    public function send(Request $request, int $id, string $type, TransferService $transfers, Validator $validator, Session $session, Database $db): Response
    {
        [$account, $receiver] = $this->resolve($id, $type);
        $amount = (int) $validator->validate($request->all(), ['amount' => 'required|integer|min:1'])['amount'];
        $receiverIp = $db->fetchColumn('SELECT last_login_ip FROM users WHERE id = ?', [$receiver->id]);

        $session->flash('status', $transfers->send($request->attribute('player'), $receiver, $account, $amount, $request->ip(), $receiverIp));

        return Response::redirect("/players/{$receiver->id}");
    }

    /** @return array{0: BankAccountType, 1: \App\Modules\Players\Models\Player} */
    private function resolve(int $id, string $type): array
    {
        if (!$this->settings->get('sendbank_on', true)) {
            throw new AuthorizationException('The game owner has disabled bank transfers.');
        }

        return [
            BankAccountType::fromConfig($type),
            $this->players->find($id) ?? throw new NotFoundException('That player does not exist.'),
        ];
    }
}
