<?php

declare(strict_types=1);

namespace App\Modules\Banking\Services;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Exceptions\ValidationException;
use App\Modules\Banking\Models\BankAccountType;
use App\Modules\Events\Services\EventService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;
use App\Support\Money;

/**
 * Legacy sendbank.php / sendcyber.php: move banked money to another player.
 */
final class TransferService
{
    public function __construct(
        private readonly PlayerRepositoryInterface $players,
        private readonly EventService $events,
        private readonly Database $db,
        private readonly ClockInterface $clock,
    ) {
    }

    public function send(Player $sender, Player $receiver, BankAccountType $type, int $amount, string $senderIp, ?string $receiverIp): string
    {
        if ($sender->id === $receiver->id) {
            throw ValidationException::withMessages(['amount' => "You can't send money to yourself."]);
        }
        if ($sender->balance($type->column) === null || $receiver->balance($type->column) === null) {
            throw ValidationException::withMessages(['amount' => "You and {$receiver->username} both need a {$type->name} account."]);
        }
        if ($amount > $sender->balance($type->column)) {
            throw ValidationException::withMessages(['amount' => 'You do not have that much in your account.']);
        }

        $this->db->transaction(function () use ($sender, $receiver, $type, $amount, $senderIp, $receiverIp): void {
            if (!$this->players->transferBalance($sender->id, $receiver->id, $type->column, $amount)) {
                throw ValidationException::withMessages(['amount' => 'You do not have that much in your account.']);
            }

            $this->db->insert('transfer_logs', [
                'type' => $type->key === 'cyber' ? 'cyber' : 'bank',
                'from_user_id' => $sender->id,
                'to_user_id' => $receiver->id,
                'amount' => $amount,
                'from_ip' => $senderIp,
                'to_ip' => $receiverIp,
                'created_at' => $this->clock->now()->format('Y-m-d H:i:s'),
            ]);

            $this->events->add(
                $receiver->id,
                'You received ' . Money::of($amount)->format() . " into your {$type->name} account from {$sender->username}.",
            );
        });

        return 'You sent ' . Money::of($amount)->format() . " to {$receiver->username}'s {$type->name} account.";
    }
}
