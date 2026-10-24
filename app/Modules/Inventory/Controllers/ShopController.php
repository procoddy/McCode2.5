<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Http\Concerns\RespondsWithPanel;
use App\Modules\Inventory\Repositories\ShopRepository;
use App\Modules\Inventory\Services\ShopService;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;

final class ShopController
{
    use RespondsWithPanel;

    public function __construct(
        private readonly ShopService $service,
        private readonly ShopRepository $shops,
        private readonly PlayerRepositoryInterface $players,
    ) {
    }

    public function index(Request $request): Response
    {
        return view('pages.shops.index', ['shops' => $this->shops->inCity($request->attribute('player')->cityId)]);
    }

    public function show(Request $request, int $id): Response
    {
        return view('pages.shops.show', $this->shopData($request, $id));
    }

    public function buy(Request $request, int $id, int $item, Validator $validator): Response
    {
        return $this->panelAction(
            $request,
            "/shops/{$id}",
            function () use ($request, $id, $item, $validator): string {
                $quantity = (int) $validator->validate($request->all(), ['quantity' => 'required|integer|min:1|max:1000'])['quantity'];

                return $this->service->buy($request->attribute('player'), $id, $item, $quantity, $request->ip());
            },
            fn (array $data, int $status) => view('partials.shops.panel', [
                ...$this->shopData($request, $id),
                'player' => $this->players->find($request->attribute('player')->id),
                'oob' => true,
                ...$data,
            ], layout: null, status: $status),
        );
    }

    private function shopData(Request $request, int $id): array
    {
        $shop = $this->service->shopFor($request->attribute('player'), $id);

        return ['shop' => $shop, 'stock' => $this->shops->stock($id)];
    }
}
