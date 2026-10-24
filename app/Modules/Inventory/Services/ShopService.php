<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Exceptions\AuthorizationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Inventory\Repositories\ShopRepository;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;
use App\Support\Money;

/**
 * Legacy shops.php and itembuy.php.
 */
final class ShopService
{
    public function __construct(
        private readonly ShopRepository $shops,
        private readonly InventoryRepository $inventory,
        private readonly PlayerRepositoryInterface $players,
        private readonly Database $db,
        private readonly ClockInterface $clock,
    ) {
    }

    /** A shop in the player's city. */
    public function shopFor(Player $player, int $shopId): array
    {
        $shop = $this->shops->find($shopId) ?? throw new NotFoundException('That shop does not exist.');

        if ((int) $shop['city_id'] !== $player->cityId) {
            throw new AuthorizationException('That shop is in another city.');
        }

        return $shop;
    }

    public function buy(Player $player, int $shopId, int $itemId, int $quantity, string $ip): string
    {
        $shop = $this->shopFor($player, $shopId);
        $item = $this->inventory->findItem($itemId);

        if ($item === null || !$item->buyable || !$this->shops->sells($shopId, $itemId)) {
            throw ValidationException::withMessages(['quantity' => "{$shop['name']} doesn't sell that."]);
        }

        $cost = $item->buyPrice * $quantity;

        $this->db->transaction(function () use ($player, $item, $quantity, $cost, $ip): void {
            if (!$this->players->changeCurrency($player->id, 'money', -$cost)) {
                throw ValidationException::withMessages(['quantity' => 'You need ' . Money::of($cost)->format() . " to buy {$quantity} {$item->name}."]);
            }

            $this->inventory->add($player->id, $item->id, $quantity);
            $this->inventory->log('shop_buy', [
                'user_id' => $player->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'price' => $cost,
                'user_ip' => $ip,
            ], $this->clock->now()->format('Y-m-d H:i:s'));
        });

        return "You bought {$quantity} {$item->name} for " . Money::of($cost)->format() . '.';
    }
}
