<?php

declare(strict_types=1);

namespace App\Modules\Market\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Modules\Inventory\Repositories\InventoryRepository;
use App\Modules\Market\Repositories\MarketRepository;
use App\Modules\Market\Services\MarketService;

final class MarketController
{
    public function __construct(
        private readonly MarketService $service,
        private readonly MarketRepository $market,
        private readonly Validator $validator,
        private readonly Session $session,
    ) {
    }

    public function items(Request $request, InventoryRepository $inventory): Response
    {
        return view('pages.market.items', [
            'listings' => $this->market->itemListings(),
            'inventory' => $inventory->inventory($request->attribute('player')->id),
        ]);
    }

    public function listItem(Request $request): Response
    {
        $data = $this->validator->validate($request->all(), [
            'item_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|integer|min:1',
            'currency' => 'required|in:money,crystals',
        ]);

        return $this->done('/item-market', $this->service->listItem(
            $request->attribute('player'),
            (int) $data['item_id'],
            (int) $data['quantity'],
            (int) $data['price'],
            $data['currency'],
            $request->ip(),
        ));
    }

    public function buyItem(Request $request, int $id): Response
    {
        return $this->done('/item-market', $this->service->buyItem($request->attribute('player'), $id, $this->quantity($request), $request->ip()));
    }

    public function withdrawItem(Request $request, int $id): Response
    {
        return $this->done('/item-market', $this->service->withdrawItem($request->attribute('player'), $id, $request->ip()));
    }

    public function crystals(): Response
    {
        return view('pages.market.crystals', ['listings' => $this->market->crystalListings()]);
    }

    public function listCrystals(Request $request): Response
    {
        $data = $this->validator->validate($request->all(), [
            'quantity' => 'required|integer|min:1',
            'price_each' => 'required|integer|min:1',
        ]);

        return $this->done('/crystal-market', $this->service->listCrystals($request->attribute('player'), (int) $data['quantity'], (int) $data['price_each']));
    }

    public function buyCrystals(Request $request, int $id): Response
    {
        return $this->done('/crystal-market', $this->service->buyCrystals($request->attribute('player'), $id, $this->quantity($request)));
    }

    public function withdrawCrystals(Request $request, int $id): Response
    {
        return $this->done('/crystal-market', $this->service->withdrawCrystals($request->attribute('player'), $id));
    }

    private function quantity(Request $request): int
    {
        return (int) $this->validator->validate($request->all(), ['quantity' => 'required|integer|min:1'])['quantity'];
    }

    private function done(string $url, string $message): Response
    {
        $this->session->flash('status', $message);

        return Response::redirect($url);
    }
}
