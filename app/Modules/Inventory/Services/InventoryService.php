<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Services;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Modules\Events\Services\EventService;
use App\Modules\Inventory\Models\EquipmentSlot;
use App\Modules\Inventory\Models\Item;
use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Models\Player;
use App\Support\Money;

/**
 * Legacy inventory.php, itemuse.php, equip.php, unequip.php, itemsell.php, itemsend.php.
 * Each method returns the message to show, or throws a ValidationException.
 */
final class InventoryService
{
    public function __construct(
        private readonly InventoryRepository $inventory,
        private readonly PlayerRepositoryInterface $players,
        private readonly EventService $events,
        private readonly Database $db,
        private readonly ClockInterface $clock,
    ) {
    }

    public function item(int $id): Item
    {
        return $this->inventory->findItem($id) ?? throw new NotFoundException('That item does not exist.');
    }

    public function use(Player $player, int $itemId): string
    {
        $item = $this->item($itemId);

        if (!$item->isUsable()) {
            throw ValidationException::withMessages(['item' => "The {$item->name} can't be used."]);
        }

        $now = $this->now();
        $deltas = [];
        foreach ($item->effects as $effect) {
            $deltas[$effect->stat] = ($deltas[$effect->stat] ?? 0) + $effect->delta($player, $now);
        }

        if (!$this->inventory->useItem($player->id, $item->id, $deltas, "Used {$this->article($item->name)} {$item->name}", $now)) {
            throw ValidationException::withMessages(['item' => "You don't have any {$item->name} left."]);
        }

        return "You used the {$item->name}.";
    }

    public function equip(Player $player, int $itemId, EquipmentSlot $slot): string
    {
        $item = $this->item($itemId);

        if (!$slot->accepts($item)) {
            throw ValidationException::withMessages(['item' => "The {$item->name} can't go in your " . strtolower($slot->label()) . ' slot.']);
        }
        if (!$this->inventory->equip($player->id, $slot, $item->id)) {
            throw ValidationException::withMessages(['item' => "You don't have a {$item->name}."]);
        }

        return "You equipped the {$item->name} as your " . strtolower($slot->label()) . '.';
    }

    public function unequip(Player $player, EquipmentSlot $slot): string
    {
        if ($this->inventory->unequip($player->id, $slot) === null) {
            throw ValidationException::withMessages(['item' => 'You do not have anything equipped in this slot.']);
        }

        return "The item in your {$slot->label()} slot was unequipped.";
    }

    public function sell(Player $player, int $itemId, int $quantity, string $ip): string
    {
        $item = $this->item($itemId);
        $value = $item->sellPrice * $quantity;

        $this->db->transaction(function () use ($player, $item, $quantity, $value, $ip): void {
            if (!$this->inventory->remove($player->id, $item->id, $quantity)) {
                throw ValidationException::withMessages(['quantity' => "You don't have that many {$item->name}."]);
            }

            $this->players->changeCurrency($player->id, 'money', $value);
            $this->inventory->log('shop_sell', [
                'user_id' => $player->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'price' => $value,
                'user_ip' => $ip,
            ], $this->now());
        });

        return "You sold {$quantity} {$item->name} for " . Money::of($value)->format() . '.';
    }

    public function send(Player $sender, int $itemId, int $quantity, int $receiverId, string $ip): string
    {
        $item = $this->item($itemId);
        $receiver = $this->players->find($receiverId);

        if ($receiver === null) {
            throw ValidationException::withMessages(['user_id' => 'That player does not exist.']);
        }
        if ($receiver->id === $sender->id) {
            throw ValidationException::withMessages(['user_id' => "You can't send items to yourself."]);
        }

        $this->db->transaction(function () use ($sender, $receiver, $item, $quantity, $ip): void {
            if (!$this->inventory->remove($sender->id, $item->id, $quantity)) {
                throw ValidationException::withMessages(['quantity' => "You don't have that many {$item->name}."]);
            }

            $this->inventory->add($receiver->id, $item->id, $quantity);
            $this->inventory->log('transfer', [
                'user_id' => $sender->id,
                'other_user_id' => $receiver->id,
                'item_id' => $item->id,
                'quantity' => $quantity,
                'user_ip' => $ip,
            ], $this->now());
            $this->events->add($receiver->id, "You received {$quantity} {$item->name} from {$sender->username}.");
        });

        return "You sent {$quantity} {$item->name} to {$receiver->username}.";
    }

    private function article(string $name): string
    {
        return preg_match('/^[aeiou]/i', $name) ? 'an' : 'a';
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
