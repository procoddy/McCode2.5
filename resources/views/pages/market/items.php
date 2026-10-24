<?php
/**
 * @var list<array<string, mixed>> $listings
 * @var list<App\Modules\Inventory\Models\InventoryEntry> $inventory
 * @var App\Modules\Players\Models\Player $player
 */
use App\Support\Money;

$this->set('title', 'Item Market');
$price = fn (int $amount, string $currency) => $currency === 'crystals'
    ? number_format($amount) . ' ' . config('game.crystals_name')
    : Money::of($amount)->format();
?>
<h1 class="mb-6 text-2xl font-semibold">Item Market</h1>

<?= $this->partial('error-summary', ['errors' => $errors ?? []]) ?>

<?php if ($listings === []): ?>
    <div class="card mb-6 text-center text-gray-500 dark:text-gray-400">Nothing is listed on the market right now.</div>
<?php else: ?>
    <div class="mb-6 overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left dark:bg-gray-800/50">
                <tr>
                    <th class="px-4 py-2 font-semibold">Item</th>
                    <th class="px-4 py-2 font-semibold">Seller</th>
                    <th class="px-4 py-2 font-semibold">Qty</th>
                    <th class="px-4 py-2 font-semibold">Price each</th>
                    <th class="px-4 py-2 text-right font-semibold"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <?php foreach ($listings as $l): ?>
                    <tr>
                        <td class="px-4 py-2">
                            <a href="/inventory/<?= (int) $l['item_id'] ?>" class="link"><?= e($l['item_name']) ?></a>
                            <span class="text-gray-500"><?= e($l['type_name'] ?? '') ?></span>
                        </td>
                        <td class="px-4 py-2"><a href="/players/<?= (int) $l['seller_id'] ?>" class="link"><?= e($l['seller']) ?></a></td>
                        <td class="px-4 py-2"><?= number_format((int) $l['quantity']) ?></td>
                        <td class="px-4 py-2 whitespace-nowrap"><?= e($price((int) $l['price'], $l['currency'])) ?></td>
                        <td class="px-4 py-2">
                            <?php if ((int) $l['seller_id'] === $player->id): ?>
                                <form method="post" action="/item-market/<?= (int) $l['id'] ?>/remove" class="text-right">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="link cursor-pointer">Remove</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/item-market/<?= (int) $l['id'] ?>/buy" class="flex items-center justify-end gap-2">
                                    <?= csrf_field() ?>
                                    <label class="sr-only" for="buy-<?= (int) $l['id'] ?>">Quantity</label>
                                    <input id="buy-<?= (int) $l['id'] ?>" type="number" name="quantity" value="1" min="1" max="<?= (int) $l['quantity'] ?>" class="input w-20 py-1">
                                    <button type="submit" class="btn btn-secondary px-3 py-1 text-xs">Buy</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($inventory !== []): ?>
    <form method="post" action="/item-market" class="card max-w-xl space-y-4">
        <?= csrf_field() ?>
        <h2 class="font-semibold">Add a listing</h2>
        <?= $this->component('form-field', [
            'name' => 'item_id', 'label' => 'Item', 'type' => 'select', 'value' => old('item_id'), 'errors' => [],
            'options' => array_combine(
                array_map(fn ($entry) => $entry->item->id, $inventory),
                array_map(fn ($entry) => "{$entry->item->name} (you have {$entry->quantity})", $inventory),
            ),
        ]) ?>
        <div class="grid gap-4 sm:grid-cols-3">
            <?= $this->component('form-field', ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'value' => old('quantity', '1'), 'errors' => []]) ?>
            <?= $this->component('form-field', ['name' => 'price', 'label' => 'Price each', 'type' => 'number', 'value' => old('price'), 'errors' => []]) ?>
            <?= $this->component('form-field', [
                'name' => 'currency', 'label' => 'Currency', 'type' => 'select', 'value' => old('currency', 'money'), 'errors' => [],
                'options' => ['money' => 'Money', 'crystals' => config('game.crystals_name')],
            ]) ?>
        </div>
        <?= $this->component('button', ['label' => 'Add listing', 'type' => 'submit']) ?>
    </form>
<?php endif; ?>
