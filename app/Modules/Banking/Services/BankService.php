<?php

declare(strict_types=1);

namespace App\Modules\Banking\Services;

use App\Exceptions\ValidationException;
use App\Modules\Banking\Models\BankAccountType;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;
use App\Support\Money;

/**
 * Legacy bank.php and cyberbank.php. Each method returns the message to show
 * or throws a ValidationException keyed by the form field.
 */
final class BankService
{
    public function __construct(private readonly PlayerRepositoryInterface $players)
    {
    }

    public function open(Player $player, BankAccountType $type): string
    {
        if ($player->balance($type->column) !== null) {
            throw ValidationException::withMessages(['account' => "You already have a {$type->name} account."]);
        }

        if (!$this->players->openAccount($player->id, $type->column, $type->cost)) {
            throw ValidationException::withMessages([
                'account' => 'You need ' . Money::of($type->cost)->format() . ' to open an account.',
            ]);
        }

        return "Your {$type->name} account is open. It cost " . Money::of($type->cost)->format() . '.';
    }

    public function deposit(Player $player, BankAccountType $type, int $amount): string
    {
        $this->requireAccount($player, $type, 'deposit');
        $fee = $type->depositFee($amount);

        if ($amount > $player->money->amount() || !$this->players->deposit($player->id, $type->column, $amount, $amount - $fee)) {
            throw ValidationException::withMessages(['deposit' => 'You do not have enough money to deposit this amount.']);
        }

        return 'You deposited ' . Money::of($amount)->format()
            . ($fee > 0 ? '. After the ' . Money::of($fee)->format() . ' fee, ' : '; ')
            . Money::of($amount - $fee)->format() . ' was added to your account.';
    }

    public function withdraw(Player $player, BankAccountType $type, int $amount): string
    {
        $balance = $this->requireAccount($player, $type, 'withdraw');
        $fee = $type->withdrawFee($amount);

        // Legacy cyberbank.php only removed the amount after fees from the balance; the full amount is taken here.
        if ($amount > $balance || !$this->players->withdraw($player->id, $type->column, $amount, $amount - $fee)) {
            throw ValidationException::withMessages(['withdraw' => 'You do not have enough banked money to withdraw this amount.']);
        }

        return 'You withdrew ' . Money::of($amount)->format()
            . ($fee > 0 ? ' and received ' . Money::of($amount - $fee)->format() . ' after the ' . Money::of($fee)->format() . ' fee.' : '.');
    }

    private function requireAccount(Player $player, BankAccountType $type, string $field): int
    {
        return $player->balance($type->column)
            ?? throw ValidationException::withMessages([$field => "Open a {$type->name} account first."]);
    }
}
