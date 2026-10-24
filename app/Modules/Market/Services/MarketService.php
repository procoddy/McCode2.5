<?php

declare(strict_types=1);

namespace App\Modules\Market\Services;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Events\Services\EventService;
use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Market\Repositories\MarketRepository;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;
use App\Support\Money;

/**
 * Legacy itemmarket.php / imadd.php and cmarket.php. Items and crystals are
 * held by the listing until bought or withdrawn.
 */
final class MarketService
{
    public function __construct(
        private readonly MarketRepository $market,
        private readonly InventoryRepository $inventory,
        private readonly PlayerRepositoryInterface $players,
        private readonly EventService $events,
        private readonly Database $db,
        private readonly ClockInterface $clock,
    ) {
    }

    public function listItem(Player $seller, int $itemId, int $quantity, int $price, string $currency, string $ip): string
    {
        $item = $this->inventory->findItem($itemId) ?? throw new NotFoundException('That item does not exist.');

        $this->db->transaction(function () use ($seller, $item, $quantity, $price, $currency, $ip): void {
            if (!$this->inventory->remove($seller->id, $item->id, $quantity)) {
                throw ValidationException::withMessages(['quantity' => "You don't have that many {$item->name}."]);
            }

            $this->market->create('item', [
                'seller_id' => $seller->id,
                'item_id' => $item->id,
                'price' => $price,
                'currency' => $currency,
                'quantity' => $quantity,
                'created_at' => $this->now(),
            ]);
            $this->inventory->log('market_add', [
                'user_id' => $seller->id, 'item_id' => $item->id, 'quantity' => $quantity,
                'price' => $price, 'currency' => $currency, 'user_ip' => $ip,
            ], $this->now());
        });

        return "You listed {$quantity} {$item->name} for " . $this->price($price, $currency) . ' each.';
    }

    public function buyItem(Player $buyer, int $listingId, int $quantity, string $ip): string
    {
        $listing = $this->listing('item', $listingId, $buyer);
        $item = $this->inventory->findItem((int) $listing['item_id']) ?? throw new NotFoundException('That item no longer exists.');
        $total = (int) $listing['price'] * $quantity;
        $currency = (string) $listing['currency'];

        $this->db->transaction(function () use ($buyer, $listing, $item, $quantity, $total, $currency, $ip): void {
            if (!$this->market->take('item', (int) $listing['id'], $quantity)) {
                throw ValidationException::withMessages(['quantity' => 'There are not that many left in this listing.']);
            }
            if (!$this->players->changeCurrency($buyer->id, $currency, -$total)) {
                throw ValidationException::withMessages(['quantity' => 'You need ' . $this->price($total, $currency) . ' to buy that many.']);
            }

            $this->players->changeCurrency((int) $listing['seller_id'], $currency, $total);
            $this->inventory->add($buyer->id, $item->id, $quantity);
            $this->inventory->log('market_buy', [
                'user_id' => $buyer->id, 'other_user_id' => (int) $listing['seller_id'], 'item_id' => $item->id,
                'quantity' => $quantity, 'price' => $total, 'currency' => $currency, 'user_ip' => $ip,
            ], $this->now());
            $this->events->add((int) $listing['seller_id'], "{$buyer->username} bought {$quantity} of your {$item->name} from the item market for " . $this->price($total, $currency) . '.');
        });

        return "You bought {$quantity} {$item->name} for " . $this->price($total, $currency) . '.';
    }

    public function withdrawItem(Player $seller, int $listingId, string $ip): string
    {
        return $this->db->transaction(function () use ($seller, $listingId, $ip): string {
            $listing = $this->market->withdraw('item', $listingId, $seller->id)
                ?? throw new NotFoundException('That listing does not exist, or it is not yours.');

            $this->inventory->add($seller->id, (int) $listing['item_id'], (int) $listing['quantity']);
            $this->inventory->log('market_remove', [
                'user_id' => $seller->id, 'item_id' => (int) $listing['item_id'], 'quantity' => (int) $listing['quantity'],
                'price' => (int) $listing['price'], 'currency' => $listing['currency'], 'user_ip' => $ip,
            ], $this->now());

            return 'The items were returned to your inventory.';
        });
    }

    public function listCrystals(Player $seller, int $quantity, int $priceEach): string
    {
        $this->db->transaction(function () use ($seller, $quantity, $priceEach): void {
            if (!$this->players->changeCurrency($seller->id, 'crystals', -$quantity)) {
                throw ValidationException::withMessages(['quantity' => 'You are trying to add more ' . $this->crystals() . ' than you have.']);
            }

            $this->market->create('crystal', [
                'seller_id' => $seller->id,
                'quantity' => $quantity,
                'price_each' => $priceEach,
                'created_at' => $this->now(),
            ]);
        });

        return "You listed {$quantity} {$this->crystals()} for " . Money::of($priceEach)->format() . ' each.';
    }

    public function buyCrystals(Player $buyer, int $listingId, int $quantity): string
    {
        $listing = $this->listing('crystal', $listingId, $buyer);
        $total = (int) $listing['price_each'] * $quantity;

        $this->db->transaction(function () use ($buyer, $listing, $quantity, $total): void {
            if (!$this->market->take('crystal', (int) $listing['id'], $quantity)) {
                throw ValidationException::withMessages(['quantity' => 'You selected more ' . $this->crystals() . ' than there are in this listing.']);
            }
            if (!$this->players->changeCurrency($buyer->id, 'money', -$total)) {
                throw ValidationException::withMessages(['quantity' => 'You need ' . Money::of($total)->format() . ' to buy that many.']);
            }

            $this->players->changeCurrency($buyer->id, 'crystals', $quantity);
            $this->players->changeCurrency((int) $listing['seller_id'], 'money', $total);
            $this->events->add((int) $listing['seller_id'], "{$buyer->username} bought {$quantity} of your {$this->crystals()} from the market for " . Money::of($total)->format() . '.');
        });

        return "You bought {$quantity} {$this->crystals()} for " . Money::of($total)->format() . '.';
    }

    public function withdrawCrystals(Player $seller, int $listingId): string
    {
        return $this->db->transaction(function () use ($seller, $listingId): string {
            $listing = $this->market->withdraw('crystal', $listingId, $seller->id)
                ?? throw new NotFoundException('That listing does not exist, or it is not yours.');

            $this->players->changeCurrency($seller->id, 'crystals', (int) $listing['quantity']);

            return ucfirst($this->crystals()) . ' removed from the market.';
        });
    }

    private function listing(string $market, int $id, Player $buyer): array
    {
        $listing = $this->market->find($market, $id) ?? throw new NotFoundException('That listing does not exist, or it has already been bought.');

        if ((int) $listing['seller_id'] === $buyer->id) {
            throw ValidationException::withMessages(['quantity' => "You can't buy your own listing; remove it instead."]);
        }

        return $listing;
    }

    private function price(int $amount, string $currency): string
    {
        return $currency === 'crystals' ? number_format($amount) . ' ' . $this->crystals() : Money::of($amount)->format();
    }

    private function crystals(): string
    {
        return strtolower((string) config('game.crystals_name', 'crystals'));
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
