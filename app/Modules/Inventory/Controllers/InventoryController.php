<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Exceptions\NotFoundException;
use App\Http\Concerns\RespondsWithPanel;
use App\Modules\Inventory\Models\EquipmentSlot;
use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;

/**
 * Use / equip / unequip post into #inventory-panel; sell and send post into
 * #item-panel on the item's page. Both fall back to redirects without JavaScript.
 */
final class InventoryController
{
    use RespondsWithPanel;

    public function __construct(
        private readonly InventoryService $service,
        private readonly InventoryRepository $inventory,
        private readonly PlayerRepositoryInterface $players,
        private readonly Validator $validator,
    ) {
    }

    public function index(Request $request): Response
    {
        return view('pages.inventory.index', $this->inventoryData($request->attribute('player')->id));
    }

    /** Legacy iteminfo.php, plus the sell / send / market forms when the player owns some. */
    public function show(Request $request, int $id): Response
    {
        return view('pages.inventory.show', $this->itemData($request, $id));
    }

    public function use(Request $request, int $id): Response
    {
        return $this->inventoryAction($request, fn () => $this->service->use($request->attribute('player'), $id));
    }

    public function equip(Request $request, int $id): Response
    {
        $slot = EquipmentSlot::tryFrom((string) $request->input('slot')) ?? throw new NotFoundException('That slot is not valid.');

        return $this->inventoryAction($request, fn () => $this->service->equip($request->attribute('player'), $id, $slot));
    }

    public function unequip(Request $request, string $slot): Response
    {
        $slot = EquipmentSlot::tryFrom($slot) ?? throw new NotFoundException('That slot is not valid.');

        return $this->inventoryAction($request, fn () => $this->service->unequip($request->attribute('player'), $slot));
    }

    public function sell(Request $request, int $id): Response
    {
        return $this->itemAction($request, $id, fn () => $this->service->sell(
            $request->attribute('player'),
            $id,
            $this->quantity($request, 'sell_quantity'),
            $request->ip(),
        ));
    }

    public function send(Request $request, int $id): Response
    {
        return $this->itemAction($request, $id, function () use ($request, $id): string {
            $data = $this->validator->validate($request->all(), [
                'send_quantity' => 'required|integer|min:1',
                'user_id' => 'required|integer|min:1',
            ]);

            return $this->service->send($request->attribute('player'), $id, (int) $data['send_quantity'], (int) $data['user_id'], $request->ip());
        });
    }

    private function inventoryAction(Request $request, callable $action): Response
    {
        return $this->panelAction(
            $request,
            '/inventory',
            $action,
            fn (array $data, int $status) => view('partials.inventory.panel', [
                ...$this->inventoryData($request->attribute('player')->id),
                'oob' => true,
                ...$data,
            ], layout: null, status: $status),
        );
    }

    private function itemAction(Request $request, int $id, callable $action): Response
    {
        $this->service->item($id);

        return $this->panelAction(
            $request,
            "/inventory/{$id}",
            $action,
            fn (array $data, int $status) => view('partials.inventory.item-panel', [
                ...$this->itemData($request, $id),
                'oob' => true,
                ...$data,
            ], layout: null, status: $status),
        );
    }

    private function inventoryData(int $playerId): array
    {
        return [
            'player' => $this->players->find($playerId),
            'entries' => $this->inventory->inventory($playerId),
            'equipped' => $this->inventory->equipped($playerId),
        ];
    }

    private function itemData(Request $request, int $id): array
    {
        $playerId = $request->attribute('player')->id;

        return [
            'item' => $this->service->item($id),
            'owned' => $this->inventory->quantity($playerId, $id),
            'player' => $this->players->find($playerId),
        ];
    }

    private function quantity(Request $request, string $field): int
    {
        return (int) $this->validator->validate($request->all(), [$field => 'required|integer|min:1'])[$field];
    }
}
