<?php

declare(strict_types=1);

namespace App\Modules\Banking\Models;

use App\Exceptions\NotFoundException;

/**
 * One kind of account (Bank, Cyber Bank), defined in config/game.php.
 */
final class BankAccountType
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $url,
        public readonly string $column,
        public readonly int $cost,
        public readonly float $depositFeePercent,
        public readonly int $depositMaxFee,
        public readonly float $withdrawFeePercent,
        public readonly int $withdrawMaxFee,
        public readonly float $dailyInterestPercent,
    ) {
    }

    public static function fromConfig(string $key): self
    {
        $c = config("game.banks.{$key}") ?? throw new NotFoundException();

        return new self(
            key: $key,
            name: $c['name'],
            url: $c['url'],
            column: $c['column'],
            cost: $c['cost'],
            depositFeePercent: $c['deposit_fee_percent'],
            depositMaxFee: $c['deposit_max_fee'],
            withdrawFeePercent: $c['withdraw_fee_percent'],
            withdrawMaxFee: $c['withdraw_max_fee'],
            dailyInterestPercent: $c['daily_interest_percent'],
        );
    }

    /** @return list<self> */
    public static function all(): array
    {
        return array_map(self::fromConfig(...), array_keys(config('game.banks', [])));
    }

    public function depositFee(int $amount): int
    {
        return min((int) ceil($amount * $this->depositFeePercent / 100), $this->depositMaxFee);
    }

    public function withdrawFee(int $amount): int
    {
        return min((int) ceil($amount * $this->withdrawFeePercent / 100), $this->withdrawMaxFee);
    }
}
