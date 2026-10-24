<?php
/**
 * @var array $shop
 * @var list<App\Modules\Inventory\Models\Item> $stock
 * @var App\Modules\Players\Models\Player $player
 * @var string|null $message
 * @var bool $oob
 */
use App\Support\Money;

$lastType = false;
?>
<section id="shop-panel" class="space-y-4">
    <?php if (!empty($message)): ?>
        <?= $this->component('alert', ['message' => $message, 'type' => 'success']) ?>
    <?php endif; ?>
    <?php if (!empty($errors['quantity'])): ?>
        <?= $this->component('alert', ['message' => $errors['quantity'][0], 'type' => 'error']) ?>
    <?php endif; ?>

    <?php if ($shop['description']): ?>
        <p class="text-gray-600 dark:text-gray-400"><?= e($shop['description']) ?></p>
    <?php endif; ?>

    <?php if ($stock === []): ?>
        <div class="card text-center text-gray-500 dark:text-gray-400">This shop has nothing for sale.</div>
    <?php else: ?>
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left dark:bg-gray-800/50">
                    <tr>
                        <th class="px-4 py-2 font-semibold">Item</th>
                        <th class="px-4 py-2 font-semibold">Price</th>
                        <th class="px-4 py-2 font-semibold">Sell value</th>
                        <th class="px-4 py-2 text-right font-semibold">Buy</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    <?php foreach ($stock as $item): ?>
                        <?php if ($item->typeName !== $lastType): $lastType = $item->typeName; ?>
                            <tr class="bg-gray-50/60 dark:bg-gray-800/30">
                                <th colspan="4" class="px-4 py-1.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase"><?= e($item->typeName ?? 'Other') ?></th>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="px-4 py-2">
                                <a href="/inventory/<?= $item->id ?>" class="link font-medium"><?= e($item->name) ?></a>
                                <?php if ($item->description !== ''): ?>
                                    <p class="text-gray-500 dark:text-gray-400"><?= e($item->description) ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap"><?= e(Money::of($item->buyPrice)->format()) ?></td>
                            <td class="px-4 py-2 whitespace-nowrap"><?= e($item->sellValue()->format()) ?></td>
                            <td class="px-4 py-2">
                                <form method="post" action="/shops/<?= (int) $shop['id'] ?>/buy/<?= $item->id ?>"
                                      hx-post="/shops/<?= (int) $shop['id'] ?>/buy/<?= $item->id ?>" hx-target="#shop-panel" hx-swap="outerHTML"
                                      class="flex items-center justify-end gap-2">
                                    <?= csrf_field() ?>
                                    <label class="sr-only" for="qty-<?= $item->id ?>">Quantity</label>
                                    <input id="qty-<?= $item->id ?>" type="number" name="quantity" value="1" min="1" max="1000" class="input w-20 py-1">
                                    <button type="submit" class="btn btn-secondary px-3 py-1 text-xs">Buy</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php if (!empty($oob)): ?>
    <?= $this->partial('player-status', ['player' => $player, 'oob' => true]) ?>
<?php endif; ?>
